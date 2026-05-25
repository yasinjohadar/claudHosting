<?php

namespace App\Services\Coolify;

use App\Models\ContainerFileAudit;
use App\Models\CoolifyWordpressSite;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ContainerFileManager
{
    public function __construct(
        protected CoolifySshExecutor $ssh,
        protected ContainerPathGuard $guard,
        protected ContainerContextFactory $contextFactory
    ) {}

    /**
     * @return array{success: bool, entries?: array<int, array<string, mixed>>, path?: string, message?: string}
     */
    public function listDirectory(CoolifyWordpressSite $site, string $relativePath = ''): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $resolved = $this->guard->resolve($context, $relativePath);
        if (! ($resolved['ok'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'مسار غير صالح'];
        }

        $abs = $resolved['absolute'];
        $inner = 'ls -la --time-style=+%s '.escapeshellarg($abs).' 2>/dev/null || ls -la '.escapeshellarg($abs);
        $result = $this->dockerExec($context, $inner, 60);

        if (! ($result['success'] ?? false)) {
            $this->audit($site, 'list', $resolved['relative'] ?? '', false, $result['output'] ?? '');

            return ['success' => false, 'message' => $result['output'] ?: 'فشل قراءة المجلد'];
        }

        $entries = $this->parseLsOutput($result['output'] ?? '', $context, $resolved['relative'] ?? '');
        $this->audit($site, 'list', $resolved['relative'] ?? '', true);

        return [
            'success' => true,
            'path' => $resolved['relative'] ?? '',
            'entries' => $entries,
        ];
    }

    /**
     * @return array{success: bool, content?: string, encoding?: string, size?: int, message?: string}
     */
    public function readFile(CoolifyWordpressSite $site, string $relativePath): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $resolved = $this->guard->resolve($context, $relativePath);
        if (! ($resolved['ok'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'مسار غير صالح'];
        }

        $abs = $resolved['absolute'];
        $sizeResult = $this->dockerExec($context, 'stat -c%s '.escapeshellarg($abs).' 2>/dev/null || stat -f%z '.escapeshellarg($abs), 30);
        $size = (int) trim($sizeResult['output'] ?? '0');
        $maxRead = (int) config('coolify_files.max_read_bytes', 5 * 1024 * 1024);
        if ($size > $maxRead) {
            return ['success' => false, 'message' => 'الملف أكبر من الحد المسموح للقراءة ('.number_format($maxRead).' بايت)'];
        }

        $b64 = $this->dockerExec($context, 'base64 '.escapeshellarg($abs), 120);
        if (! ($b64['success'] ?? false)) {
            $this->audit($site, 'read', $resolved['relative'] ?? '', false, $b64['output'] ?? '');

            return ['success' => false, 'message' => $b64['output'] ?: 'فشل قراءة الملف'];
        }

        $raw = base64_decode(preg_replace('/\s+/', '', $b64['output'] ?? ''), true);
        if ($raw === false) {
            return ['success' => false, 'message' => 'فشل فك ترميز الملف'];
        }

        $this->audit($site, 'read', $resolved['relative'] ?? '', true);

        return [
            'success' => true,
            'content' => $raw,
            'encoding' => 'base64',
            'size' => strlen($raw),
            'text' => $this->isTextPath($resolved['relative'] ?? '') ? $raw : null,
        ];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function writeFile(CoolifyWordpressSite $site, string $relativePath, string $content, bool $allowProtected = false): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $check = $this->guard->assertWritable($context, $relativePath, $allowProtected);
        if (! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير مسموح'];
        }

        $resolved = $this->guard->resolve($context, $relativePath);
        $local = $this->localTempPath($context, 'write-'.Str::random(8));
        File::ensureDirectoryExists(dirname($local));
        file_put_contents($local, $content);

        $result = $this->copyHostFileToContainer($context, $local, $resolved['absolute']);
        @unlink($local);

        $this->audit($site, 'write', $resolved['relative'] ?? '', $result['success'] ?? false, $result['message'] ?? $result['output'] ?? '');

        return $result['success']
            ? ['success' => true, 'message' => 'تم حفظ الملف']
            : ['success' => false, 'message' => $result['message'] ?? $result['output'] ?? 'فشل الحفظ'];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function uploadFile(CoolifyWordpressSite $site, string $relativeDest, UploadedFile $file): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        $max = (int) config('coolify_files.max_upload_bytes', 50 * 1024 * 1024);
        if ($file->getSize() > $max) {
            return ['success' => false, 'message' => 'حجم الملف يتجاوز الحد المسموح'];
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $destRelative = $relativeDest === '' ? $file->getClientOriginalName() : rtrim($relativeDest, '/').'/'.$file->getClientOriginalName();
        $check = $this->guard->assertWritable($context, $destRelative);
        if (! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير مسموح'];
        }

        $resolved = $this->guard->resolve($context, $destRelative);
        $result = $this->copyHostFileToContainer($context, $file->getRealPath(), $resolved['absolute']);

        $this->audit($site, 'upload', $resolved['relative'] ?? '', $result['success'] ?? false, $result['message'] ?? $result['output'] ?? '');

        return $result['success']
            ? ['success' => true, 'message' => 'تم رفع الملف']
            : ['success' => false, 'message' => $result['message'] ?? $result['output'] ?? 'فشل الرفع'];
    }

    /**
     * @return array{success: bool, local_path?: string, filename?: string, message?: string}
     */
    public function downloadToLocal(CoolifyWordpressSite $site, string $relativePath): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $resolved = $this->guard->resolve($context, $relativePath);
        if (! ($resolved['ok'] ?? false)) {
            return ['success' => false, 'message' => $resolved['message'] ?? 'مسار غير صالح'];
        }

        $hostTmp = $this->hostTempPath($context, 'dl');
        $containerRef = $context->containerId.':'.$resolved['absolute'];
        $cp = sprintf('docker cp %s %s', escapeshellarg($containerRef), escapeshellarg($hostTmp));
        $cpResult = $this->ssh->run($context->host, $cp, 300);
        if (! ($cpResult['success'] ?? false)) {
            $this->audit($site, 'download', $resolved['relative'] ?? '', false, $cpResult['output'] ?? '');

            return ['success' => false, 'message' => $cpResult['output'] ?: 'فشل نسخ الملف من الحاوية'];
        }

        $local = $this->localTempPath($context, 'download-'.Str::random(8));
        File::ensureDirectoryExists(dirname($local));
        $dl = $this->ssh->downloadFile($context->host, $hostTmp, $local);
        $this->ssh->run($context->host, 'rm -f '.escapeshellarg($hostTmp), 15);

        if (! ($dl['success'] ?? false) || ! is_file($local)) {
            return ['success' => false, 'message' => $dl['output'] ?? 'فشل تنزيل الملف'];
        }

        $this->audit($site, 'download', $resolved['relative'] ?? '', true);

        return [
            'success' => true,
            'local_path' => $local,
            'filename' => basename($resolved['relative'] ?? 'file'),
        ];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function deletePath(CoolifyWordpressSite $site, string $relativePath): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $check = $this->guard->assertDeletable($context, $relativePath);
        if (! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير مسموح'];
        }

        $resolved = $this->guard->resolve($context, $relativePath);
        $inner = 'rm -rf '.escapeshellarg($resolved['absolute']);
        $result = $this->dockerExec($context, $inner, 60);

        $this->audit($site, 'delete', $resolved['relative'] ?? '', $result['success'] ?? false, $result['output'] ?? '');

        return ($result['success'] ?? false)
            ? ['success' => true, 'message' => 'تم الحذف']
            : ['success' => false, 'message' => $result['output'] ?: 'فشل الحذف'];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function mkdir(CoolifyWordpressSite $site, string $relativePath): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        $check = $this->guard->assertWritable($context, $relativePath);
        if (! ($check['ok'] ?? false)) {
            return ['success' => false, 'message' => $check['message'] ?? 'غير مسموح'];
        }

        $resolved = $this->guard->resolve($context, $relativePath);
        $inner = 'mkdir -p '.escapeshellarg($resolved['absolute']);
        $result = $this->dockerExec($context, $inner, 30);

        $this->audit($site, 'mkdir', $resolved['relative'] ?? '', $result['success'] ?? false, $result['output'] ?? '');

        return ($result['success'] ?? false)
            ? ['success' => true, 'message' => 'تم إنشاء المجلد']
            : ['success' => false, 'message' => $result['output'] ?: 'فشل إنشاء المجلد'];
    }

    /**
     * @return array{success: bool, message?: string}
     */
    public function renamePath(CoolifyWordpressSite $site, string $from, string $to): array
    {
        $ctx = $this->requireContext($site);
        if (! $ctx['success']) {
            return $ctx;
        }

        /** @var ContainerExecutionContext $context */
        $context = $ctx['context'];
        if (! ($this->guard->assertDeletable($context, $from)['ok'] ?? false)) {
            return ['success' => false, 'message' => 'لا يمكن نقل/إعادة تسمية المصدر'];
        }
        if (! ($this->guard->assertWritable($context, $to)['ok'] ?? false)) {
            return ['success' => false, 'message' => 'الوجهة غير مسموحة'];
        }

        $fromR = $this->guard->resolve($context, $from);
        $toR = $this->guard->resolve($context, $to);
        $inner = 'mv '.escapeshellarg($fromR['absolute']).' '.escapeshellarg($toR['absolute']);
        $result = $this->dockerExec($context, $inner, 30);

        $this->audit($site, 'rename', ($fromR['relative'] ?? '').' → '.($toR['relative'] ?? ''), $result['success'] ?? false, $result['output'] ?? '');

        return ($result['success'] ?? false)
            ? ['success' => true, 'message' => 'تمت إعادة التسمية']
            : ['success' => false, 'message' => $result['output'] ?: 'فشلت العملية'];
    }

    /**
     * @return array{success: bool, context?: ContainerExecutionContext, message?: string}
     */
    protected function requireContext(CoolifyWordpressSite $site): array
    {
        return $this->contextFactory->forSite($site->fresh() ?? $site);
    }

    /**
     * @return array{success: bool, output: string, message?: string}
     */
    protected function dockerExec(ContainerExecutionContext $ctx, string $innerShell, int $timeout): array
    {
        $remote = sprintf('docker exec %s sh -c %s', escapeshellarg($ctx->containerId), escapeshellarg($innerShell));
        $result = $this->ssh->run($ctx->host, $remote, $timeout);
        if ($result['success'] ?? false) {
            return $result;
        }

        $remoteRoot = sprintf('docker exec -u root %s sh -c %s', escapeshellarg($ctx->containerId), escapeshellarg($innerShell));

        return $this->ssh->run($ctx->host, $remoteRoot, $timeout);
    }

    /**
     * @return array{success: bool, message?: string, output?: string}
     */
    protected function copyHostFileToContainer(ContainerExecutionContext $ctx, string $localSource, string $containerAbsolute): array
    {
        if (! is_file($localSource)) {
            return ['success' => false, 'message' => 'الملف المصدر غير موجود'];
        }

        $hostTmp = $this->hostTempPath($ctx, 'up');
        $upload = $this->ssh->uploadFile($ctx->host, $localSource, $hostTmp);
        if (! ($upload['success'] ?? false)) {
            return ['success' => false, 'message' => $upload['output'] ?? 'فشل رفع الملف إلى السيرفر'];
        }

        $cp = sprintf(
            'docker cp %s %s:%s',
            escapeshellarg($hostTmp),
            escapeshellarg($ctx->containerId),
            escapeshellarg($containerAbsolute)
        );
        $cpResult = $this->ssh->run($ctx->host, $cp, 300);
        $this->ssh->run($ctx->host, 'rm -f '.escapeshellarg($hostTmp), 15);

        if (! ($cpResult['success'] ?? false)) {
            return ['success' => false, 'message' => $cpResult['output'] ?? 'فشل docker cp إلى الحاوية', 'output' => $cpResult['output'] ?? ''];
        }

        return ['success' => true];
    }

    protected function hostTempPath(ContainerExecutionContext $ctx, string $suffix): string
    {
        $prefix = rtrim((string) config('coolify_files.host_temp_prefix', '/tmp/claud-host'), '/');

        return $prefix.'/'.$ctx->siteUuid.'-'.$suffix.'-'.Str::random(6);
    }

    protected function localTempPath(ContainerExecutionContext $ctx, string $suffix): string
    {
        $dir = storage_path('app/'.config('coolify_files.local_temp_dir', 'container-tmp').'/'.$ctx->siteUuid);
        File::ensureDirectoryExists($dir);

        return $dir.'/'.$suffix;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function parseLsOutput(string $output, ContainerExecutionContext $ctx, string $currentRelative): array
    {
        $entries = [];
        $max = (int) config('coolify_files.max_list_entries', 500);
        $lines = preg_split('/\r\n|\r|\n/', trim($output)) ?: [];

        foreach ($lines as $line) {
            if ($line === '' || str_starts_with($line, 'total ')) {
                continue;
            }
            if (! preg_match('/^([-dlrwxStT]+)\s+\d+\s+\S+\s+\S+\s+(\d+)\s+(\d+)\s+(.+)$/', $line, $m)) {
                continue;
            }
            $name = trim($m[4]);
            if ($name === '.' || $name === '..') {
                continue;
            }
            $type = str_starts_with($m[1], 'd') ? 'dir' : 'file';
            $relative = $currentRelative === '' ? $name : $currentRelative.'/'.$name;
            $entries[] = [
                'name' => $name,
                'type' => $type,
                'size' => (int) $m[2],
                'modified_at' => (int) $m[3],
                'permissions' => $m[1],
                'relative' => $relative,
                'protected' => $this->guard->isProtectedPath($relative),
            ];
            if (count($entries) >= $max) {
                break;
            }
        }

        usort($entries, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'dir' ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $entries;
    }

    protected function isTextPath(string $relative): bool
    {
        $relative = strtolower($relative);
        if (str_ends_with($relative, '.blade.php')) {
            return true;
        }
        $ext = pathinfo($relative, PATHINFO_EXTENSION);

        return in_array($ext, config('coolify_files.text_extensions', []), true);
    }

    protected function audit(CoolifyWordpressSite $site, string $action, string $path, bool $success, string $detail = ''): void
    {
        try {
            ContainerFileAudit::query()->create([
                'coolify_wordpress_site_id' => $site->id,
                'user_id' => Auth::id(),
                'action' => $action,
                'path' => Str::limit($path, 500, ''),
                'success' => $success,
                'ip' => request()->ip(),
                'detail' => Str::limit($detail, 2000, ''),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // audits optional until migration runs
        }
    }
}
