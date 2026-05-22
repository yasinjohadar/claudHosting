<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserImpersonationToken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ClientImpersonationService
{
    public function createLink(User $target, User $admin): array
    {
        $this->assertCanCreate($target, $admin);

        $plain = Str::random(64);
        $expiresAt = now()->addMinutes(config('impersonation.token_ttl_minutes', 15));

        UserImpersonationToken::create([
            'user_id' => $target->id,
            'created_by' => $admin->id,
            'token_hash' => $this->hashToken($plain),
            'expires_at' => $expiresAt,
        ]);

        return [
            'url' => route('client.impersonate', ['token' => $plain]),
            'expires_at' => $expiresAt->toIso8601String(),
            'expires_at_human' => $expiresAt->locale('ar')->translatedFormat('Y-m-d H:i'),
            'client_name' => $target->name,
        ];
    }

    public function consume(string $plain, ?string $ip = null): User
    {
        $record = $this->findValidToken($plain);

        if ($record->isUsed()) {
            throw new InvalidArgumentException('تم استخدام هذا الرابط مسبقاً.');
        }

        if ($record->isExpired()) {
            throw new InvalidArgumentException('انتهت صلاحية الرابط.');
        }

        $target = $record->user;
        $this->assertTargetCanBeImpersonated($target);

        $record->update([
            'used_at' => now(),
            'used_ip' => $ip,
        ]);

        Auth::logout();

        Auth::login($target);
        session()->regenerate();
        session(['impersonator_id' => $record->created_by]);

        return $target;
    }

    public function stop(): ?User
    {
        $impersonatorId = session('impersonator_id');

        if (! $impersonatorId) {
            return null;
        }

        $admin = User::find($impersonatorId);

        session()->forget('impersonator_id');
        Auth::logout();

        if ($admin) {
            Auth::login($admin);
            session()->regenerate();
        }

        return $admin;
    }

    public function assertCanCreate(User $target, User $admin): void
    {
        if (! $admin->isAdminPanelUser()) {
            throw new AuthorizationException('غير مصرح بإنشاء رابط دخول كعميل.');
        }

        if ($admin->id === $target->id) {
            throw new InvalidArgumentException('لا يمكن إنشاء رابط لحسابك الحالي.');
        }

        if ($target->isAdminPanelUser()) {
            throw new InvalidArgumentException('لا يمكن الدخول كمستخدم لوحة الإدارة.');
        }

        $this->assertTargetCanBeImpersonated($target);
    }

    protected function assertTargetCanBeImpersonated(User $target): void
    {
        if (! $target->is_active) {
            throw new InvalidArgumentException('حساب العميل غير نشط.');
        }

        if ($target->status === 'banned') {
            throw new InvalidArgumentException('حساب العميل محظور.');
        }
    }

    protected function findValidToken(string $plain): UserImpersonationToken
    {
        $record = UserImpersonationToken::query()
            ->where('token_hash', $this->hashToken($plain))
            ->with('user')
            ->first();

        if (! $record) {
            throw new InvalidArgumentException('الرابط غير صالح.');
        }

        return $record;
    }

    protected function hashToken(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
