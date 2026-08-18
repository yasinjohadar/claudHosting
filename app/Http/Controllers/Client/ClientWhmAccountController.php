<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\WhmAccount;
use App\Services\Client\ClientBillingService;
use App\Services\Whm\MailDns\WhmMailDnsSyncService;
use App\Services\Whm\WhmAccountService;
use App\Services\Whm\WhmApiService;
use App\Services\Whm\WhmEmailDeliverabilityService;
use App\Support\Dns\DnsValue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientWhmAccountController extends Controller
{
    public function __construct(
        protected WhmAccountService $accounts,
        protected WhmApiService $whmApi,
        protected WhmEmailDeliverabilityService $deliverability,
        protected ClientBillingService $billing,
        protected WhmMailDnsSyncService $mailDns
    ) {
        $this->middleware('auth');
    }

    /**
     * The account must belong to the signed-in client. There is no ambient guard on
     * the client route group — only `auth` — so every action must check this itself.
     */
    protected function authorizeOwnership(WhmAccount $account): void
    {
        abort_unless($this->accounts->userOwnsAccount(auth()->user(), $account), 403);
    }

    /** Ownership plus "the account still exists" — for the read-only detail pages. */
    protected function authorizeAccount(WhmAccount $account): void
    {
        $this->authorizeOwnership($account);
        abort_if($account->status === 'terminated', 404);
    }

    public function openCpanel(WhmAccount $account): RedirectResponse
    {
        $user = auth()->user();
        $this->authorizeOwnership($account);

        if ($account->status === 'suspended') {
            return redirect()
                ->route('client.services')
                ->withFragment('hosting')
                ->with('error', 'الحساب معلّق حاليًا. تواصل مع الدعم لإعادة تفعيله قبل فتح cPanel.');
        }

        $result = $this->accounts->portalCpanelLoginUrl($user, $account);

        if (! ($result['success'] ?? false)) {
            return redirect()
                ->route('client.services')
                ->withFragment('hosting')
                ->with('error', $result['message'] ?? 'تعذر فتح cPanel.');
        }

        return redirect()->away($result['url']);
    }

    public function updatePassword(Request $request, WhmAccount $account): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $this->authorizeOwnership($account);

        if (in_array($account->status, ['terminated', 'suspended'], true)) {
            $message = $account->status === 'suspended'
                ? 'لا يمكن تغيير كلمة المرور لحساب معلّق.'
                : 'لا يمكن تغيير كلمة المرور لحساب محذوف.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|max:64|confirmed',
        ], [
            'password.required' => 'أدخل كلمة المرور الجديدة.',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
        ]);

        $result = $this->accounts->updatePassword($account, $validated['password']);

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];

        if ($result['success']) {
            $session = $this->accounts->portalCpanelLoginUrl($user, $account);
            if ($session['success'] ?? false) {
                $payload['cpanel_url'] = $session['url'];
                $payload['message'] = ($result['message'] ?? 'تم تغيير كلمة المرور').' — جاري فتح cPanel…';
            }
        }

        if ($request->expectsJson()) {
            return response()->json($payload, $result['success'] ? 200 : 422);
        }

        if ($result['success'] && ! empty($payload['cpanel_url'])) {
            return redirect()->away($payload['cpanel_url']);
        }

        return redirect()
            ->route('client.services')
            ->withFragment('hosting')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function updateEmail(Request $request, WhmAccount $account): JsonResponse|RedirectResponse
    {
        $this->authorizeOwnership($account);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $result = $this->accounts->updateContactEmail($account, $validated['email']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'email' => $account->fresh()->display_email,
            ], $result['success'] ? 200 : 422);
        }

        return redirect()
            ->route('client.services')
            ->withFragment('hosting')
            ->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    /** Read-only account detail page: subscription, deliverability, resources. */
    public function show(WhmAccount $account): View
    {
        $this->authorizeAccount($account);

        $user = auth()->user();
        $configured = $this->whmApi->isConfigured();

        $summary = $this->accounts->getCachedSummary($account) ?? [];
        if ($configured && $account->status === 'active' && $summary === []) {
            $refresh = $this->accounts->refreshSummary($account, true);
            $summary = is_array($refresh['summary'] ?? null) ? $refresh['summary'] : [];
            $account->refresh();
        }

        $summarySyncedAt = null;
        $meta = is_array($account->metadata) ? $account->metadata : [];
        if (! empty($meta['summary_synced_at'])) {
            try {
                $summarySyncedAt = \Carbon\Carbon::parse($meta['summary_synced_at'])->format('Y-m-d H:i');
            } catch (\Throwable) {
                $summarySyncedAt = $meta['summary_synced_at'];
            }
        }

        $sslBadge = $configured && $account->status === 'active'
            ? $this->accounts->formatSslBadgeForDomain($account)
            : null;

        // Scope invoices to the customer profile the client can actually open, so every
        // rendered link is followable — WhmSubscriptionBillingService::resolveCustomer()
        // prefers $account->customer_id, which can diverge from the client's customer.
        $customerId = $this->billing->customerIdForUser($user);
        $invoices = $customerId === null
            ? collect()
            : $account->invoices()->where('customer_id', $customerId)->latest('date')->limit(5)->get();

        return view('client.pages.hosting.show', compact(
            'account', 'configured', 'summary', 'summarySyncedAt', 'sslBadge', 'invoices'
        ));
    }

    /** Lazy-loaded deliverability fragment. Same JSON contract as the admin endpoint. */
    public function emailDeliverability(Request $request, WhmAccount $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $data = $this->deliverability->forAccount($account, $request->boolean('fresh'));

        return response()->json([
            'success' => (bool) $data['success'],
            'message' => $data['message'] ?? '',
            'fetched_at_human' => $data['fetched_at_human'] ?? null,
            'html' => view('admin.whm.accounts.partials.email-deliverability-body', [
                'account' => $account,
                'data' => $data,
            ])->render(),
        ]);
    }

    /**
     * A client may only ever act on their OWN account's domain.
     *
     * The sync service accepts a domain and reads that domain's zone from WHM with root
     * credentials, then allow-lists record names against the domain it was GIVEN. That is
     * fine for an admin, but for a client an unvalidated domain would be a privilege
     * escalation: passing another tenant's domain would write into their zone. So the
     * request's domain must resolve to this account's domain or something inside it.
     */
    protected function resolveOwnDomain(WhmAccount $account, ?string $requested): string
    {
        $accountDomain = DnsValue::host($account->domain);
        $requested = DnsValue::host($requested ?? '');

        if ($requested === '' || $requested === $accountDomain) {
            return $accountDomain;
        }

        abort_unless(DnsValue::isWithin($requested, $accountDomain), 403);

        return $requested;
    }

    /** Diff the account's cPanel mail records against its Cloudflare zone. Writes nothing. */
    public function mailDnsPreview(Request $request, WhmAccount $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $validated = $request->validate(['domain' => 'nullable|string|max:253']);
        $domain = $this->resolveOwnDomain($account, $validated['domain'] ?? null);

        return $this->mailDnsResponse(
            $this->mailDns->preview($account, $domain, $request->boolean('fresh'))
        );
    }

    /** Install the account's mail records into Cloudflare. */
    public function mailDnsApply(Request $request, WhmAccount $account): JsonResponse
    {
        $this->authorizeAccount($account);

        $validated = $request->validate([
            'domain' => 'nullable|string|max:253',
            'plan_hash' => 'required|string|size:64',
            'ack' => 'nullable|array',
            'ack.*' => 'string|max:64',
        ]);

        $domain = $this->resolveOwnDomain($account, $validated['domain'] ?? null);

        return $this->mailDnsResponse($this->mailDns->apply(
            $account,
            $domain,
            $validated['plan_hash'],
            array_values($validated['ack'] ?? []),
            dryRun: false,
            source: 'client'
        ));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mailDnsResponse(array $data): JsonResponse
    {
        // Always 200: a blocked plan is a renderable answer, not a transport error.
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
