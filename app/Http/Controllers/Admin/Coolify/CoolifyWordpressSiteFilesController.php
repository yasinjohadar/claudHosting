<?php

namespace App\Http\Controllers\Admin\Coolify;

use App\Http\Controllers\Concerns\ResolvesAuthorizedWordpressSite;
use App\Http\Controllers\Controller;
use App\Models\CoolifyWordpressSite;
use App\Services\Coolify\ContainerFileManager;
use App\Services\Coolify\ContainerInspector;
use App\Services\Coolify\CoolifySettingsService;
use App\Services\Coolify\TerminalSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CoolifyWordpressSiteFilesController extends Controller
{
    use ResolvesAuthorizedWordpressSite;

    public function __construct(
        protected ContainerFileManager $files,
        protected ContainerInspector $inspector,
        protected TerminalSessionService $terminal,
        protected CoolifySettingsService $settings
    ) {
        $this->middleware('auth');
    }

    public function list(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $result = $this->files->listDirectory($site, (string) $request->query('path', ''));

        return response()->json($result);
    }

    public function read(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $path = (string) $request->query('path', '');
        if ($path === '') {
            return response()->json(['success' => false, 'message' => 'المسار مطلوب'], 422);
        }

        $result = $this->files->readFile($site, $path);
        if (! ($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $binary = $result['content'] ?? '';
        unset($result['content']);
        $result['content_base64'] = base64_encode($binary);
        if (($result['text'] ?? null) !== null && $this->isUtf8($binary)) {
            $result['content_text'] = $binary;
        }
        unset($result['text']);

        return response()->json($result);
    }

    public function write(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $validated = $request->validate([
            'path' => 'required|string|max:512',
            'content' => 'required|string',
        ]);

        $result = $this->files->writeFile($site, $validated['path'], $validated['content']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function upload(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $request->validate([
            'path' => 'nullable|string|max:512',
            'file' => 'required|file',
        ]);

        $result = $this->files->uploadFile(
            $site,
            (string) $request->input('path', ''),
            $request->file('file')
        );

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function mkdir(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $validated = $request->validate(['path' => 'required|string|max:512']);
        $result = $this->files->mkdir($site, $validated['path']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function rename(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $validated = $request->validate([
            'from' => 'required|string|max:512',
            'to' => 'required|string|max:512',
        ]);
        $result = $this->files->renamePath($site, $validated['from'], $validated['to']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $validated = $request->validate(['path' => 'required|string|max:512']);
        $result = $this->files->deletePath($site, $validated['path']);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function download(Request $request, string $uuid): BinaryFileResponse|JsonResponse
    {
        $site = $this->site($uuid);
        $path = (string) $request->query('path', '');
        if ($path === '') {
            return response()->json(['success' => false, 'message' => 'المسار مطلوب'], 422);
        }

        $result = $this->files->downloadToLocal($site, $path);
        if (! ($result['success'] ?? false) || empty($result['local_path'])) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'فشل التنزيل'], 422);
        }

        return response()->download($result['local_path'], $result['filename'] ?? 'download')->deleteFileAfterSend(true);
    }

    public function dockerLogs(Request $request, string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $tail = (int) $request->query('tail', 500);
        $result = $this->inspector->containerLogs($site, $tail);

        return response()->json($result);
    }

    public function dockerInspect(string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $result = $this->inspector->inspect($site);

        return response()->json($result);
    }

    public function terminalSession(string $uuid): JsonResponse
    {
        $site = $this->site($uuid);
        $result = $this->terminal->createSession($site, (int) Auth::id());

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function terminalCommands(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'groups' => config('terminal_commands.groups', []),
            'bridge_enabled' => $this->settings->getTerminalBridgeConfig()['enabled'] ?? false,
        ]);
    }

    protected function site(string $uuid): CoolifyWordpressSite
    {
        return $this->resolveAuthorizedWordpressSite($uuid);
    }

    protected function isUtf8(string $data): bool
    {
        return mb_check_encoding($data, 'UTF-8');
    }
}
