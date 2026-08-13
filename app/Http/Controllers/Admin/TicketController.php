<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketNote;
use App\Models\TicketReply;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Ticket::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tid', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        $tickets = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();

        $stats = [
            'total' => Ticket::count(),
            'open' => Ticket::query()->where('status', 'Open')->count(),
            'awaiting' => Ticket::query()->whereIn('status', ['Customer-Reply', 'In Progress'])->count(),
            'closed' => Ticket::query()->where('status', 'Closed')->count(),
        ];

        return view('admin.tickets.index', compact('tickets', 'stats'));
    }

    public function create()
    {
        $customers = Customer::orderBy('fullname')->get();

        return view('admin.tickets.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'deptid' => 'required|integer',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'urgency' => 'required|in:Low,Medium,High,Urgent',
            'department' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            $ticketNumber = Ticket::generateTicketNumber();

            Ticket::create([
                'customer_id' => $customer->id,
                'whmcs_id' => null,
                'whmcs_client_id' => $customer->whmcs_id,
                'tid' => $ticketNumber,
                'deptid' => $request->deptid,
                'userid' => $customer->id,
                'name' => $customer->fullname,
                'email' => $customer->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'Open',
                'priority' => $request->priority,
                'urgency' => $request->urgency,
                'department' => $request->department,
                'date' => Carbon::now(),
                'lastmodified' => Carbon::now(),
                'synced_at' => null,
            ]);

            DB::commit();

            return redirect()->route('admin.tickets.index')
                ->with('success', 'تم إنشاء التذكرة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء التذكرة: '.$e->getMessage())
                ->withInput();
        }
    }

    public function show($id)
    {
        $ticket = Ticket::with('customer', 'replies', 'notes')->findOrFail($id);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);
        $customers = Customer::orderBy('fullname')->get();

        return view('admin.tickets.edit', compact('ticket', 'customers'));
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'deptid' => 'required|integer',
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'urgency' => 'required|in:Low,Medium,High,Urgent',
            'department' => 'required|string|max:100',
            'status' => 'required|in:Open,Answered,Customer-Reply,In Progress,Closed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $ticket->update([
            'deptid' => $request->deptid,
            'subject' => $request->subject,
            'priority' => $request->priority,
            'urgency' => $request->urgency,
            'department' => $request->department,
            'status' => $request->status,
            'lastmodified' => Carbon::now(),
        ]);

        return redirect()->route('admin.tickets.index')
            ->with('success', 'تم تحديث بيانات التذكرة بنجاح');
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);

        DB::beginTransaction();

        try {
            $ticket->replies()->delete();
            $ticket->notes()->delete();
            $ticket->delete();

            DB::commit();

            return redirect()->route('admin.tickets.index')
                ->with('success', 'تم حذف التذكرة بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف التذكرة: '.$e->getMessage());
        }
    }

    public function reply(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        TicketReply::create([
            'ticket_id' => $ticket->id,
            'whmcs_id' => null,
            'whmcs_ticket_id' => null,
            'type' => 'admin',
            'admin' => auth()->user()->name,
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'date' => Carbon::now(),
            'message' => $request->message,
        ]);

        $ticket->update([
            'status' => 'Answered',
            'lastreply' => Carbon::now(),
            'lastmodified' => Carbon::now(),
        ]);

        return redirect()->route('admin.tickets.show', $id)
            ->with('success', 'تم إضافة الرد بنجاح');
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => 'Closed',
            'lastmodified' => Carbon::now(),
        ]);

        return redirect()->route('admin.tickets.show', $id)
            ->with('success', 'تم إغلاق التذكرة بنجاح');
    }

    public function addNote(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $validator = Validator::make($request->all(), ['message' => 'required|string|max:5000']);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        TicketNote::create([
            'ticket_id' => $ticket->id,
            'admin_id' => auth()->id(),
            'admin_name' => auth()->user()->name ?? '',
            'note' => $request->message,
            'date' => Carbon::now(),
        ]);

        return redirect()->route('admin.tickets.show', $id)->with('success', 'تم إضافة الملاحظة بنجاح');
    }

    public function reopen($id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'status' => 'Open',
            'lastmodified' => Carbon::now(),
        ]);

        return redirect()->route('admin.tickets.show', $id)
            ->with('success', 'تم إعادة فتح التذكرة بنجاح');
    }
}
