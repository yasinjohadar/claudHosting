<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WhatsAppApiException;
use App\Http\Controllers\Controller;
use App\Jobs\BroadcastWhatsAppMessageJob;
use App\Models\User;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageTemplate;
use App\Services\WhatsApp\BroadcastWhatsAppMessage;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class WhatsAppMessageController extends Controller
{
    public function __construct(
        private SendWhatsAppMessage $sendService,
        private BroadcastWhatsAppMessage $broadcastService,
        private WhatsAppSettingsService $settingsService
    ) {}

    /**
     * Display messages list
     */
    public function index(Request $request)
    {
        $messages = $this->paginateMessages($request);
        $stats = $this->messageStats();

        if ($request->ajax() || $request->boolean('ajax')) {
            return response()->json([
                'html' => view('admin.pages.whatsapp-messages.partials.list-results', compact('messages'))->render(),
                'total' => $messages->total(),
            ]);
        }

        return view('admin.pages.whatsapp-messages.index', compact('messages', 'stats'));
    }

    protected function paginateMessages(Request $request)
    {
        return $this->buildMessagesQuery($request)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();
    }

    protected function buildMessagesQuery(Request $request)
    {
        $query = WhatsAppMessage::with('contact');

        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery->where('wa_id', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * @return array<string, int>
     */
    protected function messageStats(): array
    {
        return [
            'total' => WhatsAppMessage::count(),
            'inbound' => WhatsAppMessage::inbound()->count(),
            'outbound' => WhatsAppMessage::outbound()->count(),
            'failed' => WhatsAppMessage::where('status', WhatsAppMessage::STATUS_FAILED)->count(),
            'today' => WhatsAppMessage::whereDate('created_at', today())->count(),
        ];
    }

    /**
     * Display message details
     */
    public function show(WhatsAppMessage $message)
    {
        $message->load('contact');

        return view('admin.pages.whatsapp-messages.show', compact('message'));
    }

    /**
     * Display send message form
     */
    public function create()
    {
        return view('admin.pages.whatsapp-messages.send', [
            'templates' => $this->availableTemplates(),
        ]);
    }

    /**
     * Active text templates for the picker.
     *
     * Returns an empty collection when the table is missing so the send page still works on an
     * install where the templates migration has not run yet.
     */
    protected function availableTemplates()
    {
        if (! Schema::hasTable('whatsapp_message_templates')) {
            return collect();
        }

        return WhatsAppMessageTemplate::active()
            ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'body', 'category']);
    }

    /**
     * Resolve the chosen template and render it for one recipient.
     *
     * Central to the fix for the old "قالب" option: it called sendTemplate(), which on
     * Evolution falls back to sending the template NAME as the message body — so picking a
     * template mailed the customer the string "welcome_msg". Templates are now rendered here
     * and sent as ordinary text.
     */
    protected function renderTemplateFor(int $templateId, ?User $recipient): ?string
    {
        $template = WhatsAppMessageTemplate::active()
            ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
            ->find($templateId);

        if ($template === null) {
            return null;
        }

        return $template->render([], $recipient !== null ? ['user' => $recipient] : []);
    }

    /**
     * Search students for individual messaging
     */
    public function searchStudents(Request $request)
    {
        try {
            $query = User::query();

            // Filter students only (if student role exists)
            $hasStudentRole = \Spatie\Permission\Models\Role::where('name', 'student')->exists();
            if ($hasStudentRole) {
                try {
                    $query->students();
                } catch (\Exception $e) {
                    Log::warning('Error in students scope: '.$e->getMessage());
                }
            }

            // Filter by phone
            $query->whereNotNull('phone')
                ->where('phone', '!=', '');

            // Search
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');

                    if (is_numeric($search)) {
                        $q->orWhere('id', $search);
                    }
                });
            }

            $students = $query->limit(50)->get()->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email ?? '',
                    'phone' => $student->phone ?? '',
                ];
            });

            return response()->json($students);
        } catch (\Exception $e) {
            Log::error('Error searching students: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send WhatsApp message
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:users,id',
            'to' => 'required_without:student_id|string|regex:/^\+[1-9]\d{1,14}$/',
            'type' => 'required|in:text,template',
            'message' => 'required_if:type,text|nullable|string|max:4096',
            'template_id' => 'required_if:type,template|nullable|integer',
        ], [
            'student_id.exists' => 'الطالب المحدد غير موجود',
            'to.required_without' => 'رقم الهاتف مطلوب إذا لم يتم اختيار طالب',
            'to.regex' => 'رقم الهاتف يجب أن يبدأ بـ + متبوعاً برمز الدولة',
            'type.required' => 'نوع الرسالة مطلوب',
            'message.required_if' => 'نص الرسالة مطلوب',
            'template_id.required_if' => 'اختر قالباً من القائمة',
        ]);

        try {
            $phone = $validated['to'] ?? null;
            $student = null;
            $messageText = $validated['message'] ?? '';

            // If student_id is provided, get student and use their phone
            if (! empty($validated['student_id'])) {
                $student = User::findOrFail($validated['student_id']);
                if (! $student->phone) {
                    return redirect()->back()
                        ->with('error', 'الطالب المحدد لا يملك رقم هاتف مسجل.')
                        ->withInput();
                }
                $phone = $student->phone;
            }

            $templatePayload = null;

            if ($validated['type'] === 'template') {
                $messageText = $this->renderTemplateFor((int) $validated['template_id'], $student);

                if ($messageText === null) {
                    return redirect()->back()
                        ->with('error', 'القالب المحدد غير موجود أو غير مفعّل.')
                        ->withInput();
                }

                if (trim($messageText) === '') {
                    return redirect()->back()
                        ->with('error', 'القالب المحدد ينتج نصاً فارغاً — راجعه من صفحة القوالب.')
                        ->withInput();
                }

                // Kept on the record so the log still shows which template produced this text.
                $templatePayload = ['template_id' => (int) $validated['template_id']];
            } elseif (! empty($messageText) && $student !== null) {
                $messageText = $this->broadcastService->replacePlaceholders($messageText, $student);
            }

            // Find or create contact
            $contact = WhatsAppContact::findOrCreateByWaId($phone);

            // Create message record
            $message = WhatsAppMessage::create([
                'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
                'contact_id' => $contact->id,
                // Always TYPE_TEXT now: Evolution has no real template channel, and recording
                // it as a template made retry() re-send the template name instead of the text.
                'type' => WhatsAppMessage::TYPE_TEXT,
                'body' => $messageText,
                'status' => WhatsAppMessage::STATUS_QUEUED, // Will be updated after sending
                'payload' => $templatePayload,
            ]);

            // Get provider settings and send message directly (synchronous)
            $settings = $this->settingsService->getSettings();
            $provider = $settings['whatsapp_provider'] ?? 'evolution';
            $config = $this->settingsService->getProviderConfig();

            // Create provider instance
            $providerInstance = WhatsAppProviderFactory::create($provider, $config);

            // One path for both types: a template is already rendered text by this point.
            $response = $providerInstance->sendText($phone, $messageText, false);

            // Update message with meta_message_id and status
            $message->update([
                'meta_message_id' => $response->metaMessageId,
                'status' => WhatsAppMessage::STATUS_SENT,
                'payload' => array_merge($message->payload ?? [], [
                    'response' => $response->rawResponse,
                    'sent_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::channel('whatsapp')->info('WhatsApp message sent successfully (direct)', [
                'message_id' => $message->id,
                'meta_message_id' => $response->metaMessageId,
                'to' => $phone,
            ]);

            return redirect()->route('admin.whatsapp-messages.show', $message)
                ->with('success', 'تم إرسال الرسالة بنجاح!');
        } catch (WhatsAppApiException $e) {
            // Update message with error if message was created
            if (isset($message) && $message->id) {
                $message->update([
                    'status' => WhatsAppMessage::STATUS_FAILED,
                    'error' => [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'details' => $e->getDetails(),
                    ],
                ]);
            }

            Log::channel('whatsapp')->error('Failed to send WhatsApp message', [
                'message_id' => $message->id ?? null,
                'error' => $e->getMessage(),
                'details' => $e->getDetails(),
            ]);

            return redirect()->back()
                ->with('error', 'فشل إرسال الرسالة: '.$e->getMessage())
                ->withInput();
        } catch (\Exception $e) {
            // Update message with error if message was created
            if (isset($message) && $message->id) {
                $message->update([
                    'status' => WhatsAppMessage::STATUS_FAILED,
                    'error' => [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ],
                ]);
            }

            Log::channel('whatsapp')->error('Exception sending WhatsApp message', [
                'message_id' => $message->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إرسال الرسالة: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get students count by criteria (AJAX)
     */
    public function getStudentsCount(Request $request)
    {
        return response()->json([
            'count' => $this->broadcastService->getBroadcastRecipients()->count(),
        ]);
    }

    /**
     * Send broadcast message
     */
    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'send_type' => 'required|in:individual,broadcast',
            'type' => 'required|in:text,template',
            'message' => 'required_if:type,text|nullable|string|max:4096',
            'template_id' => 'required_if:type,template|nullable|integer',
            // Individual field
            'to' => 'required_if:send_type,individual|nullable|string|regex:/^\+[1-9]\d{1,14}$/',
        ], [
            'send_type.required' => 'نوع الإرسال مطلوب',
            'type.required' => 'نوع الرسالة مطلوب',
            'message.required_if' => 'نص الرسالة مطلوب',
            'template_id.required_if' => 'اختر قالباً من القائمة',
            'to.required_if' => 'رقم الهاتف مطلوب للإرسال الفردي',
        ]);

        try {
            if ($validated['send_type'] === 'individual') {
                // Redirect to regular send method
                return $this->send($request);
            }

            // Broadcast logic - send to all users with valid phone numbers
            $students = $this->broadcastService->getBroadcastRecipients();

            if ($students->isEmpty()) {
                return redirect()->back()
                    ->with('error', 'لا يوجد مستخدمون لديهم أرقام هواتف صحيحة.')
                    ->withInput();
            }

            $template = null;
            if ($validated['type'] === 'template') {
                $template = WhatsAppMessageTemplate::active()
                    ->byType(WhatsAppMessageTemplate::TYPE_TEXT)
                    ->find((int) $validated['template_id']);

                if ($template === null) {
                    return redirect()->back()
                        ->with('error', 'القالب المحدد غير موجود أو غير مفعّل.')
                        ->withInput();
                }
            }

            // Create broadcast record
            $broadcast = WhatsAppBroadcast::create([
                'message_template' => $template !== null ? $template->body : ($validated['message'] ?? ''),
                'send_type' => $validated['type'],
                'course_id' => null,
                'group_id' => null,
                'total_recipients' => $students->count(),
                'status' => WhatsAppBroadcast::STATUS_PENDING,
                'created_by' => Auth::id(),
            ]);

            // Get delay settings
            $delaySettings = $this->settingsService->getDelaySettings();
            $baseDelay = $delaySettings['delay_between_messages'];

            // Dispatch jobs for each student with delay
            $index = 0;
            foreach ($students as $student) {
                // Rendered per recipient, never once for the batch — otherwise the first
                // recipient's name and city would go out to every number.
                $message = $template !== null
                    ? $template->render([], ['user' => $student])
                    : $this->broadcastService->replacePlaceholders($validated['message'] ?? '', $student);

                // Calculate delay for this message (with random variation if enabled)
                $delay = $this->settingsService->calculateDelay($baseDelay);

                BroadcastWhatsAppMessageJob::dispatch(
                    $broadcast,
                    $student,
                    $message,
                    $validated['type'],
                    $delay,
                    $index
                );

                $index++;
            }

            return redirect()->route('admin.whatsapp-messages.index')
                ->with('success', 'تم بدء إرسال '.$students->count().' رسالة جماعية.');
        } catch (\Exception $e) {
            Log::error('Error sending broadcast message: '.$e->getMessage());

            return redirect()->back()
                ->with('error', 'فشل إرسال الرسالة الجماعية: '.$e->getMessage())
                ->withInput();
        }
    }

    /**
     * Retry sending a failed or queued message (synchronous - without queue)
     */
    public function retry(WhatsAppMessage $message)
    {
        try {
            // Only allow retry for queued or failed messages
            if (! in_array($message->status, [WhatsAppMessage::STATUS_QUEUED, WhatsAppMessage::STATUS_FAILED])) {
                return redirect()->back()
                    ->with('error', 'لا يمكن إعادة إرسال هذه الرسالة. الحالة الحالية: '.$message->status);
            }

            // Load contact relationship
            $message->load('contact');
            if (! $message->contact) {
                return redirect()->back()
                    ->with('error', 'المستقبل غير موجود.');
            }

            $to = $message->contact->wa_id;

            // Get provider settings
            $settings = $this->settingsService->getSettings();
            $provider = $settings['whatsapp_provider'] ?? 'evolution';
            $config = $this->settingsService->getProviderConfig();

            // Create provider instance
            $providerInstance = WhatsAppProviderFactory::create($provider, $config);

            // Always resend the stored body. The old branch called sendTemplate() for template
            // messages, which on Evolution resends the template NAME — so retrying a template
            // message delivered "welcome_msg" to the customer.
            $response = $providerInstance->sendText($to, $message->body ?? '', false);

            // Update message with meta_message_id and status
            $message->update([
                'meta_message_id' => $response->metaMessageId,
                'status' => WhatsAppMessage::STATUS_SENT,
                'error' => null,
                'payload' => array_merge($message->payload ?? [], [
                    'response' => $response->rawResponse,
                    'sent_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::channel('whatsapp')->info('WhatsApp message sent successfully (retry)', [
                'message_id' => $message->id,
                'meta_message_id' => $response->metaMessageId,
                'to' => $to,
            ]);

            return redirect()->back()
                ->with('success', 'تم إرسال الرسالة بنجاح!');
        } catch (WhatsAppApiException $e) {
            // Update message with error
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'details' => $e->getDetails(),
                    'retried_at' => now()->toIso8601String(),
                ],
            ]);

            Log::channel('whatsapp')->error('Failed to send WhatsApp message (retry)', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'details' => $e->getDetails(),
            ]);

            return redirect()->back()
                ->with('error', 'فشل إرسال الرسالة: '.$e->getMessage());
        } catch (\Exception $e) {
            // Update message with error
            $message->update([
                'status' => WhatsAppMessage::STATUS_FAILED,
                'error' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'retried_at' => now()->toIso8601String(),
                ],
            ]);

            Log::channel('whatsapp')->error('Exception sending WhatsApp message (retry)', [
                'message_id' => $message->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إرسال الرسالة: '.$e->getMessage());
        }
    }
}
