<?php

namespace App\Services\Coolify;

use Illuminate\Support\Str;

class WordpressCliActionRunner
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function actions(): array
    {
        return config('wordpress_cli.actions', []);
    }

    /**
     * @return list<string>
     */
    public function allowedActionNames(): array
    {
        return array_keys($this->actions());
    }

    /**
     * @return list<string>
     */
    public function asyncActionNames(): array
    {
        $async = [];
        foreach ($this->actions() as $name => $def) {
            if ($def['async'] ?? false) {
                $async[] = $name;
            }
        }

        return $async;
    }

    public function isAllowed(string $action): bool
    {
        return isset($this->actions()[$action]);
    }

    public function isAsync(string $action): bool
    {
        return (bool) ($this->actions()[$action]['async'] ?? false);
    }

    public function definition(string $action): ?array
    {
        return $this->actions()[$action] ?? null;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{success: bool, message?: string, command?: string, timeout?: int, handler?: string, lifecycle?: string}
     */
    public function resolve(string $action, array $params = []): array
    {
        $def = $this->definition($action);
        if ($def === null) {
            return ['success' => false, 'message' => 'إجراء غير مسموح'];
        }

        foreach ($def['params'] ?? [] as $key) {
            if (! filled($params[$key] ?? null)) {
                return ['success' => false, 'message' => "المعامل «{$key}» مطلوب"];
            }
        }

        if (($def['type'] ?? '') === 'wp') {
            $command = $this->buildWpCommand((string) ($def['command'] ?? ''), $params);
            if ($command === '') {
                return ['success' => false, 'message' => 'أمر WP-CLI غير صالح'];
            }

            return [
                'success' => true,
                'command' => $command,
                'timeout' => (int) ($def['timeout'] ?? 120),
            ];
        }

        if (($def['type'] ?? '') === 'special') {
            return [
                'success' => true,
                'handler' => (string) ($def['handler'] ?? $action),
                'lifecycle' => $def['lifecycle'] ?? null,
                'timeout' => (int) ($def['timeout'] ?? 120),
            ];
        }

        return ['success' => false, 'message' => 'نوع إجراء غير مدعوم'];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function buildWpCommand(string $template, array $params): string
    {
        $command = $template;
        foreach ($params as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            $safe = $this->sanitizeParam((string) $key, (string) $value);
            $command = str_replace('{'.$key.'}', $safe, $command);
        }

        if (preg_match('/\{[a-z_]+\}/', $command)) {
            return '';
        }

        if (! empty($params['activate']) && str_contains($command, 'plugin install')) {
            $command .= ' --activate';
        }

        return trim($command);
    }

    /**
     * @return array{success: bool, message?: string, dangerous?: bool}
     */
    public function validateRawCommand(string $command, bool $confirmDangerous = false): array
    {
        $command = trim($command);
        $rules = config('wordpress_cli.raw_cli', []);
        $max = (int) ($rules['max_length'] ?? 512);

        if ($command === '') {
            return ['success' => false, 'message' => 'الأمر فارغ'];
        }
        if (strlen($command) > $max) {
            return ['success' => false, 'message' => "الأمر أطول من {$max} حرف"];
        }

        foreach ($rules['blocked_patterns'] ?? [] as $pattern) {
            if (@preg_match($pattern, $command) === 1) {
                return ['success' => false, 'message' => 'الأمر محظور لأسباب أمنية'];
            }
        }

        $first = strtolower(strtok($command, ' ') ?: '');
        $allowed = $rules['allowed_prefixes'] ?? [];
        $prefixOk = false;
        foreach ($allowed as $prefix) {
            if ($first === $prefix || str_starts_with($command, $prefix.' ')) {
                $prefixOk = true;
                break;
            }
        }
        if (! $prefixOk) {
            return ['success' => false, 'message' => 'بادئة الأمر غير مسموحة. استخدم أوامر WP-CLI المعروفة فقط.'];
        }

        $dangerous = false;
        foreach ($rules['dangerous_patterns'] ?? [] as $pattern) {
            if (@preg_match($pattern, $command) === 1) {
                $dangerous = true;
                break;
            }
        }

        if ($dangerous && ! $confirmDangerous) {
            return ['success' => false, 'message' => 'أمر خطير — فعّل التأكيد (confirm_dangerous)', 'dangerous' => true];
        }

        return ['success' => true];
    }

    protected function sanitizeParam(string $key, string $value): string
    {
        $clean = match ($key) {
            'slug' => preg_replace('/[^a-z0-9_-]/i', '', $value) ?? '',
            'login' => preg_replace('/[^a-z0-9_@.\-]/i', '', $value) ?? '',
            'role' => preg_replace('/[^a-z0-9_-]/i', '', $value) ?? '',
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '',
            'user_id', 'post_id' => preg_replace('/[^0-9]/', '', $value) ?? '',
            'option' => preg_replace('/[^a-z0-9_\-]/i', '', $value) ?? '',
            'hook' => preg_replace('/[^a-z0-9_\-]/i', '', $value) ?? '',
            'old', 'new', 'value', 'title', 'command' => $value,
            default => Str::limit(preg_replace('/[^\p{L}\p{N}@._\-:\/ ]/u', '', $value) ?? '', 200, ''),
        };

        if (in_array($key, ['old', 'new', 'value', 'title', 'email'], true)) {
            return escapeshellarg($clean);
        }

        return $clean;
    }
}
