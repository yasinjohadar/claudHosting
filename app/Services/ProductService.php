<?php

namespace App\Services;

use App\Models\Product;
use App\Models\CustomerProduct;
use App\Services\WhmcsApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
    }

    /**
     * الحصول على قائمة المنتجات من WHMCS
     *
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getProductsFromWhmcs($limit = 25, $page = 1)
    {
        return $this->whmcsApiService->getProducts($limit, $page);
    }

    /**
     * الحصول على قائمة المنتجات من قاعدة البيانات المحلية
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLocalProducts($limit = 25)
    {
        return Product::withCount(['customers'])
            ->orderBy('name', 'asc')
            ->paginate($limit);
    }

    /**
     * مزامنة المنتجات من WHMCS إلى قاعدة البيانات المحلية
     *
     * @return array
     */
    public function syncProductsFromWhmcs()
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
                $whmcsProducts = $this->whmcsApiService->getProducts($limit, $page);

                if (empty($whmcsProducts)) {
                    $hasMore = false;
                    continue;
                }

                foreach ($whmcsProducts as $whmcsProduct) {
                    $product = Product::where('whmcs_id', $whmcsProduct['id'])->first();

                    if ($product) {
                        // تحديث المنتج الموجود
                        $product->update([
                            'type' => $whmcsProduct['type'] ?? 'other',
                            'gid' => $whmcsProduct['gid'] ?? 1,
                            'name' => $whmcsProduct['name'],
                            'description' => $whmcsProduct['description'] ?? null,
                            'paytype' => $whmcsProduct['paytype'] ?? 'recurring',
                            'pricing' => $whmcsProduct['pricing'] ?? null,
                            'currency' => $whmcsProduct['currency'] ?? 1,
                            'showdomainoptions' => !empty($whmcsProduct['showdomainoptions']),
                            'stockcontrol' => !empty($whmcsProduct['stockcontrol']),
                            'qty' => $whmcsProduct['qty'] ?? 0,
                            'prorata' => !empty($whmcsProduct['prorata']),
                            'proratadate' => !empty($whmcsProduct['proratadate']) ? date('Y-m-d', strtotime($whmcsProduct['proratadate'])) : null,
                            'proratachargenextmonth' => !empty($whmcsProduct['proratachargenextmonth']),
                            'hidden' => !empty($whmcsProduct['hidden']),
                            'tax' => empty($whmcsProduct['tax']) ? true : false,
                            'allowqty' => !empty($whmcsProduct['allowqty']),
                            'recurring' => empty($whmcsProduct['recurring']) ? true : false,
                            'autoterminate' => empty($whmcsProduct['autoterminate']) ? true : false,
                            'autorenew' => empty($whmcsProduct['autorenew']) ? true : false,
                            'servertype' => $whmcsProduct['servertype'] ?? null,
                            'servergroup' => $whmcsProduct['servergroup'] ?? null,
                            'configoption1' => $whmcsProduct['configoption1'] ?? null,
                            'configoption2' => $whmcsProduct['configoption2'] ?? null,
                            'configoption3' => $whmcsProduct['configoption3'] ?? null,
                            'configoption4' => $whmcsProduct['configoption4'] ?? null,
                            'configoption5' => $whmcsProduct['configoption5'] ?? null,
                            'configoption6' => $whmcsProduct['configoption6'] ?? null,
                            'configoption7' => $whmcsProduct['configoption7'] ?? null,
                            'configoption8' => $whmcsProduct['configoption8'] ?? null,
                            'configoption9' => $whmcsProduct['configoption9'] ?? null,
                            'configoption10' => $whmcsProduct['configoption10'] ?? null,
                            'configoption11' => $whmcsProduct['configoption11'] ?? null,
                            'configoption12' => $whmcsProduct['configoption12'] ?? null,
                            'configoption13' => $whmcsProduct['configoption13'] ?? null,
                            'configoption14' => $whmcsProduct['configoption14'] ?? null,
                            'configoption15' => $whmcsProduct['configoption15'] ?? null,
                            'configoption16' => $whmcsProduct['configoption16'] ?? null,
                            'configoption17' => $whmcsProduct['configoption17'] ?? null,
                            'configoption18' => $whmcsProduct['configoption18'] ?? null,
                            'configoption19' => $whmcsProduct['configoption19'] ?? null,
                            'configoption20' => $whmcsProduct['configoption20'] ?? null,
                            'configoption21' => $whmcsProduct['configoption21'] ?? null,
                            'configoption22' => $whmcsProduct['configoption22'] ?? null,
                            'configoption23' => $whmcsProduct['configoption23'] ?? null,
                            'configoption24' => $whmcsProduct['configoption24'] ?? null,
                            'synced_at' => now(),
                        ]);

                        $results['updated']++;
                    } else {
                        // إنشاء منتج جديد
                        Product::create([
                            'whmcs_id' => $whmcsProduct['id'],
                            'type' => $whmcsProduct['type'] ?? 'other',
                            'gid' => $whmcsProduct['gid'] ?? 1,
                            'name' => $whmcsProduct['name'],
                            'description' => $whmcsProduct['description'] ?? null,
                            'paytype' => $whmcsProduct['paytype'] ?? 'recurring',
                            'pricing' => $whmcsProduct['pricing'] ?? null,
                            'currency' => $whmcsProduct['currency'] ?? 1,
                            'showdomainoptions' => !empty($whmcsProduct['showdomainoptions']),
                            'stockcontrol' => !empty($whmcsProduct['stockcontrol']),
                            'qty' => $whmcsProduct['qty'] ?? 0,
                            'prorata' => !empty($whmcsProduct['prorata']),
                            'proratadate' => !empty($whmcsProduct['proratadate']) ? date('Y-m-d', strtotime($whmcsProduct['proratadate'])) : null,
                            'proratachargenextmonth' => !empty($whmcsProduct['proratachargenextmonth']),
                            'hidden' => !empty($whmcsProduct['hidden']),
                            'tax' => empty($whmcsProduct['tax']) ? true : false,
                            'allowqty' => !empty($whmcsProduct['allowqty']),
                            'recurring' => empty($whmcsProduct['recurring']) ? true : false,
                            'autoterminate' => empty($whmcsProduct['autoterminate']) ? true : false,
                            'autorenew' => empty($whmcsProduct['autorenew']) ? true : false,
                            'servertype' => $whmcsProduct['servertype'] ?? null,
                            'servergroup' => $whmcsProduct['servergroup'] ?? null,
                            'configoption1' => $whmcsProduct['configoption1'] ?? null,
                            'configoption2' => $whmcsProduct['configoption2'] ?? null,
                            'configoption3' => $whmcsProduct['configoption3'] ?? null,
                            'configoption4' => $whmcsProduct['configoption4'] ?? null,
                            'configoption5' => $whmcsProduct['configoption5'] ?? null,
                            'configoption6' => $whmcsProduct['configoption6'] ?? null,
                            'configoption7' => $whmcsProduct['configoption7'] ?? null,
                            'configoption8' => $whmcsProduct['configoption8'] ?? null,
                            'configoption9' => $whmcsProduct['configoption9'] ?? null,
                            'configoption10' => $whmcsProduct['configoption10'] ?? null,
                            'configoption11' => $whmcsProduct['configoption11'] ?? null,
                            'configoption12' => $whmcsProduct['configoption12'] ?? null,
                            'configoption13' => $whmcsProduct['configoption13'] ?? null,
                            'configoption14' => $whmcsProduct['configoption14'] ?? null,
                            'configoption15' => $whmcsProduct['configoption15'] ?? null,
                            'configoption16' => $whmcsProduct['configoption16'] ?? null,
                            'configoption17' => $whmcsProduct['configoption17'] ?? null,
                            'configoption18' => $whmcsProduct['configoption18'] ?? null,
                            'configoption19' => $whmcsProduct['configoption19'] ?? null,
                            'configoption20' => $whmcsProduct['configoption20'] ?? null,
                            'configoption21' => $whmcsProduct['configoption21'] ?? null,
                            'configoption22' => $whmcsProduct['configoption22'] ?? null,
                            'configoption23' => $whmcsProduct['configoption23'] ?? null,
                            'configoption24' => $whmcsProduct['configoption24'] ?? null,
                            'synced_at' => now(),
                        ]);

                        $results['created']++;
                    }
                }

                $page++;
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing products from WHMCS', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * الحصول على المنتجات النشطة
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveProducts()
    {
        return Product::where('hidden', false)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * الحصول على المنتجات حسب النوع
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsByType($type)
    {
        return Product::where('type', $type)
            ->where('hidden', false)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * الحصول على المنتجات حسب المجموعة
     *
     * @param int $groupId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsByGroup($groupId)
    {
        return Product::where('gid', $groupId)
            ->where('hidden', false)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * البحث عن المنتجات
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchProducts($query, $limit = 25)
    {
        return Product::withCount(['customers'])
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('type', 'like', "%{$query}%");
            })
            ->orderBy('name', 'asc')
            ->paginate($limit);
    }

    /**
     * مزامنة خدمات العملاء من WHMCS
     *
     * @return array
     */
    public function syncCustomerProductsFromWhmcs()
    {
        $results = [
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        try {
            // الحصول على جميع العملاء المحليين
            $customers = \App\Models\Customer::all();
            
            foreach ($customers as $customer) {
                // الحصول على خدمات العميل من WHMCS
                $whmcsCustomerDetails = $this->whmcsApiService->getCustomerDetails($customer->whmcs_id);
                
                if (!$whmcsCustomerDetails || !isset($whmcsCustomerDetails['products'])) {
                    continue;
                }
                
                foreach ($whmcsCustomerDetails['products']['product'] as $whmcsProduct) {
                    // البحث عن المنتج المحلي
                    $product = Product::where('whmcs_id', $whmcsProduct['pid'])->first();
                    
                    if (!$product) {
                        continue;
                    }
                    
                    // البحث عن خدمة العميل
                    $customerProduct = CustomerProduct::where('whmcs_service_id', $whmcsProduct['id'])->first();
                    
                    if ($customerProduct) {
                        // تحديث خدمة العميل الموجودة
                        $customerProduct->update([
                            'orderid' => $whmcsProduct['orderid'] ?? null,
                            'regdate' => !empty($whmcsProduct['regdate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['regdate'])) : now(),
                            'domain' => $whmcsProduct['domain'] ?? null,
                            'paymentmethod' => $whmcsProduct['paymentmethod'] ?? null,
                            'firstpaymentamount' => $whmcsProduct['firstpaymentamount'] ?? 0,
                            'amount' => $whmcsProduct['amount'] ?? 0,
                            'billingcycle' => $whmcsProduct['billingcycle'] ?? 'Monthly',
                            'nextduedate' => !empty($whmcsProduct['nextduedate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['nextduedate'])) : null,
                            'nextinvoicedate' => !empty($whmcsProduct['nextinvoicedate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['nextinvoicedate'])) : null,
                            'termination_date' => !empty($whmcsProduct['termination_date']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['termination_date'])) : null,
                            'completed_date' => !empty($whmcsProduct['completed_date']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['completed_date'])) : null,
                            'domainstatus' => $whmcsProduct['status'] ?? 'Pending',
                            'username' => $whmcsProduct['username'] ?? null,
                            'password' => $whmcsProduct['password'] ?? null,
                            'notes' => $whmcsProduct['notes'] ?? null,
                            'subscriptionid' => $whmcsProduct['subscriptionid'] ?? null,
                            'promoid' => $whmcsProduct['promoid'] ?? null,
                            'overideautosuspend' => !empty($whmcsProduct['overideautosuspend']),
                            'overidesuspenduntil' => !empty($whmcsProduct['overidesuspenduntil']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['overidesuspenduntil'])) : null,
                            'lastupdate' => !empty($whmcsProduct['lastupdate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['lastupdate'])) : now(),
                            'synced_at' => now(),
                        ]);
                        
                        $results['updated']++;
                    } else {
                        // إنشاء خدمة عميل جديدة
                        CustomerProduct::create([
                            'whmcs_service_id' => $whmcsProduct['id'],
                            'customer_id' => $customer->id,
                            'product_id' => $product->id,
                            'orderid' => $whmcsProduct['orderid'] ?? null,
                            'regdate' => !empty($whmcsProduct['regdate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['regdate'])) : now(),
                            'domain' => $whmcsProduct['domain'] ?? null,
                            'paymentmethod' => $whmcsProduct['paymentmethod'] ?? null,
                            'firstpaymentamount' => $whmcsProduct['firstpaymentamount'] ?? 0,
                            'amount' => $whmcsProduct['amount'] ?? 0,
                            'billingcycle' => $whmcsProduct['billingcycle'] ?? 'Monthly',
                            'nextduedate' => !empty($whmcsProduct['nextduedate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['nextduedate'])) : null,
                            'nextinvoicedate' => !empty($whmcsProduct['nextinvoicedate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['nextinvoicedate'])) : null,
                            'termination_date' => !empty($whmcsProduct['termination_date']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['termination_date'])) : null,
                            'completed_date' => !empty($whmcsProduct['completed_date']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['completed_date'])) : null,
                            'domainstatus' => $whmcsProduct['status'] ?? 'Pending',
                            'username' => $whmcsProduct['username'] ?? null,
                            'password' => $whmcsProduct['password'] ?? null,
                            'notes' => $whmcsProduct['notes'] ?? null,
                            'subscriptionid' => $whmcsProduct['subscriptionid'] ?? null,
                            'promoid' => $whmcsProduct['promoid'] ?? null,
                            'overideautosuspend' => !empty($whmcsProduct['overideautosuspend']),
                            'overidesuspenduntil' => !empty($whmcsProduct['overidesuspenduntil']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['overidesuspenduntil'])) : null,
                            'lastupdate' => !empty($whmcsProduct['lastupdate']) ? date('Y-m-d H:i:s', strtotime($whmcsProduct['lastupdate'])) : now(),
                            'synced_at' => now(),
                        ]);
                        
                        $results['created']++;
                    }
                }
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing customer products from WHMCS', ['error' => $e->getMessage()]);
        }

        return $results;
    }
}