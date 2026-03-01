<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppContact;
use App\Services\WhatsApp\SendWhatsAppMessage;
use App\Services\WhatsApp\BroadcastWhatsAppMessage;
use App\Jobs\BroadcastWhatsAppMessageJob;
use App\Jobs\SendWhatsAppMessageJob;
use App\Services\WhatsApp\WhatsAppProviderFactory;
use App\Services\WhatsApp\WhatsAppSettingsService;
use App\Exceptions\WhatsAppApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        $query = WhatsAppMessage::with('contact');

        // Filter by direction
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search
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

        $messages = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.pages.whatsapp-messages.index', compact('messages'));
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
        return view('admin.pages.whatsapp-messages.send');
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
                    Log::warning('Error in students scope: ' . $e->getMessage());
                }
            }

            // Filter by phone
            $query->whereNotNull('phone')
                  ->where('phone', '!=', '');

            // Search
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('phone', 'like', '%' . $search . '%');
                      
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
            Log::error('Error searching students: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
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
            'template_name' => 'required_if:type,template|nullable|string|max:255',
            'language' => 'required_if:type,template|nullable|string|max:10',
        ], [
            'student_id.exists' => 'الطالب المحدد غير موجود',
            'to.required_without' => 'رقم الهاتف مطلوب إذا لم يتم اختيار طالب',
            'to.regex' => 'رقم الهاتف يجب أن يبدأ بـ + متبوعاً برمز الدولة',
            'type.required' => 'نوع الرسالة مطلوب',
            'message.required_if' => 'نص الرسالة مطلوب',
            'template_name.required_if' => 'اسم القالب مطلوب',
            'language.required_if' => 'اللغة مطلوبة',
        ]);

        try {
            $phone = $validated['to'] ?? null;
            $student = null;
            $messageText = $validated['message'] ?? '';

            // If student_id is provided, get student and use their phone
            if (!empty($validated['student_id'])) {
                $student = User::findOrFail($validated['student_id']);
                if (!$student->phone) {
                    return redirect()->back()
                                   ->with('error', 'الطالب المحدد لا يملك رقم هاتف مسجل.')
                                   ->withInput();
                }
                $phone = $student->phone;

                // Replace placeholders if message is text type
                if ($validated['type'] === 'text' && !empty($messageText)) {
                    $messageText = $this->broadcastService->replacePlaceholders(
                        $messageText,
                        $student,
                        null,
                        null
                    );
                }
            }

            // Find or create contact
            $contact = WhatsAppContact::findOrCreateByWaId($phone);

            // Create message record
            $message = WhatsAppMessage::create([
                'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
                'contact_id' => $contact->id,
                'type' => $validated['type'] === 'template' ? WhatsAppMessage::TYPE_TEMPLATE : WhatsAppMessage::TYPE_TEXT,
                'body' => $validated['type'] === 'template' ? $validated['template_name'] : $messageText,
                'status' => WhatsAppMessage::STATUS_QUEUED, // Will be updated after sending
                'payload' => $validated['type'] === 'template' ? [
                    'template_name' => $validated['template_name'],
                    'language' => $validated['language'] ?? 'ar',
                    'components' => [],
                ] : null,
            ]);

            // Get provider settings and send message directly (synchronous)
            $settings = $this->settingsService->getSettings();
            $provider = $settings['whatsapp_provider'] ?? 'meta';
            $config = $this->settingsService->getProviderConfig();

            // Create provider instance
            $providerInstance = WhatsAppProviderFactory::create($provider, $config);

            // Send message directly
            if ($validated['type'] === 'template') {
                $response = $providerInstance->sendTemplate(
                    $phone,
                    $validated['template_name'],
                    $validated['language'] ?? 'ar',
                    []
                );
            } else {
                $response = $providerInstance->sendText(
                    $phone,
                    $messageText,
                    false
                );
            }

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
                           ->with('error', 'فشل إرسال الرسالة: ' . $e->getMessage())
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
                           ->with('error', 'حدث خطأ أثناء إرسال الرسالة: ' . $e->getMessage())
                           ->withInput();
        }
    }

    /**
     * Get students count by criteria (AJAX)
     */
    public function getStudentsCount(Request $request)
    {
        $students = $this->broadcastService->getStudentsByCriteria(
            null, // course_id - not used in this project
            null  // group_id - not used in this project
        );

        return response()->json([
            'count' => $students->count(),
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
            'template_name' => 'required_if:type,template|nullable|string|max:255',
            'language' => 'required_if:type,template|nullable|string|max:10',
            // Individual field
            'to' => 'required_if:send_type,individual|nullable|string|regex:/^\+[1-9]\d{1,14}$/',
        ], [
            'send_type.required' => 'نوع الإرسال مطلوب',
            'type.required' => 'نوع الرسالة مطلوب',
            'message.required_if' => 'نص الرسالة مطلوب',
            'to.required_if' => 'رقم الهاتف مطلوب للإرسال الفردي',
        ]);

        try {
            if ($validated['send_type'] === 'individual') {
                // Redirect to regular send method
                return $this->send($request);
            }

            // Broadcast logic - send to all users with valid phone numbers
            $students = $this->broadcastService->getStudentsByCriteria(
                null, // course_id - not used
                null  // group_id - not used
            );

            if ($students->isEmpty()) {
                return redirect()->back()
                    ->with('error', 'لا يوجد مستخدمون لديهم أرقام هواتف صحيحة.')
                    ->withInput();
            }

            // Create broadcast record
            $broadcast = WhatsAppBroadcast::create([
                'message_template' => $validated['message'] ?? $validated['template_name'] ?? '',
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
                $message = $this->broadcastService->replacePlaceholders(
                    $validated['message'] ?? '',
                    $student,
                    null, // course - not used
                    null  // group - not used
                );

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
                ->with('success', 'تم بدء إرسال ' . $students->count() . ' رسالة جماعية.');
        } catch (\Exception $e) {
            Log::error('Error sending broadcast message: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'فشل إرسال الرسالة الجماعية: ' . $e->getMessage())
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
            if (!in_array($message->status, [WhatsAppMessage::STATUS_QUEUED, WhatsAppMessage::STATUS_FAILED])) {
                return redirect()->back()
                    ->with('error', 'لا يمكن إعادة إرسال هذه الرسالة. الحالة الحالية: ' . $message->status);
            }

            // Load contact relationship
            $message->load('contact');
            if (!$message->contact) {
                return redirect()->back()
                    ->with('error', 'المستقبل غير موجود.');
            }

            $to = $message->contact->wa_id;

            // Get provider settings
            $settings = $this->settingsService->getSettings();
            $provider = $settings['whatsapp_provider'] ?? 'meta';
            $config = $this->settingsService->getProviderConfig();

            // Create provider instance
            $providerInstance = WhatsAppProviderFactory::create($provider, $config);

            // Send message directly (synchronous)
            if ($message->type === WhatsAppMessage::TYPE_TEMPLATE) {
                $payload = $message->payload ?? [];
                $response = $providerInstance->sendTemplate(
                    $to,
                    $payload['template_name'] ?? $message->body,
                    $payload['language'] ?? 'ar',
                    $payload['components'] ?? []
                );
            } else {
                $response = $providerInstance->sendText(
                    $to,
                    $message->body ?? '',
                    false
                );
            }

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
                ->with('error', 'فشل إرسال الرسالة: ' . $e->getMessage());
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
                ->with('error', 'حدث خطأ أثناء إرسال الرسالة: ' . $e->getMessage());
        }
    }
}
