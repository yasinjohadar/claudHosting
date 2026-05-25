<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\System\DatabaseInspectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemDatabaseController extends Controller
{
    public function __construct(
        protected DatabaseInspectorService $inspector
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $connections = $this->inspector->listConnections();
        $connection = $this->resolveConnection($request, $connections);
        $refresh = $request->boolean('refresh');

        try {
            $overview = $this->inspector->getOverview($connection, $refresh);
            $tables = $this->inspector->getTables($connection, $refresh);
            $error = null;
        } catch (\Throwable $e) {
            $overview = null;
            $tables = [];
            $error = $e->getMessage();
        }

        return view('admin.system-database.index', compact(
            'connections',
            'connection',
            'overview',
            'tables',
            'error'
        ));
    }

    public function table(Request $request, string $table): JsonResponse
    {
        $connections = $this->inspector->listConnections();
        $connection = $this->resolveConnection($request, $connections);

        try {
            $detail = $this->inspector->getTableDetail($connection, $table);

            return response()->json([
                'success' => true,
                'data' => $detail,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذّر تحميل تفاصيل الجدول: '.$e->getMessage(),
            ], 500);
        }
    }

    public function refresh(Request $request): RedirectResponse
    {
        $connections = $this->inspector->listConnections();
        $connection = $this->resolveConnection($request, $connections);

        $this->inspector->clearCache($connection);

        return redirect()
            ->route('admin.system-database.index', ['connection' => $connection, 'refreshed' => 1]);
    }

    /**
     * @param  array<int, array{name: string}>  $connections
     */
    protected function resolveConnection(Request $request, array $connections): string
    {
        $default = (string) config('database.default');
        $requested = $request->input('connection', $request->query('connection', $default));
        $names = array_column($connections, 'name');

        if (! in_array($requested, $names, true)) {
            $requested = $names[0] ?? $default;
        }

        return $this->inspector->resolveConnection($requested);
    }
}
