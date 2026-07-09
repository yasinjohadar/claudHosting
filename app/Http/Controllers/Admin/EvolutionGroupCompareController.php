<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\BroadcastWhatsAppCustomerJob;
use App\Models\Customer;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Services\WhatsApp\BroadcastWhatsAppMessage;
use App\Services\WhatsApp\Evolution\EvolutionApiException;
use App\Services\WhatsApp\Evolution\EvolutionGroupCompareService;
use App\Services\WhatsApp\Evolution\EvolutionService;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\Support\WhatsAppRecipientNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class EvolutionGroupCompareController extends Controller
{
    public function __construct(
        private EvolutionGroupCompareService $compareService,
        private EvolutionService $evolutionService,
        private BroadcastWhatsAppMessage $broadcastService,
        private WhatsAppSettingsService $settingsService,
    ) {}

    public function index(Request $request): View
    {
        $delaySettings = $this->settingsService->getDelaySettings();
        $queuePendingCount = (int) \Illuminate\Support\Facades\DB::table('jobs')->count();

        $whatsappGroups = [];
        $waError = null;
        try {
            $whatsappGroups = $this->compareService->listWhatsAppGroups(false);
        } catch (\Throwable $e) {
            $waError = EvolutionApiException::resolveUserMessage($e);
        }

        $filters = $this->filtersFromRequest($request);
        $result = null;
        $waGroupInfo = null;
        $labels = ['course' => null, 'platform_group' => null];
        $compareError = null;

        if ($filters['whatsapp_jid'] !== '') {
            try {
                $customers = $this->compareService->getPlatformCustomers(
                    $filters['active_only'],
                    false,
                );

                $wa = $this->compareService->loadWhatsAppGroup($filters['whatsapp_jid']);
                $waGroupInfo = $wa['group_info'];
                $result = $this->compareService->compareCustomers($customers, $wa['phone_index'], $wa['members']);
            } catch (\Throwable $e) {
                $compareError = EvolutionApiException::resolveUserMessage($e);
            }
        }

        return view('admin.pages.evolution-api.groups.compare', [
            'courses' => collect(),
            'platformGroups' => collect(),
            'platformMode' => 'customers',
            'whatsappGroups' => $whatsappGroups,
            'waError' => $waError,
            'filters' => $filters,
            'result' => $result,
            'waGroupInfo' => $waGroupInfo,
            'labels' => $labels,
            'compareError' => $compareError,
            'delaySettings' => $delaySettings,
            'queuePendingCount' => $queuePendingCount,
        ]);
    }

    public function campaigns(Request $request): View
    {
        $campaigns = WhatsAppBroadcast::compareMissing()
            ->with(['creator', 'recipients.customer'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.pages.evolution-api.groups.compare-campaigns', compact('campaigns'));
    }

    public function showCampaign(WhatsAppBroadcast $broadcast): View
    {
        abort_unless($broadcast->isCompareMissing(), 404);

        $broadcast->load(['creator', 'recipients.customer']);

        $delaySettings = $this->settingsService->getDelaySettings();

        return view('admin.pages.evolution-api.groups.compare-campaign-show', compact('broadcast', 'delaySettings'));
    }

    public function export(Request $request): Response
    {
        $filters = $this->filtersFromRequest($request);
        abort_if($filters['whatsapp_jid'] === '', 422, 'يجب اختيار مجموعة واتساب.');

        $customers = $this->compareService->getPlatformCustomers($filters['active_only'], false);
        $wa = $this->compareService->loadWhatsAppGroup($filters['whatsapp_jid']);
        $result = $this->compareService->compareCustomers($customers, $wa['phone_index'], $wa['members']);

        $view = $filters['tab'] ?? 'missing';
        $rows = match ($view) {
            'matched' => $result['matched'],
            'wa_only' => $result['wa_only'],
            'no_phone' => $result['no_phone'],
            default => $result['missing'],
        };

        $filename = 'whatsapp-compare-'.$view.'-'.now()->format('Y-m-d-His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($rows, $view) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($view === 'wa_only') {
                fputcsv($out, ['الرقم', 'JID', 'الدور']);
                foreach ($rows as $row) {
                    fputcsv($out, [$row['phone'], $row['phone_jid'], $row['role'] ?? 'member']);
                }
            } else {
                fputcsv($out, ['ID', 'الاسم', 'البريد', 'الهاتف']);
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row['id'] ?? '',
                        $row['name'] ?? '',
                        $row['email'] ?? '',
                        $row['phone_display'] ?? $row['phone'] ?? '',
                    ]);
                }
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function messageMissing(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:customers,id'],
            'text' => ['required', 'string', 'max:5000'],
            'whatsapp_jid' => ['nullable', 'string', 'max:255'],
            'whatsapp_group_name' => ['nullable', 'string', 'max:255'],
            'active_only' => ['nullable'],
        ]);

        $customers = Customer::whereIn('id', $validated['user_ids'])->orderBy('firstname')->get();
        if ($customers->isEmpty()) {
            return back()->with('error', 'لم يتم اختيار عملاء صالحين.');
        }

        $broadcast = WhatsAppBroadcast::create([
            'message_template' => $validated['text'],
            'send_type' => WhatsAppBroadcast::TYPE_TEXT,
            'campaign_type' => WhatsAppBroadcast::CAMPAIGN_COMPARE_MISSING,
            'whatsapp_group_jid' => $validated['whatsapp_jid'] ?? null,
            'whatsapp_group_name' => $validated['whatsapp_group_name'] ?? null,
            'meta' => [
                'active_only' => $request->boolean('active_only', true),
                'source' => 'evolution_compare',
                'entity' => 'customer',
            ],
            'total_recipients' => $customers->count(),
            'status' => WhatsAppBroadcast::STATUS_PROCESSING,
            'sent_count' => 0,
            'failed_count' => 0,
            'created_by' => Auth::id(),
        ]);

        foreach ($customers as $customer) {
            WhatsAppBroadcastRecipient::create([
                'broadcast_id' => $broadcast->id,
                'customer_id' => $customer->id,
                'status' => WhatsAppBroadcastRecipient::STATUS_PENDING,
            ]);
        }

        $firstCustomer = $customers->first();
        $firstMessage = $this->broadcastService->replaceCustomerPlaceholders($validated['text'], $firstCustomer);

        $digits = $this->compareService->customerPhoneDigits($firstCustomer);
        $firstSendError = null;

        if ($digits === null) {
            $firstSendError = new \RuntimeException('رقم العميل الأول غير صالح.');
        } else {
            try {
                $settings = $this->settingsService->getSettings();
                $provider = $settings['whatsapp_provider'] ?? 'evolution';
                $config = $this->settingsService->getProviderConfig();
                $providerInstance = WhatsAppProviderFactory::create($provider, $config);
                $to = WhatsAppRecipientNormalizer::normalize($provider, $digits);
                $providerInstance->sendText($to, $firstMessage, false);
            } catch (\Throwable $e) {
                $firstSendError = $e;
            }
        }

        if ($firstSendError) {
            WhatsAppBroadcastRecipient::where('broadcast_id', $broadcast->id)
                ->where('customer_id', $firstCustomer->id)
                ->update([
                    'status' => WhatsAppBroadcastRecipient::STATUS_FAILED,
                    'error_message' => $firstSendError->getMessage(),
                ]);
            $broadcast->increment('failed_count');
            Log::channel('whatsapp')->warning('Compare campaign first send failed', [
                'broadcast_id' => $broadcast->id,
                'customer_id' => $firstCustomer->id,
                'error' => $firstSendError->getMessage(),
            ]);
        } else {
            WhatsAppBroadcastRecipient::where('broadcast_id', $broadcast->id)
                ->where('customer_id', $firstCustomer->id)
                ->update([
                    'status' => WhatsAppBroadcastRecipient::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            $broadcast->increment('sent_count');
        }

        $delaySettings = $this->settingsService->getDelaySettings();
        $baseDelay = $delaySettings['delay_between_messages'];
        $cumulativeDelay = 0;
        $index = 1;

        foreach ($customers->slice(1) as $customer) {
            $message = $this->broadcastService->replaceCustomerPlaceholders($validated['text'], $customer);
            $delay = $this->settingsService->calculateDelay($baseDelay);
            $cumulativeDelay += $delay;

            BroadcastWhatsAppCustomerJob::dispatch(
                $broadcast,
                $customer,
                $message,
                WhatsAppBroadcast::TYPE_TEXT,
                $cumulativeDelay,
                $index
            );
            $index++;
        }

        if ($customers->count() === 1) {
            $broadcast->refresh();
            $broadcast->update([
                'status' => $broadcast->sent_count > 0
                    ? WhatsAppBroadcast::STATUS_COMPLETED
                    : WhatsAppBroadcast::STATUS_FAILED,
            ]);
        }

        $delayInfo = $delaySettings['delay_between_messages'].' ث';
        if ($delaySettings['random_delay_enabled']) {
            $delayInfo .= ' (+ عشوائي '.$delaySettings['min_delay'].'–'.$delaySettings['max_delay'].' ث)';
        }

        return redirect()
            ->route('admin.evolution-api.groups.compare.campaigns.show', $broadcast)
            ->with('success', 'تم بدء إرسال '.$customers->count().' رسالة عبر الطابور. الفاصل بين الرسائل: '.$delayInfo.'. تابع التقرير في هذه الصفحة.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'whatsapp_jid' => (string) $request->input('whatsapp_jid', ''),
            'active_only' => $request->boolean('active_only', true),
            'tab' => $request->input('tab', 'missing'),
        ];
    }
}
