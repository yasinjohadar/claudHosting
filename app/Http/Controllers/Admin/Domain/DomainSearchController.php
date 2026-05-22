<?php

namespace App\Http\Controllers\Admin\Domain;

use App\Http\Controllers\Controller;
use App\Services\Domain\DomainAvailabilitySearchService;
use Illuminate\Http\Request;

class DomainSearchController extends Controller
{
    public function __construct(protected DomainAvailabilitySearchService $searchService)
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = trim((string) $request->query('q', ''));
        $payload = [
            'query' => '',
            'configured' => [
                'cloudflare' => false,
                'namecom' => false,
            ],
            'errors' => ['cloudflare' => null, 'namecom' => null],
            'rows' => [],
        ];

        if ($query !== '') {
            $payload = $this->searchService->search($query);
        }

        if ($request->ajax() || $request->wantsJson()) {
            if ($query === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'أدخل كلمة مفتاحية أو نطاقاً للبحث',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'query' => $payload['query'],
                'count' => count($payload['rows']),
                'html' => view('admin.domains.partials.search-results', [
                    'payload' => $payload,
                    'q' => $query,
                ])->render(),
            ]);
        }

        return view('admin.domains.search', [
            'q' => $query,
            'payload' => $payload,
        ]);
    }
}
