<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\InvoiceItem;
use App\Services\WhmcsApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class InvoiceService
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
    }

    /**
     * الحصول على قائمة الفواتير من WHMCS
     *
     * @param int $limit
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getInvoicesFromWhmcs($limit = 25, $page = 1, $filters = [])
    {
        return $this->whmcsApiService->getInvoices($limit, $page, $filters);
    }

    /**
     * الحصول على قائمة الفواتير من قاعدة البيانات المحلية
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLocalInvoices($limit = 25)
    {
        return Invoice::with(['customer', 'items'])
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * مزامنة الفواتير من WHMCS إلى قاعدة البيانات المحلية
     *
     * @return array
     */
    public function syncInvoicesFromWhmcs()
    {
        $results = [
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        try {
            $page = 1;
            $limit = 100;
            $hasMore = true;

            while ($hasMore) {
                $whmcsInvoices = $this->whmcsApiService->getInvoices($limit, $page);

                if (empty($whmcsInvoices)) {
                    $hasMore = false;
                    continue;
                }

                foreach ($whmcsInvoices as $whmcsInvoice) {
                    // التحقق من وجود العميل
                    $customer = \App\Models\Customer::where('whmcs_id', $whmcsInvoice['userid'])->first();
                    
                    if (!$customer) {
                        continue;
                    }

                    $invoice = Invoice::where('whmcs_id', $whmcsInvoice['id'])->first();

                    if ($invoice) {
                        // تحديث الفاتورة الموجودة
                        $invoice->update([
                            'whmcs_client_id' => $customer->whmcs_id,
                            'invoicenum' => $whmcsInvoice['invoicenum'] ?? null,
                            'date' => !empty($whmcsInvoice['date']) ? date('Y-m-d H:i:s', strtotime($whmcsInvoice['date'])) : now(),
                            'duedate' => !empty($whmcsInvoice['duedate']) ? date('Y-m-d H:i:s', strtotime($whmcsInvoice['duedate'])) : now(),
                            'datepaid' => !empty($whmcsInvoice['datepaid']) ? date('Y-m-d H:i:s', strtotime($whmcsInvoice['datepaid'])) : null,
                            'subtotal' => $whmcsInvoice['subtotal'] ?? 0,
                            'credit' => $whmcsInvoice['credit'] ?? 0,
                            'tax' => $whmcsInvoice['tax'] ?? 0,
                            'taxrate' => $whmcsInvoice['taxrate'] ?? 0,
                            'tax2' => $whmcsInvoice['tax2'] ?? 0,
                            'taxrate2' => $whmcsInvoice['taxrate2'] ?? 0,
                            'total' => $whmcsInvoice['total'] ?? 0,
                            'status' => $whmcsInvoice['status'] ?? 'Unpaid',
                            'paymentmethod' => $whmcsInvoice['paymentmethod'] ?? null,
                            'notes' => $whmcsInvoice['notes'] ?? null,
                            'synced_at' => now(),
                        ]);

                        $results['updated']++;
                    } else {
                        // إنشاء فاتورة جديدة
                        $invoice = Invoice::create([
                            'whmcs_id' => $whmcsInvoice['id'],
                            'whmcs_client_id' => $customer->whmcs_id,
                            'invoicenum' => $whmcsInvoice['invoicenum'] ?? null,
                            'date' => !empty($whmcsInvoice['date']) ? date('Y-m-d H:i:s', strtotime($whmcsInvoice['date'])) : now(),
                            'duedate' => !empty($whmcsInvoice['duedate']) ? date('Y-m-d H:i:s', strtotime($whmcsInvoice['duedate'])) : now(),
                            'datepaid' => !empty($whmcsInvoice['datepaid']) ? date('Y-m-d H:i:s', strtotime($whmcsInvoice['datepaid'])) : null,
                            'subtotal' => $whmcsInvoice['subtotal'] ?? 0,
                            'credit' => $whmcsInvoice['credit'] ?? 0,
                            'tax' => $whmcsInvoice['tax'] ?? 0,
                            'taxrate' => $whmcsInvoice['taxrate'] ?? 0,
                            'tax2' => $whmcsInvoice['tax2'] ?? 0,
                            'taxrate2' => $whmcsInvoice['taxrate2'] ?? 0,
                            'total' => $whmcsInvoice['total'] ?? 0,
                            'status' => $whmcsInvoice['status'] ?? 'Unpaid',
                            'paymentmethod' => $whmcsInvoice['paymentmethod'] ?? null,
                            'notes' => $whmcsInvoice['notes'] ?? null,
                            'synced_at' => now(),
                        ]);

                        $results['created']++;
                    }
                    
                    // مزامنة بنود الفاتورة
                    $this->syncInvoiceItems($whmcsInvoice['id'], $invoice->id);
                }

                $page++;
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing invoices from WHMCS', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * مزامنة بنود الفاتورة
     *
     * @param int $whmcsInvoiceId
     * @param int $localInvoiceId
     * @return void
     */
    private function syncInvoiceItems($whmcsInvoiceId, $localInvoiceId)
    {
        try {
            $whmcsInvoiceDetails = $this->whmcsApiService->getInvoiceDetails($whmcsInvoiceId);
            
            if (!$whmcsInvoiceDetails || !isset($whmcsInvoiceDetails['items']['item'])) {
                return;
            }
            
            foreach ($whmcsInvoiceDetails['items']['item'] as $whmcsItem) {
                // البحث عن المنتج المحلي
                $product = null;
                if (!empty($whmcsItem['pid'])) {
                    $product = \App\Models\Product::where('whmcs_id', $whmcsItem['pid'])->first();
                }
                
                // البحث عن بند الفاتورة
                $invoiceItem = InvoiceItem::where('whmcs_invoice_item_id', $whmcsItem['id'])->first();
                
                if ($invoiceItem) {
                    // تحديث بند الفاتورة الموجود
                    $invoiceItem->update([
                        'product_id' => $product ? $product->id : null,
                        'description' => $whmcsItem['description'] ?? '',
                        'amount' => $whmcsItem['amount'] ?? 0,
                        'taxed' => !empty($whmcsItem['taxed']),
                    ]);
                } else {
                    // إنشاء بند فاتورة جديد
                    InvoiceItem::create([
                        'whmcs_invoice_item_id' => $whmcsItem['id'],
                        'invoice_id' => $localInvoiceId,
                        'product_id' => $product ? $product->id : null,
                        'description' => $whmcsItem['description'] ?? '',
                        'amount' => $whmcsItem['amount'] ?? 0,
                        'taxed' => !empty($whmcsItem['taxed']),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing invoice items', [
                'whmcs_invoice_id' => $whmcsInvoiceId,
                'local_invoice_id' => $localInvoiceId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * الحصول على الفواتير حسب العميل
     *
     * @param int $customerId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getInvoicesByCustomer($customerId)
    {
        return Invoice::where('whmcs_client_id', $customerId)
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * الحصول على الفواتير حسب الحالة
     *
     * @param string $status
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInvoicesByStatus($status, $limit = 25)
    {
        return Invoice::where('status', $status)
            ->with(['customer'])
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * الحصول على الفواتير المتأخرة
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOverdueInvoices($limit = 25)
    {
        return Invoice::where('status', 'Unpaid')
            ->where('duedate', '<', now())
            ->with(['customer'])
            ->orderBy('duedate', 'asc')
            ->paginate($limit);
    }

    /**
     * الحصول على الفواتير المدفوعة
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPaidInvoices($limit = 25)
    {
        return Invoice::where('status', 'Paid')
            ->with(['customer'])
            ->orderBy('datepaid', 'desc')
            ->paginate($limit);
    }

    /**
     * البحث عن الفواتير
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchInvoices($query, $limit = 25)
    {
        return Invoice::with(['customer'])
            ->where(function ($q) use ($query) {
                $q->where('invoicenum', 'like', "%{$query}%")
                  ->orWhereHas('customer', function ($q) use ($query) {
                      $q->where('firstname', 'like', "%{$query}%")
                        ->orWhere('lastname', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                  });
            })
            ->orderBy('date', 'desc')
            ->paginate($limit);
    }

    /**
     * مزامنة المدفوعات من WHMCS
     *
     * @return array
     */
    public function syncPaymentsFromWhmcs()
    {
        $results = [
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        try {
            // الحصول على جميع الفواتير المحلية
            $invoices = Invoice::all();
            
            foreach ($invoices as $invoice) {
                $whmcsInvoiceDetails = $this->whmcsApiService->getInvoiceDetails($invoice->whmcs_id);
                
                if (!$whmcsInvoiceDetails || !isset($whmcsInvoiceDetails['transactions']['transaction'])) {
                    continue;
                }
                
                foreach ($whmcsInvoiceDetails['transactions']['transaction'] as $whmcsTransaction) {
                    // البحث عن المدفوع
                    $payment = Payment::where('whmcs_id', $whmcsTransaction['id'])->first();
                    
                    if ($payment) {
                        // تحديث المدفوع الموجود
                        $payment->update([
                            'whmcs_invoice_id' => $invoice->whmcs_id,
                            'whmcs_client_id' => $invoice->whmcs_client_id,
                            'date' => !empty($whmcsTransaction['date']) ? date('Y-m-d H:i:s', strtotime($whmcsTransaction['date'])) : now(),
                            'amount' => $whmcsTransaction['amountin'] ?? 0,
                            'fees' => $whmcsTransaction['fees'] ?? 0,
                            'paymentmethod' => $whmcsTransaction['gateway'] ?? null,
                            'transid' => $whmcsTransaction['transid'] ?? null,
                            'status' => $whmcsTransaction['status'] ?? 'Completed',
                            'synced_at' => now(),
                        ]);
                        
                        $results['updated']++;
                    } else {
                        // إنشاء مدفوع جديد
                        Payment::create([
                            'whmcs_id' => $whmcsTransaction['id'],
                            'invoice_id' => $invoice->id,
                            'whmcs_invoice_id' => $invoice->whmcs_id,
                            'whmcs_client_id' => $invoice->whmcs_client_id,
                            'date' => !empty($whmcsTransaction['date']) ? date('Y-m-d H:i:s', strtotime($whmcsTransaction['date'])) : now(),
                            'amount' => $whmcsTransaction['amountin'] ?? 0,
                            'fees' => $whmcsTransaction['fees'] ?? 0,
                            'paymentmethod' => $whmcsTransaction['gateway'] ?? null,
                            'transid' => $whmcsTransaction['transid'] ?? null,
                            'status' => $whmcsTransaction['status'] ?? 'Completed',
                            'synced_at' => now(),
                        ]);
                        
                        $results['created']++;
                    }
                }
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing payments from WHMCS', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * الحصول على إحصائيات الفواتير
     *
     * @return array
     */
    public function getInvoiceStatistics()
    {
        $totalInvoices = Invoice::count();
        $paidInvoices = Invoice::where('status', 'Paid')->count();
        $unpaidInvoices = Invoice::where('status', 'Unpaid')->count();
        $overdueInvoices = Invoice::where('status', 'Unpaid')
            ->where('duedate', '<', now())
            ->count();
        
        $totalAmount = Invoice::sum('total');
        $paidAmount = Invoice::where('status', 'Paid')->sum('total');
        $unpaidAmount = Invoice::where('status', 'Unpaid')->sum('total');
        $overdueAmount = Invoice::where('status', 'Unpaid')
            ->where('duedate', '<', now())
            ->sum('total');
        
        return [
            'total_invoices' => $totalInvoices,
            'paid_invoices' => $paidInvoices,
            'unpaid_invoices' => $unpaidInvoices,
            'overdue_invoices' => $overdueInvoices,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'unpaid_amount' => $unpaidAmount,
            'overdue_amount' => $overdueAmount,
        ];
    }
}