<?php

namespace App\Http\Controllers\Admin\Cloudflare;

use App\Http\Controllers\Controller;
use App\Services\CloudflareApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CloudflareRegistrarController extends Controller
{
    public function __construct(protected CloudflareApiService $cloudflare)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (! $this->cloudflare->isConfigured()) {
            return redirect()->route('admin.cloudflare.settings.index')
                ->with('error', 'يرجى ضبط إعدادات Cloudflare أولاً');
        }

        $forceRefresh = $request->boolean('refresh');
        if ($forceRefresh) {
            $this->cloudflare->clearCaches();
        }

        $meta = $this->cloudflare->listRegistrarDomainsWithMeta($forceRefresh);
        $domains = $meta['domains'];
        $error = $meta['error'];
        $registrarTotal = (int) ($meta['total_count'] ?? count($domains));

        if (empty($domains) && ! $forceRefresh) {
            $this->cloudflare->clearCaches();
            $meta = $this->cloudflare->listRegistrarDomainsWithMeta(true);
            $domains = $meta['domains'];
            $error = $meta['error'];
            $registrarTotal = (int) ($meta['total_count'] ?? count($domains));
        }

        $zonesCount = count($this->cloudflare->listAllZones());

        if ($error === null && empty($domains) && ! $this->cloudflare->getAccountId()) {
            $error = 'تعذر تحديد Account ID — أضفه في الإعدادات أو تحقق من الرمز';
        }

        $sort = $request->query('sort', 'expires');
        $sortDir = strtolower((string) $request->query('dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $domains = $this->sortRegistrarDomains($domains, $sort, $sortDir);

        return view('admin.cloudflare.registrar.index', compact(
            'domains',
            'error',
            'zonesCount',
            'registrarTotal',
            'sort',
            'sortDir'
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $domains
     * @return array<int, array<string, mixed>>
     */
    protected function sortRegistrarDomains(array $domains, string $sort, string $dir): array
    {
        if ($sort !== 'expires' || count($domains) < 2) {
            return $domains;
        }

        usort($domains, function (array $a, array $b) use ($dir): int {
            $tsA = self::expiresTimestamp($a);
            $tsB = self::expiresTimestamp($b);

            if ($tsA === $tsB) {
                return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
            }

            if ($tsA === null) {
                return 1;
            }
            if ($tsB === null) {
                return -1;
            }

            $cmp = $tsA <=> $tsB;

            return $dir === 'desc' ? -$cmp : $cmp;
        });

        return $domains;
    }

    /**
     * @param  array<string, mixed>  $domain
     */
    protected static function expiresTimestamp(array $domain): ?int
    {
        $raw = $domain['expires_at'] ?? $domain['payment_expires_at'] ?? null;
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->timestamp;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{label: string, badge_class: string, is_active: bool}
     */
    public static function formatStatus(mixed $raw): array
    {
        $key = is_string($raw) ? strtolower(trim($raw)) : '';

        $map = [
            'registrationactive' => ['label' => 'فعال', 'badge_class' => 'bg-success-transparent text-success', 'is_active' => true],
            'active' => ['label' => 'فعال', 'badge_class' => 'bg-success-transparent text-success', 'is_active' => true],
            'ok' => ['label' => 'فعال', 'badge_class' => 'bg-success-transparent text-success', 'is_active' => true],
            'pending' => ['label' => 'قيد الانتظار', 'badge_class' => 'bg-warning-transparent text-warning', 'is_active' => false],
            'pendingtransfer' => ['label' => 'نقل قيد التنفيذ', 'badge_class' => 'bg-warning-transparent text-warning', 'is_active' => false],
            'expired' => ['label' => 'منتهي', 'badge_class' => 'bg-danger-transparent text-danger', 'is_active' => false],
            'deleted' => ['label' => 'محذوف', 'badge_class' => 'bg-danger-transparent text-danger', 'is_active' => false],
            'locked' => ['label' => 'مقفل', 'badge_class' => 'bg-secondary-transparent text-secondary', 'is_active' => false],
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        if ($key === '') {
            return ['label' => '—', 'badge_class' => 'bg-secondary-transparent text-muted', 'is_active' => false];
        }

        return [
            'label' => $raw,
            'badge_class' => 'bg-secondary-transparent text-secondary',
            'is_active' => false,
        ];
    }

    public static function isExpiringSoon(?string $expiresAt, int $withinDays = 30): bool
    {
        if (! $expiresAt) {
            return false;
        }

        try {
            $expires = Carbon::parse($expiresAt);

            return $expires->isFuture() && $expires->lte(now()->addDays($withinDays));
        } catch (\Throwable) {
            return false;
        }
    }

    public static function formatDate(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return $value;
        }
    }
}
