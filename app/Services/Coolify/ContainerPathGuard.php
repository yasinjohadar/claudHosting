<?php

namespace App\Services\Coolify;

class ContainerPathGuard
{
    /**
     * @return array{ok: bool, relative?: string, absolute?: string, message?: string}
     */
    public function resolve(ContainerExecutionContext $ctx, string $inputPath): array
    {
        $root = rtrim(str_replace('\\', '/', $ctx->wordpressRoot), '/');
        if ($root === '') {
            return ['ok' => false, 'message' => 'جذر WordPress غير معروف'];
        }

        $path = str_replace('\\', '/', trim($inputPath));
        if ($path === '' || $path === '/' || $path === '.') {
            return ['ok' => true, 'relative' => '', 'absolute' => $root];
        }

        if (str_starts_with($path, $root.'/') || $path === $root) {
            $path = ltrim(substr($path, strlen($root)), '/');
        }

        $path = ltrim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                return ['ok' => false, 'message' => 'مسار غير مسموح (..)'];
            }
            if (str_contains($segment, "\0")) {
                return ['ok' => false, 'message' => 'مسار غير صالح'];
            }
            $resolved[] = $segment;
        }

        $relative = implode('/', $resolved);
        $absolute = $relative === '' ? $root : $root.'/'.$relative;

        return ['ok' => true, 'relative' => $relative, 'absolute' => $absolute];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function assertReadable(ContainerExecutionContext $ctx, string $inputPath): array
    {
        $resolved = $this->resolve($ctx, $inputPath);
        if (! ($resolved['ok'] ?? false)) {
            return $resolved;
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function assertWritable(ContainerExecutionContext $ctx, string $inputPath, bool $allowProtected = false): array
    {
        $resolved = $this->resolve($ctx, $inputPath);
        if (! ($resolved['ok'] ?? false)) {
            return $resolved;
        }

        if (! $allowProtected && $this->isProtectedPath($resolved['relative'] ?? '')) {
            return ['ok' => false, 'message' => 'هذا الملف محمي من التعديل عبر مدير الملفات'];
        }

        return ['ok' => true];
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function assertDeletable(ContainerExecutionContext $ctx, string $inputPath): array
    {
        $resolved = $this->resolve($ctx, $inputPath);
        if (! ($resolved['ok'] ?? false)) {
            return $resolved;
        }

        $relative = $resolved['relative'] ?? '';
        $protected = config('coolify_files.protected_delete_paths', []);
        if (in_array($relative, $protected, true)) {
            return ['ok' => false, 'message' => 'لا يمكن حذف جذر الموقع'];
        }

        if ($this->isProtectedPath($relative)) {
            return ['ok' => false, 'message' => 'ملف محمي من الحذف'];
        }

        return ['ok' => true];
    }

    public function isProtectedPath(string $relative): bool
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        foreach (config('coolify_files.protected_paths', []) as $protected) {
            if ($relative === $protected || str_ends_with($relative, '/'.$protected)) {
                return true;
            }
        }

        return false;
    }
}
