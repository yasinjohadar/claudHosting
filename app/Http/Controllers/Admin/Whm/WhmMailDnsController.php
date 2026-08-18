<?php

namespace App\Http\Controllers\Admin\Whm;

use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Preview and apply a cPanel account's mail DNS into Cloudflare.
 *
 * Deliberately a separate controller from WhmAccountController: that class's five-argument
 * positional constructor is pinned by two unit tests, so growing it would break them for
 * no benefit.
 *
 * Admin-only by route group (`auth` + `admin.panel`). The client panel never reaches
 * these endpoints and its Blade never renders the trigger.
 */
class WhmMailDnsController extends Controller
{
    public function __construct(protected WhmMailDnsSyncService $sync)
    {
        $this->middleware('auth');
    }

    /**
     * Build the plan and diff it. Writes nothing.
     */
    public function preview(Request $request, WhmAccount $account): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'nullable|string|max:253',
        ]);

        $data = $this->sync->preview(
            $account,
            $validated['domain'] ?? null,
            $request->boolean('fresh')
        );

        return $this->respond($data);
    }

    /**
     * Apply the plan. The plan is re-derived server-side and its hash compared with the
     * one the operator saw, so a zone that changed since the preview cannot be applied
     * blind — and a tampered payload cannot smuggle in different records.
     */
    public function apply(Request $request, WhmAccount $account): JsonResponse
    {
        $validated = $request->validate([
            'domain' => 'nullable|string|max:253',
            'plan_hash' => 'required|string|size:64',
            'ack' => 'nullable|array',
            'ack.*' => 'string|max:64',
        ]);

        $data = $this->sync->apply(
            $account,
            $validated['domain'] ?? null,
            $validated['plan_hash'],
            array_values($validated['ack'] ?? []),
            dryRun: false,
            source: 'web'
        );

        return $this->respond($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function respond(array $data): JsonResponse
    {
        // Always 200: a blocked plan is a legitimate, renderable answer, not an HTTP error.
        return response()->json([
            'ok' => (bool) ($data['ok'] ?? false),
            'can_apply' => (bool) ($data['can_apply'] ?? false),
            'outcome' => $data['outcome'] ?? null,
            'message' => $data['message'] ?? '',
            'plan_hash' => $data['plan_hash'] ?? null,
            'acks' => array_column($data['warnings'] ?? [], 'key'),
            'html' => view('admin.whm.accounts.partials.mail-dns-body', ['data' => $data])->render(),
        ]);
    }
}
