<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class QueueWorkerService
{
    protected string $pidFile;

    public function __construct()
    {
        $this->pidFile = storage_path('app/queue-worker.pid');
    }

    /**
     * Check if queue worker process is running (by stored PID or by finding queue:work process).
     */
    public function isRunning(): bool
    {
        $pid = $this->getStoredPid();
        if ($pid !== null && $this->isProcessAlive($pid)) {
            return true;
        }
        if ($pid !== null) {
            $this->clearPidFile();
        }
        return $this->hasQueueWorkProcess();
    }

    /**
     * Get stored PID from file.
     */
    public function getStoredPid(): ?int
    {
        if (!File::exists($this->pidFile)) {
            return null;
        }
        $content = trim(File::get($this->pidFile));
        return is_numeric($content) ? (int) $content : null;
    }

    /**
     * Start queue worker in background. Returns ['success' => bool, 'message' => string, 'pid' => int|null].
     */
    public function start(): array
    {
        if ($this->isRunning()) {
            return [
                'success' => true,
                'message' => 'عامل الطابور يعمل بالفعل.',
                'pid' => $this->getStoredPid(),
            ];
        }

        $this->clearPidFile();

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->startWindows();
        }

        return $this->startLinux();
    }

    /**
     * Stop queue worker. Returns ['success' => bool, 'message' => string].
     */
    public function stop(): array
    {
        $pid = $this->getStoredPid();
        if ($pid !== null && $this->isProcessAlive($pid)) {
            $this->killProcess($pid);
            $this->clearPidFile();
            return ['success' => true, 'message' => 'تم إيقاف عامل الطابور.'];
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $killed = $this->killQueueWorkProcessesWindows();
            return [
                'success' => true,
                'message' => $killed ? 'تم إيقاف عامل الطابور.' : 'لم يتم العثور على عملية عامل الطابور.',
            ];
        }

        $pids = $this->findQueueWorkPidsLinux();
        foreach ($pids as $p) {
            $this->killProcess($p);
        }
        $this->clearPidFile();
        return [
            'success' => true,
            'message' => count($pids) > 0 ? 'تم إيقاف عامل الطابور.' : 'لم يتم العثور على عملية عامل الطابور.',
        ];
    }

    /**
     * Get status for display. Returns ['running' => bool, 'pid' => int|null, 'message' => string].
     */
    public function status(): array
    {
        $running = $this->isRunning();
        $pid = $this->getStoredPid();
        if ($running && $pid === null) {
            $pid = PHP_OS_FAMILY === 'Windows' ? null : $this->findQueueWorkPidsLinux()[0] ?? null;
        }
        return [
            'running' => $running,
            'pid' => $pid,
            'message' => $running ? 'عامل الطابور يعمل.' : 'عامل الطابور متوقف.',
        ];
    }

    protected function startLinux(): array
    {
        $logFile = storage_path('logs/queue-worker.log');
        $artisan = base_path('artisan');
        $php = PHP_BINARY;
        $cmd = sprintf(
            'nohup %s %s queue:work --queue=whatsapp,default >> %s 2>&1 & echo $!',
            escapeshellarg($php),
            escapeshellarg($artisan),
            escapeshellarg($logFile)
        );
        $pid = (int) trim(shell_exec($cmd) ?? '0');
        if ($pid > 0) {
            File::put($this->pidFile, (string) $pid);
            return [
                'success' => true,
                'message' => 'تم تشغيل عامل الطابور.',
                'pid' => $pid,
            ];
        }
        Log::warning('Queue worker start failed: could not get PID');
        return [
            'success' => false,
            'message' => 'فشل تشغيل عامل الطابور (لم يتم الحصول على معرف العملية).',
            'pid' => null,
        ];
    }

    protected function startWindows(): array
    {
        $php = PHP_BINARY;
        $artisan = base_path('artisan');
        $cmd = sprintf('start /B "" %s %s queue:work --queue=whatsapp,default', escapeshellarg($php), escapeshellarg($artisan));
        pclose(popen($cmd, 'r'));

        usleep(500000);
        $pids = $this->findQueueWorkPidsWindows();
        $pid = $pids[0] ?? null;
        if ($pid > 0) {
            File::put($this->pidFile, (string) $pid);
            return [
                'success' => true,
                'message' => 'تم تشغيل عامل الطابور.',
                'pid' => $pid,
            ];
        }

        Log::warning('Queue worker start on Windows: process may have started but PID not found');
        return [
            'success' => true,
            'message' => 'تم بدء تشغيل عامل الطابور. إن لم يعمل، شغّله يدوياً: scripts\\queue-work.bat',
            'pid' => null,
        ];
    }

    /**
     * @return int[]
     */
    protected function findQueueWorkPidsWindows(): array
    {
        $out = [];
        exec('wmic process where "name=\'php.exe\'" get ProcessId,CommandLine 2>nul', $out);
        $pids = [];
        foreach ($out as $line) {
            if (stripos($line, 'queue:work') === false) {
                continue;
            }
            if (preg_match('/\s+(\d+)\s*$/', trim($line), $m)) {
                $pids[] = (int) $m[1];
            }
        }
        return $pids;
    }

    protected function isProcessAlive(int $pid): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $out = [];
            exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH 2>nul', $out);
            $line = implode(' ', $out);
            return str_contains($line, (string) $pid);
        }
        return function_exists('posix_kill') ? @posix_kill($pid, 0) : file_exists("/proc/{$pid}");
    }

    protected function killProcess(int $pid): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec('taskkill /PID ' . $pid . ' /F 2>nul');
        } else {
            if (function_exists('posix_kill')) {
                posix_kill($pid, SIGTERM);
            } else {
                exec('kill ' . $pid . ' 2>/dev/null');
            }
        }
    }

    protected function clearPidFile(): void
    {
        if (File::exists($this->pidFile)) {
            File::delete($this->pidFile);
        }
    }

    protected function hasQueueWorkProcess(): bool
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return count($this->findQueueWorkPidsWindows()) > 0;
        }
        $pids = $this->findQueueWorkPidsLinux();
        return count($pids) > 0;
    }

    /**
     * @return int[]
     */
    protected function findQueueWorkPidsLinux(): array
    {
        $out = [];
        exec('pgrep -f "artisan queue:work" 2>/dev/null', $out);
        return array_map('intval', array_filter($out, 'is_numeric'));
    }

    protected function killQueueWorkProcessesWindows(): bool
    {
        $out = [];
        exec('wmic process where "name=\'php.exe\'" get ProcessId,CommandLine 2>nul', $out);
        $killed = false;
        foreach ($out as $line) {
            if (stripos($line, 'queue:work') === false) {
                continue;
            }
            if (preg_match('/\s+(\d+)\s*$/', trim($line), $m)) {
                $pid = (int) $m[1];
                $this->killProcess($pid);
                $killed = true;
            }
        }
        return $killed;
    }
}
