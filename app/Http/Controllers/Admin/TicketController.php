<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\TicketNote;
use App\Models\Customer;
use App\Services\WhmcsApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TicketController extends Controller
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
        $this->middleware('auth');
    }

    /**
     * عرض قائمة التذاكر
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = Ticket::with('customer');
        
        // تصفية حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // تصفية حسب الأولوية
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        
        // تصفية حسب القسم
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }
        
        // تصفية حسب التاريخ من
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        
        // تصفية حسب التاريخ إلى
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        
        $tickets = $query->orderBy('date', 'desc')->paginate(10);
        $customers = Customer::all();
        
        return view('admin.tickets.index', compact('tickets', 'customers'));
    }

    /**
     * عرض نموذج إضافة تذكرة جديدة
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $customers = Customer::all();
        return view('admin.tickets.create', compact('customers'));
    }

    /**
     * حفظ تذكرة جديدة
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
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
            
            // إنشاء التذكرة في النظام المحلي
            $ticket = Ticket::create([
                'whmcs_id' => null, // سيتم تحديثه لاحقًا بعد إنشائه في WHMCS
                'whmcs_client_id' => $customer->whmcs_id,
                'tid' => $this->generateTicketNumber(),
                'deptid' => $request->deptid,
                'userid' => $customer->whmcs_id,
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
                'synced_at' => null, // لم تتم المزامنة بعد
            ]);
            
            // إنشاء التذكرة في WHMCS
            $whmcsTicket = $this->whmcsApiService->openTicket([
                'deptid' => $request->deptid,
                'subject' => $request->subject,
                'message' => $request->message,
                'priority' => $request->priority,
                'clientid' => $customer->whmcs_id,
            ]);
            
            if ($whmcsTicket && isset($whmcsTicket['id'])) {
                // تحديث التذكرة المحلية بمعرف WHMCS
                $ticket->whmcs_id = $whmcsTicket['id'];
                $ticket->tid = $whmcsTicket['tid'];
                $ticket->synced_at = Carbon::now();
                $ticket->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.tickets.index')
                ->with('success', 'تم إنشاء التذكرة بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء التذكرة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل التذكرة
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $ticket = Ticket::with('customer', 'replies', 'notes')->findOrFail($id);
        return view('admin.tickets.show', compact('ticket'));
    }

    /**
     * عرض نموذج تعديل التذكرة
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $ticket = Ticket::findOrFail($id);
        $customers = Customer::all();
        return view('admin.tickets.edit', compact('ticket', 'customers'));
    }

    /**
     * تحديث بيانات التذكرة
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
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

        DB::beginTransaction();
        
        try {
            // تحديث التذكرة في النظام المحلي
            $ticket->update([
                'deptid' => $request->deptid,
                'subject' => $request->subject,
                'priority' => $request->priority,
                'urgency' => $request->urgency,
                'department' => $request->department,
                'status' => $request->status,
                'lastmodified' => Carbon::now(),
            ]);
            
            // تحديث التذكرة في WHMCS إذا كان لديها معرف
            if ($ticket->whmcs_id) {
                $this->whmcsApiService->updateTicket($ticket->whmcs_id, [
                    'deptid' => $request->deptid,
                    'subject' => $request->subject,
                    'priority' => $request->priority,
                    'status' => $request->status,
                ]);
                
                $ticket->synced_at = Carbon::now();
                $ticket->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.tickets.index')
                ->with('success', 'تم تحديث بيانات التذكرة بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء تحديث بيانات التذكرة: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * حذف التذكرة
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // حذف التذكرة من WHMCS إذا كان لديها معرف
            if ($ticket->whmcs_id) {
                $this->whmcsApiService->deleteTicket($ticket->whmcs_id);
            }
            
            // حذف الردود والملاحظات والتذكرة من النظام المحلي
            $ticket->replies()->delete();
            $ticket->notes()->delete();
            $ticket->delete();
            
            DB::commit();
            
            return redirect()->route('admin.tickets.index')
                ->with('success', 'تم حذف التذكرة بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء حذف التذكرة: ' . $e->getMessage());
        }
    }
    
    /**
     * الرد على التذكرة
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
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

        DB::beginTransaction();
        
        try {
            // إنشاء الرد في النظام المحلي
            TicketReply::create([
                'ticket_id' => $ticket->id,
                'admin' => auth()->user()->name,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'date' => Carbon::now(),
                'message' => $request->message,
            ]);
            
            // تحديث حالة التذكرة
            $ticket->update([
                'status' => 'Answered',
                'lastreply' => Carbon::now(),
                'lastmodified' => Carbon::now(),
            ]);
            
            // إضافة الرد في WHMCS إذا كان للتذكرة معرف
            if ($ticket->whmcs_id) {
                $this->whmcsApiService->addTicketReply((int) $ticket->whmcs_id, $request->message);
                
                $ticket->synced_at = Carbon::now();
                $ticket->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'تم إضافة الرد بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إضافة الرد: ' . $e->getMessage());
        }
    }
    
    /**
     * إغلاق التذكرة
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // تحديث حالة التذكرة في النظام المحلي
            $ticket->update([
                'status' => 'Closed',
                'lastmodified' => Carbon::now(),
            ]);
            
            // إغلاق التذكرة في WHMCS إذا كان لها معرف
            if ($ticket->whmcs_id) {
                $this->whmcsApiService->closeTicket($ticket->whmcs_id);
                
                $ticket->synced_at = Carbon::now();
                $ticket->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'تم إغلاق التذكرة بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إغلاق التذكرة: ' . $e->getMessage());
        }
    }

    /**
     * إضافة ملاحظة داخلية على التذكرة
     */
    public function addNote(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $validator = Validator::make($request->all(), ['message' => 'required|string|max:5000']);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        try {
            TicketNote::create([
                'ticket_id' => $ticket->id,
                'admin_id' => auth()->id(),
                'admin_name' => auth()->user()->name ?? '',
                'note' => $request->message,
                'date' => Carbon::now(),
            ]);
            if ($ticket->whmcs_id) {
                $this->whmcsApiService->addTicketNote((int) $ticket->whmcs_id, $request->message, true);
                $ticket->update(['synced_at' => Carbon::now()]);
            }
            return redirect()->route('admin.tickets.show', $id)->with('success', 'تم إضافة الملاحظة بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
    
    /**
     * إعادة فتح التذكرة
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reopen($id)
    {
        $ticket = Ticket::findOrFail($id);
        
        DB::beginTransaction();
        
        try {
            // تحديث حالة التذكرة في النظام المحلي
            $ticket->update([
                'status' => 'Open',
                'lastmodified' => Carbon::now(),
            ]);
            
            // إعادة فتح التذكرة في WHMCS إذا كان لها معرف
            if ($ticket->whmcs_id) {
                $this->whmcsApiService->openTicket((int) $ticket->whmcs_id);
                
                $ticket->synced_at = Carbon::now();
                $ticket->save();
            }
            
            DB::commit();
            
            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'تم إعادة فتح التذكرة بنجاح');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إعادة فتح التذكرة: ' . $e->getMessage());
        }
    }
    
    /**
     * مزامنة التذكرة مع WHMCS
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sync($id)
    {
        $ticket = Ticket::findOrFail($id);
        
        try {
            // مزامنة التذكرة مع WHMCS
            $this->whmcsApiService->syncTicket($ticket);
            
            return redirect()->route('admin.tickets.show', $id)
                ->with('success', 'تمت مزامنة التذكرة مع WHMCS بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة التذكرة: ' . $e->getMessage());
        }
    }
    
    /**
     * مزامنة جميع التذاكر مع WHMCS
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function syncAll()
    {
        try {
            $count = $this->whmcsApiService->syncTickets();
            
            return redirect()->route('admin.tickets.index')
                ->with('success', 'تمت مزامنة ' . $count . ' تذكرة مع WHMCS بنجاح');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء مزامنة التذاكر: ' . $e->getMessage());
        }
    }
    
    /**
     * توليد رقم تذكرة فريد
     *
     * @return string
     */
    private function generateTicketNumber()
    {
        $prefix = 'TCK-';
        $year = date('Y');
        $month = date('m');
        
        // الحصول على آخر رقم تذكرة في الشهر الحالي
        $lastTicket = Ticket::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc')
            ->first();
            
        if ($lastTicket && $lastTicket->tid) {
            $lastNumber = intval(substr($lastTicket->tid, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }
        
        return $prefix . $year . $month . $newNumber;
    }
}