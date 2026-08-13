<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Services\Client\ClientBillingService;
use App\Support\Html;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ClientTicketController extends Controller
{
    public function __construct(
        protected ClientBillingService $billing
    ) {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $user = auth()->user();
        $customer = $this->billing->ensureCustomerProfile($user);
        $hasCustomerProfile = $customer !== null;
        $customerId = $customer?->id;

        $query = Ticket::query()->with('customer');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tid', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $tickets = $query->orderByDesc('date')->orderByDesc('id')->paginate(12)->withQueryString();

        $stats = [
            'total' => $customerId ? Ticket::query()->where('customer_id', $customerId)->count() : 0,
            'open' => $customerId ? Ticket::query()->where('customer_id', $customerId)->where('status', 'Open')->count() : 0,
            'waiting' => $customerId ? Ticket::query()->where('customer_id', $customerId)->where('status', 'Answered')->count() : 0,
            'closed' => $customerId ? Ticket::query()->where('customer_id', $customerId)->where('status', 'Closed')->count() : 0,
        ];

        return view('client.pages.tickets.index', compact('user', 'tickets', 'hasCustomerProfile', 'stats'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $hasCustomerProfile = $this->billing->ensureCustomerProfile($user) !== null;

        return view('client.pages.tickets.create', compact('user', 'hasCustomerProfile'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $customer = $this->billing->ensureCustomerProfile($user);

        if (! $customer) {
            return redirect()->route('client.tickets.index')
                ->with('error', 'تعذر تجهيز ملف العميل. حاول مرة أخرى أو تواصل مع الدعم.');
        }

        $validated = $request->validate([
            'deptid' => 'required|integer|in:1,2,3,4',
            'subject' => 'required|string|max:255',
            'message' => [
                'required',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (Html::isEmpty(is_string($value) ? $value : null)) {
                        $fail('يرجى كتابة رسالة التذكرة.');
                    }
                },
            ],
            'priority' => 'required|in:Low,Medium,High,Urgent',
        ]);

        $departments = [
            1 => 'المبيعات',
            2 => 'الدعم الفني',
            3 => 'الفوترة',
            4 => 'أخرى',
        ];

        $ticket = Ticket::create([
            'customer_id' => $customer->id,
            'whmcs_id' => null,
            'whmcs_client_id' => $customer->whmcs_id,
            'tid' => Ticket::generateTicketNumber(),
            'deptid' => (int) $validated['deptid'],
            'userid' => $customer->id,
            'name' => $customer->fullname ?: $user->name,
            'email' => $customer->email ?: $user->email,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'Open',
            'priority' => $validated['priority'],
            'urgency' => $validated['priority'],
            'department' => $departments[(int) $validated['deptid']] ?? 'الدعم الفني',
            'date' => Carbon::now(),
            'lastmodified' => Carbon::now(),
            'synced_at' => null,
        ]);

        return redirect()->route('client.tickets.show', $ticket)
            ->with('success', 'تم إنشاء التذكرة بنجاح. سيظهر نفس الطلب في لوحة الإدارة.');
    }

    public function show(Ticket $ticket): View
    {
        $user = auth()->user();
        $this->authorizeTicket($user, $ticket);

        $ticket->load(['replies' => fn ($q) => $q->orderBy('date')->orderBy('id')]);

        return view('client.pages.tickets.show', [
            'user' => $user,
            'ticket' => $ticket,
            'hasCustomerProfile' => true,
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $user = auth()->user();
        $this->authorizeTicket($user, $ticket);

        if ($ticket->status === 'Closed') {
            return redirect()->back()->with('error', 'لا يمكن الرد على تذكرة مغلقة.');
        }

        $validated = $request->validate([
            'message' => [
                'required',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (Html::isEmpty(is_string($value) ? $value : null)) {
                        $fail('يرجى كتابة الرد.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($ticket, $user, $validated) {
            TicketReply::create([
                'ticket_id' => $ticket->id,
                'whmcs_id' => null,
                'whmcs_ticket_id' => null,
                'userid' => $ticket->customer_id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => 'client',
                'date' => Carbon::now(),
                'message' => $validated['message'],
                'admin' => null,
            ]);

            $ticket->update([
                'status' => 'Customer-Reply',
                'lastreply' => Carbon::now(),
                'lastmodified' => Carbon::now(),
            ]);
        });

        return redirect()->route('client.tickets.show', $ticket)
            ->with('success', 'تم إرسال ردك بنجاح.');
    }

    protected function authorizeTicket($user, Ticket $ticket): void
    {
        $customer = $this->billing->ensureCustomerProfile($user);
        abort_unless(
            $customer !== null && (int) $ticket->customer_id === (int) $customer->id,
            403,
            'غير مصرح لك بعرض هذه التذكرة.'
        );
    }
}
