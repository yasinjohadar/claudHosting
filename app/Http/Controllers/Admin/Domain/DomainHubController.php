<?php

namespace App\Http\Controllers\Admin\Domain;

use App\Http\Controllers\Controller;
use App\Services\Domain\DomainCommandCenterService;
use Illuminate\Http\Request;

class DomainHubController extends Controller
{
    public function __construct(protected DomainCommandCenterService $commandCenter)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $forceRefresh = $request->boolean('refresh');
        $payload = $this->commandCenter->build($forceRefresh);

        $filters = [
            'q' => $request->query('q', ''),
            'source' => $request->query('source', 'all'),
            'status' => $request->query('status', 'all'),
            'sort' => $request->query('sort', 'name'),
            'dir' => $request->query('dir', 'asc'),
        ];

        $rows = $this->commandCenter->filterRows($payload['rows'], $filters);

        return view('admin.domains.index', [
            'rows' => $rows,
            'stats' => $payload['stats'],
            'errors' => $payload['errors'],
            'configured' => $payload['configured'],
            'filters' => $filters,
            'totalBeforeFilter' => count($payload['rows']),
        ]);
    }
}
