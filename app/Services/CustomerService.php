<?php

namespace App\Services;

use App\Models\Customer;
use App\Services\WhmcsApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CustomerService
{
    protected $whmcsApiService;

    public function __construct(WhmcsApiService $whmcsApiService)
    {
        $this->whmcsApiService = $whmcsApiService;
    }

    /**
     * الحصول على قائمة العملاء من WHMCS
     *
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getCustomersFromWhmcs($limit = 25, $page = 1)
    {
        return $this->whmcsApiService->getCustomers($limit, $page);
    }

    /**
     * الحصول على قائمة العملاء من قاعدة البيانات المحلية
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getLocalCustomers($limit = 25)
    {
        return Customer::withCount(['invoices', 'tickets'])
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * مزامنة العملاء من WHMCS إلى قاعدة البيانات المحلية
     *
     * @return array
     */
    public function syncCustomersFromWhmcs()
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
                $whmcsCustomers = $this->whmcsApiService->getCustomers($limit, $page);

                if (empty($whmcsCustomers)) {
                    $hasMore = false;
                    continue;
                }

                foreach ($whmcsCustomers as $whmcsCustomer) {
                    $customer = Customer::where('whmcs_id', $whmcsCustomer['id'])->first();

                    if ($customer) {
                        // تحديث العميل الموجود
                        $customer->update([
                            'firstname' => $whmcsCustomer['firstname'],
                            'lastname' => $whmcsCustomer['lastname'],
                            'fullname' => $whmcsCustomer['firstname'] . ' ' . $whmcsCustomer['lastname'],
                            'email' => $whmcsCustomer['email'],
                            'companyname' => $whmcsCustomer['companyname'] ?? null,
                            'address1' => $whmcsCustomer['address1'] ?? null,
                            'address2' => $whmcsCustomer['address2'] ?? null,
                            'city' => $whmcsCustomer['city'] ?? null,
                            'state' => $whmcsCustomer['state'] ?? null,
                            'postcode' => $whmcsCustomer['postcode'] ?? null,
                            'country' => $whmcsCustomer['country'] ?? 'US',
                            'phonenumber' => $whmcsCustomer['phonenumber'] ?? null,
                            'currency' => $whmcsCustomer['currency'] ?? 1,
                            'groupid' => $whmcsCustomer['groupid'] ?? 1,
                            'status' => $whmcsCustomer['status'] ?? 'Active',
                            'notes' => $whmcsCustomer['notes'] ?? null,
                            'last_login' => !empty($whmcsCustomer['lastlogin']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomer['lastlogin'])) : null,
                            'date_created' => !empty($whmcsCustomer['datecreated']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomer['datecreated'])) : now(),
                            'synced_at' => now(),
                        ]);

                        $results['updated']++;
                    } else {
                        // إنشاء عميل جديد
                        Customer::create([
                            'whmcs_id' => $whmcsCustomer['id'],
                            'firstname' => $whmcsCustomer['firstname'],
                            'lastname' => $whmcsCustomer['lastname'],
                            'fullname' => $whmcsCustomer['firstname'] . ' ' . $whmcsCustomer['lastname'],
                            'email' => $whmcsCustomer['email'],
                            'companyname' => $whmcsCustomer['companyname'] ?? null,
                            'address1' => $whmcsCustomer['address1'] ?? null,
                            'address2' => $whmcsCustomer['address2'] ?? null,
                            'city' => $whmcsCustomer['city'] ?? null,
                            'state' => $whmcsCustomer['state'] ?? null,
                            'postcode' => $whmcsCustomer['postcode'] ?? null,
                            'country' => $whmcsCustomer['country'] ?? 'US',
                            'phonenumber' => $whmcsCustomer['phonenumber'] ?? null,
                            'currency' => $whmcsCustomer['currency'] ?? 1,
                            'groupid' => $whmcsCustomer['groupid'] ?? 1,
                            'status' => $whmcsCustomer['status'] ?? 'Active',
                            'notes' => $whmcsCustomer['notes'] ?? null,
                            'last_login' => !empty($whmcsCustomer['lastlogin']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomer['lastlogin'])) : null,
                            'date_created' => !empty($whmcsCustomer['datecreated']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomer['datecreated'])) : now(),
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
            Log::error('Error syncing customers from WHMCS', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * الحصول على تفاصيل عميل من WHMCS
     *
     * @param int $whmcsCustomerId
     * @return array|null
     */
    public function getCustomerDetailsFromWhmcs($whmcsCustomerId)
    {
        return $this->whmcsApiService->getCustomerDetails($whmcsCustomerId);
    }

    /**
     * الحصول على تفاصيل عميل من قاعدة البيانات المحلية
     *
     * @param int $id
     * @return Customer|null
     */
    public function getLocalCustomerDetails($id)
    {
        return Customer::with(['invoices', 'tickets', 'products'])
            ->find($id);
    }

    /**
     * مزامنة عميل واحد من WHMCS
     *
     * @param int $whmcsCustomerId
     * @return array
     */
    public function syncSingleCustomerFromWhmcs($whmcsCustomerId)
    {
        $results = [
            'success' => true,
            'created' => false,
            'updated' => false,
            'customer' => null,
            'errors' => []
        ];

        try {
            $whmcsCustomerDetails = $this->whmcsApiService->getCustomerDetails($whmcsCustomerId);

            if (!$whmcsCustomerDetails || !isset($whmcsCustomerDetails['id'])) {
                $results['success'] = false;
                $results['errors'][] = 'Customer not found in WHMCS';
                return $results;
            }

            $customer = Customer::where('whmcs_id', $whmcsCustomerId)->first();

            if ($customer) {
                // تحديث العميل الموجود
                $customer->update([
                    'firstname' => $whmcsCustomerDetails['firstname'],
                    'lastname' => $whmcsCustomerDetails['lastname'],
                    'fullname' => $whmcsCustomerDetails['firstname'] . ' ' . $whmcsCustomerDetails['lastname'],
                    'email' => $whmcsCustomerDetails['email'],
                    'companyname' => $whmcsCustomerDetails['companyname'] ?? null,
                    'address1' => $whmcsCustomerDetails['address1'] ?? null,
                    'address2' => $whmcsCustomerDetails['address2'] ?? null,
                    'city' => $whmcsCustomerDetails['city'] ?? null,
                    'state' => $whmcsCustomerDetails['state'] ?? null,
                    'postcode' => $whmcsCustomerDetails['postcode'] ?? null,
                    'country' => $whmcsCustomerDetails['country'] ?? 'US',
                    'phonenumber' => $whmcsCustomerDetails['phonenumber'] ?? null,
                    'currency' => $whmcsCustomerDetails['currency'] ?? 1,
                    'groupid' => $whmcsCustomerDetails['groupid'] ?? 1,
                    'status' => $whmcsCustomerDetails['status'] ?? 'Active',
                    'notes' => $whmcsCustomerDetails['notes'] ?? null,
                    'last_login' => !empty($whmcsCustomerDetails['lastlogin']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomerDetails['lastlogin'])) : null,
                    'date_created' => !empty($whmcsCustomerDetails['datecreated']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomerDetails['datecreated'])) : now(),
                    'synced_at' => now(),
                ]);

                $results['updated'] = true;
            } else {
                // إنشاء عميل جديد
                $customer = Customer::create([
                    'whmcs_id' => $whmcsCustomerDetails['id'],
                    'firstname' => $whmcsCustomerDetails['firstname'],
                    'lastname' => $whmcsCustomerDetails['lastname'],
                    'fullname' => $whmcsCustomerDetails['firstname'] . ' ' . $whmcsCustomerDetails['lastname'],
                    'email' => $whmcsCustomerDetails['email'],
                    'companyname' => $whmcsCustomerDetails['companyname'] ?? null,
                    'address1' => $whmcsCustomerDetails['address1'] ?? null,
                    'address2' => $whmcsCustomerDetails['address2'] ?? null,
                    'city' => $whmcsCustomerDetails['city'] ?? null,
                    'state' => $whmcsCustomerDetails['state'] ?? null,
                    'postcode' => $whmcsCustomerDetails['postcode'] ?? null,
                    'country' => $whmcsCustomerDetails['country'] ?? 'US',
                    'phonenumber' => $whmcsCustomerDetails['phonenumber'] ?? null,
                    'currency' => $whmcsCustomerDetails['currency'] ?? 1,
                    'groupid' => $whmcsCustomerDetails['groupid'] ?? 1,
                    'status' => $whmcsCustomerDetails['status'] ?? 'Active',
                    'notes' => $whmcsCustomerDetails['notes'] ?? null,
                    'last_login' => !empty($whmcsCustomerDetails['lastlogin']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomerDetails['lastlogin'])) : null,
                    'date_created' => !empty($whmcsCustomerDetails['datecreated']) ? date('Y-m-d H:i:s', strtotime($whmcsCustomerDetails['datecreated'])) : now(),
                    'synced_at' => now(),
                ]);

                $results['created'] = true;
            }

            $results['customer'] = $customer;
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error syncing customer from WHMCS', [
                'whmcs_customer_id' => $whmcsCustomerId,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * إضافة عميل جديد إلى WHMCS
     *
     * @param array $data
     * @return array
     */
    public function addCustomerToWhmcs($data)
    {
        $results = [
            'success' => true,
            'customer' => null,
            'errors' => []
        ];

        try {
            $response = $this->whmcsApiService->addCustomer($data);

            if ($response['success']) {
                $whmcsCustomerId = $response['data']['clientid'] ?? null;
                
                if ($whmcsCustomerId) {
                    // مزامنة العميل من WHMCS إلى قاعدة البيانات المحلية
                    $syncResult = $this->syncSingleCustomerFromWhmcs($whmcsCustomerId);
                    
                    if ($syncResult['success']) {
                        $results['customer'] = $syncResult['customer'];
                    } else {
                        $results['success'] = false;
                        $results['errors'] = $syncResult['errors'];
                    }
                } else {
                    $results['success'] = false;
                    $results['errors'][] = 'Failed to get customer ID from WHMCS response';
                }
            } else {
                $results['success'] = false;
                $results['errors'][] = $response['message'] ?? 'Unknown error';
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error adding customer to WHMCS', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * تحديث بيانات عميل في WHMCS
     *
     * @param int $whmcsCustomerId
     * @param array $data
     * @return array
     */
    public function updateCustomerInWhmcs($whmcsCustomerId, $data)
    {
        $results = [
            'success' => true,
            'customer' => null,
            'errors' => []
        ];

        try {
            $response = $this->whmcsApiService->updateCustomer($whmcsCustomerId, $data);

            if ($response['success']) {
                // مزامنة العميل من WHMCS إلى قاعدة البيانات المحلية
                $syncResult = $this->syncSingleCustomerFromWhmcs($whmcsCustomerId);
                
                if ($syncResult['success']) {
                    $results['customer'] = $syncResult['customer'];
                } else {
                    $results['success'] = false;
                    $results['errors'] = $syncResult['errors'];
                }
            } else {
                $results['success'] = false;
                $results['errors'][] = $response['message'] ?? 'Unknown error';
            }
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['errors'][] = $e->getMessage();
            Log::error('Error updating customer in WHMCS', [
                'whmcs_customer_id' => $whmcsCustomerId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
        }

        return $results;
    }

    /**
     * البحث عن العملاء
     *
     * @param string $query
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchCustomers($query, $limit = 25)
    {
        return Customer::withCount(['invoices', 'tickets'])
            ->where(function ($q) use ($query) {
                $q->where('firstname', 'like', "%{$query}%")
                  ->orWhere('lastname', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phonenumber', 'like', "%{$query}%")
                  ->orWhere('companyname', 'like', "%{$query}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
}