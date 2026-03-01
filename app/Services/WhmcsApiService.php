<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhmcsApiService
{
    public function __construct(
        protected WhmcsService $whmcsService
    ) {}

    /**
     * إجراء طلب إلى WHMCS API (عبر WhmcsService الذي يستخدم config/services.php)
     *
     * @param string $action
     * @param array $params
     * @return array
     */
    public function request($action, $params = [])
    {
        return $this->makeRequest($action, $params);
    }

    /**
     * إجراء طلب إلى WHMCS API
     *
     * @param string $action
     * @param array $params
     * @return array
     */
    public function makeRequest($action, $params = [])
    {
        $allowed = config('whmcs_allowed_actions.actions', []);
        if (! empty($allowed) && ! static::isActionAllowed($action)) {
            return ['success' => false, 'message' => 'Action not allowed: ' . $action];
        }

        try {
            $data = $this->whmcsService->call($action, $params);

            if (isset($data['result']) && $data['result'] === 'success') {
                return ['success' => true, 'data' => $data];
            }

            $message = $data['message'] ?? 'Unknown error';
            Log::warning('WHMCS API Error', ['action' => $action, 'message' => $message]);

            return ['success' => false, 'message' => $message];
        } catch (\Exception $e) {
            Log::error('WHMCS API Exception', ['action' => $action, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }
    
    /**
     * إجراء طلب إلى WHMCS API (别名)
     *
     * @param string $action
     * @param array $params
     * @return array
     */
    public function callAPI($action, $params = [])
    {
        return $this->makeRequest($action, $params);
    }

    /**
     * التحقق من أن الـ action مسموح به حسب roles-whmcs
     */
    public static function isActionAllowed(string $action): bool
    {
        $allowed = config('whmcs_allowed_actions.actions', []);
        return in_array($action, $allowed, true);
    }

    /**
     * الحصول على قائمة العملاء
     *
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getCustomers($limit = 25, $page = 1)
    {
        $cacheKey = "whmcs_customers_page_{$page}_limit_{$limit}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($limit, $page) {
            $response = $this->request('GetClients', [
                'limitnum' => $limit,
                'page' => $page,
            ]);
            
            if ($response['success']) {
                $client = $response['data']['clients']['client'] ?? [];
                return $this->normalizeClientList($client);
            }
            
            return [];
        });
    }

    /**
     * تحويل استجابة العملاء إلى مصفوفة (WHMCS أحياناً يرجع عميلاً واحداً ككائن)
     */
    private function normalizeClientList($client): array
    {
        if (! is_array($client)) {
            return [];
        }
        return isset($client[0]) ? $client : [$client];
    }

    /**
     * تحويل تاريخ من WHMCS إلى Carbon أو null (تواريخ غير صالحة لـ MySQL تُعاد null)
     */
    private function parseDate($value): ?\DateTimeInterface
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = (string) $value;
        if (str_starts_with($value, '0000-00-00') || str_contains($value, '-0001-') || str_starts_with($value, '-')) {
            return null;
        }
        try {
            $dt = new \DateTime($value);
            $y = (int) $dt->format('Y');
            if ($y < 1000 || $y > 9999) {
                return null;
            }
            return $dt;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * توحيد مفاتيح بيانات العميل (WHMCS قد يرجع أول حرف كبير)
     */
    private function normalizeCustomerData(array $data): array
    {
        $map = [
            'ID' => 'id', 'Firstname' => 'firstname', 'Lastname' => 'lastname',
            'Email' => 'email', 'CompanyName' => 'companyname', 'Address1' => 'address1',
            'Address2' => 'address2', 'City' => 'city', 'State' => 'state',
            'Postcode' => 'postcode', 'Country' => 'country', 'PhoneNumber' => 'phonenumber',
            'Currency' => 'currency', 'GroupId' => 'groupid', 'Status' => 'status',
            'Notes' => 'notes', 'LastLogin' => 'lastlogin', 'DateCreated' => 'datecreated',
        ];
        $out = [];
        foreach ($data as $key => $value) {
            $out[$map[$key] ?? strtolower($key)] = $value;
        }
        return $out;
    }

    /**
     * الحصول على تفاصيل عميل محدد
     *
     * @param int $clientId
     * @return array
     */
    public function getCustomerDetails($clientId)
    {
        $cacheKey = "whmcs_customer_details_{$clientId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($clientId) {
            $response = $this->request('GetClientsDetails', [
                'clientid' => $clientId,
            ]);
            
            if ($response['success']) {
                return $response['data'];
            }
            
            return null;
        });
    }

    /**
     * إضافة عميل جديد
     *
     * @param array $data
     * @return array
     */
    public function addCustomer($data)
    {
        $response = $this->request('AddClient', [
            'firstname' => $data['firstname'] ?? '',
            'lastname' => $data['lastname'] ?? '',
            'email' => $data['email'],
            'companyname' => $data['companyname'] ?? '',
            'address1' => $data['address1'] ?? '',
            'address2' => $data['address2'] ?? '',
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'postcode' => $data['postcode'] ?? '',
            'country' => $data['country'] ?? '',
            'phonenumber' => $data['phonenumber'] ?? '',
            'password2' => $data['password'],
            'currency' => $data['currency'] ?? 1,
            'groupid' => $data['groupid'] ?? 1,
            'notes' => $data['notes'] ?? '',
        ]);
        
        if ($response['success']) {
            // مسح ذاكرة التخزين المؤقت للعملاء
            Cache::forget('whmcs_customers_page_1_limit_25');
        }
        
        return $response;
    }

    /**
     * تحديث بيانات عميل
     *
     * @param int $clientId
     * @param array $data
     * @return array
     */
    public function updateCustomer($clientId, $data)
    {
        $params = ['clientid' => $clientId];
        
        // إعداد المعلمات القابلة للتحديث
        $updatableFields = [
            'firstname', 'lastname', 'companyname', 'email', 'address1', 'address2',
            'city', 'state', 'postcode', 'country', 'phonenumber', 'notes'
        ];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $params[$field] = $data[$field];
            }
        }
        
        $response = $this->request('UpdateClient', $params);
        
        if ($response['success']) {
            // مسح ذاكرة التخزين المؤقت للعميل
            Cache::forget("whmcs_customer_details_{$clientId}");
            Cache::forget('whmcs_customers_page_1_limit_25');
        }
        
        return $response;
    }

    /**
     * الحصول على مجموعات العملاء
     */
    public function getClientGroups(): array
    {
        $response = $this->request('GetClientGroups', []);
        if ($response['success'] && isset($response['data']['groups']['group'])) {
            $g = $response['data']['groups']['group'];
            return isset($g[0]) ? $g : [$g];
        }
        return [];
    }

    /**
     * الحصول على رصيد العميل (Credits)
     */
    public function getCredits(int $clientId): array
    {
        $response = $this->request('GetCredits', ['clientid' => $clientId]);
        if ($response['success']) {
            return $response['data'] ?? [];
        }
        return [];
    }

    /**
     * إضافة رصيد للعميل
     */
    public function addCredit(int $clientId, float $amount, string $description = '', int $type = 1): array
    {
        return $this->request('AddCredit', [
            'clientid' => $clientId,
            'amount' => $amount,
            'description' => $description,
            'type' => $type,
        ]);
    }

    /**
     * تطبيق رصيد على فاتورة أو حساب
     */
    public function applyCredit(int $clientId, float $amount, ?int $invoiceId = null): array
    {
        $params = ['clientid' => $clientId, 'amount' => $amount];
        if ($invoiceId) {
            $params['invoiceid'] = $invoiceId;
        }
        return $this->request('ApplyCredit', $params);
    }

    /**
     * إغلاق حساب العميل
     */
    public function closeClient(int $clientId): array
    {
        $response = $this->request('CloseClient', ['clientid' => $clientId]);
        if ($response['success']) {
            Cache::forget("whmcs_customer_details_{$clientId}");
            Cache::forget('whmcs_customers_page_1_limit_25');
        }
        return $response;
    }

    /**
     * حذف عميل من WHMCS
     */
    public function deleteClient(int $clientId): array
    {
        $response = $this->request('DeleteClient', ['clientid' => $clientId]);
        if ($response['success']) {
            Cache::forget("whmcs_customer_details_{$clientId}");
            Cache::forget('whmcs_customers_page_1_limit_25');
        }
        return $response;
    }

    /**
     * إعادة تعيين كلمة مرور العميل
     */
    public function resetClientPassword(int $clientId, string $newPassword): array
    {
        return $this->request('ResetPassword', [
            'clientid' => $clientId,
            'password2' => $newPassword,
        ]);
    }

    /**
     * الحصول على منتجات/خدمات عميل (Client Products/Services)
     */
    public function getClientsProducts(int $clientId, int $limit = 500, int $offset = 0): array
    {
        $response = $this->request('GetClientsProducts', [
            'clientid' => $clientId,
            'limitnum' => $limit,
            'limitstart' => $offset,
        ]);
        if (! $response['success']) {
            Log::info('WHMCS GetClientsProducts failed', [
                'clientid' => $clientId,
                'message' => $response['message'] ?? 'unknown',
            ]);
            return [];
        }
        $data = $response['data'] ?? [];
        $totalResults = $data['totalresults'] ?? $data['numreturned'] ?? null;
        $products = $data['products']['product'] ?? $data['products'] ?? $data['product'] ?? null;
        if ($products === null || ! is_array($products) || empty($products)) {
            Log::info('WHMCS GetClientsProducts: استجابة فارغة أو شكل غير متوقع', [
                'clientid' => $clientId,
                'response_keys' => array_keys($data),
                'totalresults' => $totalResults,
            ]);
            return [];
        }
        // استجابة WHMCS: عنصر واحد ككائن، أكثر من واحد كمصفوفة
        return isset($products[0]) ? $products : [$products];
    }

    /**
     * الحصول على إضافات خدمات العميل
     */
    public function getClientsAddons(int $clientId): array
    {
        $response = $this->request('GetClientsAddons', ['clientid' => $clientId]);
        if ($response['success'] && isset($response['data']['addons']['addon'])) {
            $a = $response['data']['addons']['addon'];
            return is_array($a) && isset($a[0]) ? $a : [$a];
        }
        return [];
    }

    /**
     * تحديث خدمة/منتج عميل
     */
    public function updateClientProduct(int $serviceId, array $params): array
    {
        $params['serviceid'] = $serviceId;
        return $this->request('UpdateClientProduct', $params);
    }

    /**
     * تعليق خدمة (ModuleSuspend)
     */
    public function moduleSuspend(int $serviceId, ?string $reason = null): array
    {
        $params = ['serviceid' => $serviceId];
        if ($reason !== null && $reason !== '') {
            $params['suspendreason'] = $reason;
        }
        return $this->request('ModuleSuspend', $params);
    }

    /**
     * إلغاء تعليق خدمة (ModuleUnsuspend)
     */
    public function moduleUnsuspend(int $serviceId): array
    {
        return $this->request('ModuleUnsuspend', ['serviceid' => $serviceId]);
    }

    /**
     * إنهاء خدمة نهائياً (ModuleTerminate)
     */
    public function moduleTerminate(int $serviceId): array
    {
        return $this->request('ModuleTerminate', ['serviceid' => $serviceId]);
    }

    /**
     * الحصول على جهات اتصال العميل
     */
    public function getContacts(int $clientId, int $limit = 100, int $offset = 0): array
    {
        $response = $this->request('GetContacts', [
            'userid' => $clientId,
            'limitnum' => $limit,
            'limitstart' => $offset,
        ]);
        if ($response['success'] && isset($response['data']['contacts']['contact'])) {
            $c = $response['data']['contacts']['contact'];
            return is_array($c) && isset($c[0]) ? $c : [$c];
        }
        return [];
    }

    /**
     * إضافة جهة اتصال
     */
    public function addContact(array $params): array
    {
        return $this->request('AddContact', $params);
    }

    /**
     * تحديث جهة اتصال
     */
    public function updateContact(int $contactId, array $params): array
    {
        $params['contactid'] = $contactId;
        return $this->request('UpdateContact', $params);
    }

    /**
     * حذف جهة اتصال
     */
    public function deleteContact(int $contactId): array
    {
        return $this->request('DeleteContact', ['contactid' => $contactId]);
    }

    /**
     * الحصول على قائمة المنتجات
     *
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function getProducts($limit = 25, $page = 1)
    {
        $cacheKey = "whmcs_products_page_{$page}_limit_{$limit}";
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($limit, $page) {
            $response = $this->request('GetProducts', [
                'limitnum' => $limit,
                'page' => $page,
            ]);
            
            if ($response['success']) {
                $raw = $response['data']['products']['product'] ?? [];
                return $this->normalizeProductList($raw);
            }
            
            return [];
        });
    }

    /**
     * تحويل استجابة المنتجات إلى مصفوفة (WHMCS قد يرجع منتجاً واحداً ككائن)
     */
    private function normalizeProductList($product): array
    {
        if (! is_array($product)) {
            return [];
        }
        return isset($product[0]) ? $product : [$product];
    }

    /**
     * تحويل قيمة من WHMCS (قد تكون "true"/"false" أو 1/0) إلى boolean لاستخدامها في الحقول المنطقية
     */
    private function toBool($value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * الحصول على قائمة الفواتير
     *
     * @param int $limit
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getInvoices($limit = 25, $page = 1, $filters = [])
    {
        $cacheKey = "whmcs_invoices_page_{$page}_limit_{$limit}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($limit, $page, $filters) {
            $params = [
                'limitnum' => $limit,
                'page' => $page,
            ];
            
            // إضافة الفلاتر
            if (!empty($filters)) {
                $params = array_merge($params, $filters);
            }
            
            $response = $this->request('GetInvoices', $params);
            
            if ($response['success']) {
                return $response['data']['invoices']['invoice'] ?? [];
            }
            
            return [];
        });
    }

    /**
     * الحصول على تفاصيل فاتورة محددة
     *
     * @param int $invoiceId
     * @return array
     */
    public function getInvoiceDetails($invoiceId)
    {
        $cacheKey = "whmcs_invoice_details_{$invoiceId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($invoiceId) {
            $response = $this->request('GetInvoice', [
                'invoiceid' => $invoiceId,
            ]);
            
            if ($response['success']) {
                return $response['data'];
            }
            
            return null;
        });
    }

    /**
     * إنشاء فاتورة في WHMCS (CreateInvoice)
     * المعاملات: userid, date, duedate, paymentmethod, notes, itemdescription[], itemamount[], itemtaxed[]
     *
     * @return array ['success' => bool, 'invoiceid' => int|null, 'message' => string]
     */
    public function createInvoice(array $params): array
    {
        $response = $this->request('CreateInvoice', $params);
        if ($response['success'] && isset($response['data']['id'])) {
            return ['success' => true, 'invoiceid' => (int) $response['data']['id'], 'data' => $response['data']];
        }
        return ['success' => false, 'invoiceid' => null, 'message' => $response['message'] ?? 'Unknown error'];
    }

    /**
     * تحديث فاتورة (UpdateInvoice)
     */
    public function updateInvoice(int $invoiceId, array $params): array
    {
        $params['invoiceid'] = $invoiceId;
        $response = $this->request('UpdateInvoice', $params);
        if ($response['success']) {
            Cache::forget("whmcs_invoice_details_{$invoiceId}");
        }
        return $response;
    }

    /**
     * تسجيل دفعة على فاتورة (AddInvoicePayment)
     */
    public function addInvoicePayment(int $invoiceId, float $amount, string $transId = '', string $gateway = '', ?string $date = null): array
    {
        $params = [
            'invoiceid' => $invoiceId,
            'amount' => $amount,
            'transid' => $transId,
            'gateway' => $gateway,
        ];
        if ($date) {
            $params['date'] = $date;
        }
        $response = $this->request('AddInvoicePayment', $params);
        if ($response['success']) {
            Cache::forget("whmcs_invoice_details_{$invoiceId}");
        }
        return $response;
    }

    /**
     * توليد الفواتير المستحقة (GenInvoices)
     */
    public function genInvoices(?int $userId = null): array
    {
        $params = [];
        if ($userId !== null) {
            $params['userid'] = $userId;
        }
        return $this->request('GenInvoices', $params);
    }

    /**
     * الحصول على قائمة التذاكر
     *
     * @param int $limit
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getTickets($limit = 25, $page = 1, $filters = [])
    {
        $cacheKey = "whmcs_tickets_page_{$page}_limit_{$limit}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($limit, $page, $filters) {
            $params = [
                'limitnum' => $limit,
                'page' => $page,
            ];
            
            // إضافة الفلاتر
            if (!empty($filters)) {
                $params = array_merge($params, $filters);
            }
            
            $response = $this->request('GetTickets', $params);
            
            if ($response['success']) {
                return $response['data']['tickets']['ticket'] ?? [];
            }
            
            return [];
        });
    }

    /**
     * الحصول على تفاصيل تذكرة محددة
     *
     * @param int $ticketId
     * @return array
     */
    public function getTicketDetails($ticketId)
    {
        $cacheKey = "whmcs_ticket_details_{$ticketId}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($ticketId) {
            $response = $this->request('GetTicket', [
                'ticketid' => $ticketId,
            ]);
            
            if ($response['success']) {
                return $response['data'];
            }
            
            return null;
        });
    }

    /**
     * إضافة رد على تذكرة
     *
     * @param int $ticketId
     * @param string $message
     * @return array
     */
    public function addTicketReply($ticketId, $message)
    {
        $response = $this->request('AddTicketReply', [
            'ticketid' => $ticketId,
            'message' => $message,
        ]);
        
        if ($response['success']) {
            // مسح ذاكرة التخزين المؤقت للتذكرة
            Cache::forget("whmcs_ticket_details_{$ticketId}");
            Cache::forget('whmcs_tickets_page_1_limit_25');
        }
        
        return $response;
    }

    /**
     * تحديث حالة التذكرة
     *
     * @param int $ticketId
     * @param string $status
     * @return array
     */
    public function updateTicketStatus($ticketId, $status)
    {
        $response = $this->request('UpdateTicket', [
            'ticketid' => $ticketId,
            'status' => $status,
        ]);
        
        if ($response['success']) {
            // مسح ذاكرة التخزين المؤقت للتذكرة
            Cache::forget("whmcs_ticket_details_{$ticketId}");
            Cache::forget('whmcs_tickets_page_1_limit_25');
        }
        
        return $response;
    }

    /**
     * إضافة ملاحظة داخلية على التذكرة (AddTicketNote)
     */
    public function addTicketNote(int $ticketId, string $message, bool $staffVisible = true): array
    {
        $response = $this->request('AddTicketNote', [
            'ticketid' => $ticketId,
            'message' => $message,
            'staffonly' => $staffVisible ? 'on' : '',
        ]);
        if ($response['success']) {
            Cache::forget("whmcs_ticket_details_{$ticketId}");
        }
        return $response;
    }

    /**
     * الحصول على ملاحظات التذكرة (GetTicketNotes)
     */
    public function getTicketNotes(int $ticketId): array
    {
        $response = $this->request('GetTicketNotes', ['ticketid' => $ticketId]);
        if ($response['success'] && isset($response['data']['notes']['note'])) {
            $n = $response['data']['notes']['note'];
            return is_array($n) && isset($n[0]) ? $n : [$n];
        }
        return [];
    }

    /**
     * فتح تذكرة (OpenTicket) - إعادة فتح تذكرة مغلقة
     */
    public function openTicket(int $ticketId): array
    {
        $response = $this->request('OpenTicket', ['ticketid' => $ticketId]);
        if ($response['success']) {
            Cache::forget("whmcs_ticket_details_{$ticketId}");
        }
        return $response;
    }

    /**
     * دمج تذكرة في أخرى (MergeTicket)
     */
    public function mergeTicket(int $ticketId, int $mergeIntoId): array
    {
        $response = $this->request('MergeTicket', [
            'ticketid' => $ticketId,
            'mergeticketid' => $mergeIntoId,
        ]);
        if ($response['success']) {
            Cache::forget("whmcs_ticket_details_{$ticketId}");
            Cache::forget("whmcs_ticket_details_{$mergeIntoId}");
        }
        return $response;
    }

    /**
     * منع مرسل التذكرة من إرسال تذاكر (BlockTicketSender)
     */
    public function blockTicketSender(int $ticketId): array
    {
        return $this->request('BlockTicketSender', ['ticketid' => $ticketId]);
    }

    /**
     * الحصول على أقسام الدعم (GetSupportDepartments)
     */
    public function getSupportDepartments(): array
    {
        $response = $this->request('GetSupportDepartments', []);
        if ($response['success'] && isset($response['data']['departments']['department'])) {
            $d = $response['data']['departments']['department'];
            return is_array($d) && isset($d[0]) ? $d : [$d];
        }
        return [];
    }

    /**
     * الحصول على حالات التذاكر (GetSupportStatuses)
     */
    public function getSupportStatuses(): array
    {
        $response = $this->request('GetSupportStatuses', []);
        if ($response['success'] && isset($response['data']['statuses']['status'])) {
            $s = $response['data']['statuses']['status'];
            return is_array($s) && isset($s[0]) ? $s : [$s];
        }
        return [];
    }

    /**
     * الحصول على التصنيفات المحددة مسبقاً للتذاكر (GetTicketPredefinedCats)
     */
    public function getTicketPredefinedCats(): array
    {
        $response = $this->request('GetTicketPredefinedCats', []);
        if ($response['success'] && isset($response['data']['categories']['category'])) {
            $c = $response['data']['categories']['category'];
            return is_array($c) && isset($c[0]) ? $c : [$c];
        }
        return [];
    }

    /**
     * الحصول على الردود الجاهزة (GetTicketPredefinedReplies)
     */
    public function getTicketPredefinedReplies(): array
    {
        $response = $this->request('GetTicketPredefinedReplies', []);
        if ($response['success'] && isset($response['data']['predefinedreplies']['predefinedreply'])) {
            $r = $response['data']['predefinedreplies']['predefinedreply'];
            return is_array($r) && isset($r[0]) ? $r : [$r];
        }
        return [];
    }

    /**
     * مزامنة عميل واحد من WHMCS (تحديث بياناته المحلية من GetClientsDetails)
     *
     * @param \App\Models\Customer $customer
     * @return void
     */
    public function syncCustomer(\App\Models\Customer $customer): void
    {
        if (! $customer->whmcs_id) {
            return;
        }
        $response = $this->getCustomerDetails($customer->whmcs_id);
        if (empty($response)) {
            return;
        }
        $data = $this->normalizeCustomerData($response);
        $customer->update([
            'firstname' => $data['firstname'] ?? $customer->firstname,
            'lastname' => $data['lastname'] ?? $customer->lastname,
            'fullname' => trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? '')),
            'email' => $data['email'] ?? $customer->email,
            'companyname' => $data['companyname'] ?? $customer->companyname,
            'address1' => $data['address1'] ?? $customer->address1,
            'address2' => $data['address2'] ?? $customer->address2,
            'city' => $data['city'] ?? $customer->city,
            'state' => $data['state'] ?? $customer->state,
            'postcode' => $data['postcode'] ?? $customer->postcode,
            'country' => $data['country'] ?? $customer->country,
            'phonenumber' => $data['phonenumber'] ?? $customer->phonenumber,
            'status' => $data['status'] ?? $customer->status,
            'notes' => $data['notes'] ?? $customer->notes,
            'last_login' => $this->parseDate($data['lastlogin'] ?? null) ?? $customer->last_login,
            'date_created' => $this->parseDate($data['datecreated'] ?? null) ?? $customer->date_created,
            'synced_at' => now(),
        ]);
    }

    /**
     * مزامنة منتجات/خدمات عميل واحد من WHMCS (GetClientsProducts → customer_products)
     *
     * @param \App\Models\Customer $customer
     * @return int عدد السجلات المحدثة أو المضافة
     */
    public function syncCustomerProducts(\App\Models\Customer $customer): int
    {
        Log::info('syncCustomerProducts: بدء', ['customer_id' => $customer->id, 'whmcs_id' => $customer->whmcs_id]);
        if (! $customer->whmcs_id) {
            return 0;
        }
        $items = $this->getClientsProducts($customer->whmcs_id);
        Log::info('GetClientsProducts: عدد العناصر المرجعة', ['count' => count($items), 'whmcs_client_id' => $customer->whmcs_id]);
        if (empty($items)) {
            Log::info('WHMCS syncCustomerProducts: no products returned', [
                'customer_id' => $customer->id,
                'whmcs_id' => $customer->whmcs_id,
            ]);
        }
        $count = 0;
        foreach ($items as $row) {
            $pid = (int) ($row['pid'] ?? $row['productid'] ?? 0);
            $serviceId = (int) ($row['id'] ?? $row['serviceid'] ?? $row['service_id'] ?? 0);
            if ($serviceId <= 0) {
                Log::warning('syncCustomerProducts: تخطي عنصر بسبب عدم وجود معرف خدمة', [
                    'row_keys' => array_keys(is_array($row) ? $row : []),
                    'pid' => $pid,
                ]);
                continue;
            }
            try {
                // إن لم يكن المنتج موجوداً في الكتالوج المحلي، إنشاء سجل افتراضي من بيانات الخدمة حتى تظهر خدمات العميل
                $productName = trim(($row['name'] ?? '') . ($row['domain'] ? ' - ' . $row['domain'] : ''));
                if ($productName === '') {
                    $productName = 'منتج #' . $pid . ' - ' . ($row['domain'] ?? 'خدمة');
                }
                $product = \App\Models\Product::firstOrCreate(
                    ['whmcs_id' => $pid],
                    [
                        'type' => $row['type'] ?? 'hostingaccount',
                        'gid' => (int) ($row['gid'] ?? 1),
                        'name' => $productName,
                        'description' => '',
                        'paytype' => 'recurring',
                        'pricing' => [],
                        'currency' => 1,
                        'showdomainoptions' => false,
                        'stockcontrol' => false,
                        'qty' => 0,
                        'status' => 'Active',
                    ]
                );
                $regdate = $this->parseDate($row['regdate'] ?? null);
                $nextduedate = $this->parseDate($row['nextduedate'] ?? null);
                $nextinvoicedate = $this->parseDate($row['nextinvoicedate'] ?? null);
                $terminationdate = $this->parseDate($row['terminationdate'] ?? $row['termination_date'] ?? null);
                $completed = $this->parseDate($row['completed_date'] ?? null);
                $overidesuspenduntil = $this->parseDate($row['overidesuspenduntil'] ?? null);
                $lastupdate = $this->parseDate($row['lastupdate'] ?? null);
                \App\Models\CustomerProduct::withTrashed()->updateOrCreate(
                    ['whmcs_service_id' => $serviceId],
                    [
                        'customer_id' => $customer->id,
                        'product_id' => $product->id,
                        'orderid' => $row['orderid'] ?? null,
                        'regdate' => $regdate ?? now(),
                        'domain' => $row['domain'] ?? null,
                        'paymentmethod' => $row['paymentmethod'] ?? null,
                        'firstpaymentamount' => (float) ($row['firstpaymentamount'] ?? 0),
                        'amount' => (float) ($row['amount'] ?? $row['recurringamount'] ?? 0),
                        'billingcycle' => $row['billingcycle'] ?? 'Monthly',
                        'nextduedate' => $nextduedate,
                        'nextinvoicedate' => $nextinvoicedate,
                        'termination_date' => $terminationdate,
                        'completed_date' => $completed,
                        'domainstatus' => $row['domainstatus'] ?? 'Pending',
                        'username' => $row['username'] ?? null,
                        'password' => $row['password'] ?? null,
                        'notes' => $row['notes'] ?? null,
                        'subscriptionid' => $row['subscriptionid'] ?? null,
                        'promoid' => isset($row['promoid']) ? (int) $row['promoid'] : null,
                        'overideautosuspend' => ! empty($row['overideautosuspend']),
                        'overidesuspenduntil' => $overidesuspenduntil,
                        'lastupdate' => $lastupdate,
                        'synced_at' => now(),
                        'deleted_at' => null,
                    ]
                );
                $count++;
            } catch (\Throwable $e) {
                Log::error('syncCustomerProducts: خطأ عند معالجة عنصر', [
                    'service_id' => $serviceId,
                    'pid' => $pid,
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
        return $count;
    }

    /**
     * مزامنة جهات اتصال عميل واحد من WHMCS
     *
     * @param \App\Models\Customer $customer
     * @return int
     */
    public function syncCustomerContacts(\App\Models\Customer $customer): int
    {
        if (! $customer->whmcs_id) {
            return 0;
        }
        $items = $this->getContacts($customer->whmcs_id);
        $count = 0;
        foreach ($items as $row) {
            $contactId = (int) ($row['id'] ?? 0);
            if ($contactId <= 0) {
                continue;
            }
            \App\Models\Contact::updateOrCreate(
                ['whmcs_id' => $contactId],
                [
                    'customer_id' => $customer->id,
                    'firstname' => $row['firstname'] ?? null,
                    'lastname' => $row['lastname'] ?? null,
                    'companyname' => $row['companyname'] ?? null,
                    'email' => $row['email'] ?? null,
                    'address1' => $row['address1'] ?? null,
                    'address2' => $row['address2'] ?? null,
                    'city' => $row['city'] ?? null,
                    'state' => $row['state'] ?? null,
                    'postcode' => $row['postcode'] ?? null,
                    'country' => $row['country'] ?? null,
                    'phonenumber' => $row['phonenumber'] ?? null,
                    'generalemails' => ! empty($row['generalemails']),
                    'productemails' => ! empty($row['productemails']),
                    'domainemails' => ! empty($row['domainemails']),
                    'invoiceemails' => ! empty($row['invoiceemails']),
                    'supportemails' => ! empty($row['supportemails']),
                    'affiliateemails' => ! empty($row['affiliateemails']),
                    'synced_at' => now(),
                ]
            );
            $count++;
        }
        return $count;
    }

    /**
     * مزامنة العملاء مع WHMCS
     *
     * @return int
     */
    public function syncCustomers()
    {
        $count = 0;
        $page = 1;
        $limit = 100;

        for ($p = 1; $p <= 10; $p++) {
            Cache::forget("whmcs_customers_page_{$p}_limit_{$limit}");
        }

        do {
            $customers = $this->getCustomers($limit, $page);
            
            if (empty($customers)) {
                break;
            }
            
            foreach ($customers as $customerData) {
                $customerData = $this->normalizeCustomerData($customerData);
                \App\Models\Customer::updateOrCreate(
                    ['whmcs_id' => $customerData['id']],
                    [
                        'firstname' => $customerData['firstname'] ?? '',
                        'lastname' => $customerData['lastname'] ?? '',
                        'fullname' => trim(($customerData['firstname'] ?? '') . ' ' . ($customerData['lastname'] ?? '')),
                        'email' => $customerData['email'] ?? '',
                        'companyname' => $customerData['companyname'] ?? '',
                        'address1' => $customerData['address1'] ?? '',
                        'address2' => $customerData['address2'] ?? '',
                        'city' => $customerData['city'] ?? '',
                        'state' => $customerData['state'] ?? '',
                        'postcode' => $customerData['postcode'] ?? '',
                        'country' => $customerData['country'] ?? 'US',
                        'phonenumber' => $customerData['phonenumber'] ?? '',
                        'currency' => (int) ($customerData['currency'] ?? 1),
                        'groupid' => (int) ($customerData['groupid'] ?? 1),
                        'status' => $customerData['status'] ?? 'Active',
                        'notes' => $customerData['notes'] ?? '',
                        'last_login' => $this->parseDate($customerData['lastlogin'] ?? null),
                        'date_created' => $this->parseDate($customerData['datecreated'] ?? null),
                        'synced_at' => now(),
                    ]
                );
                $count++;
            }
            
            $page++;
        } while (count($customers) == $limit);
        
        return $count;
    }
    
    /**
     * مزامنة منتج واحد من WHMCS (جلب بياناته حسب whmcs_id وتحديث السجل المحلي)
     *
     * @param \App\Models\Product $product
     * @return void
     */
    public function syncProduct(\App\Models\Product $product): void
    {
        $pid = (int) $product->whmcs_id;
        if ($pid <= 0) {
            return;
        }
        $response = $this->request('GetProducts', ['pid' => $pid]);
        if (! $response['success'] || empty($response['data']['products']['product'])) {
            return;
        }
        $raw = $response['data']['products']['product'];
        $list = $this->normalizeProductList($raw);
        if (empty($list)) {
            return;
        }
        $productData = $list[0];
        $product->update([
            'type' => $productData['type'] ?? 'hostingaccount',
            'gid' => (int) ($productData['gid'] ?? 1),
            'name' => $productData['name'] ?? $product->name,
            'description' => $productData['description'] ?? $product->description,
            'paytype' => $productData['paytype'] ?? 'recurring',
            'pricing' => is_array($productData['pricing'] ?? null) ? $productData['pricing'] : (is_object($productData['pricing'] ?? null) ? (array) $productData['pricing'] : $product->pricing ?? []),
            'currency' => (int) ($productData['currency'] ?? $product->currency),
            'showdomainoptions' => $this->toBool($productData['showdomainoptions'] ?? null, false),
            'stockcontrol' => $this->toBool($productData['stockcontrol'] ?? null, false),
            'qty' => (int) ($productData['qty'] ?? 0),
            'prorata' => $this->toBool($productData['prorata'] ?? null, false),
            'proratadate' => $productData['proratadate'] ?? null,
            'proratachargenextmonth' => $this->toBool($productData['proratachargenextmonth'] ?? null, false),
            'hidden' => $this->toBool($productData['hidden'] ?? null, false),
            'tax' => $this->toBool($productData['tax'] ?? null, true),
            'allowqty' => $this->toBool($productData['allowqty'] ?? null, false),
            'recurring' => $this->toBool($productData['recurring'] ?? null, true),
            'autoterminate' => $this->toBool($productData['autoterminate'] ?? null, true),
            'autorenew' => $this->toBool($productData['autorenew'] ?? null, true),
            'servertype' => $productData['servertype'] ?? null,
            'servergroup' => $productData['servergroup'] ?? null,
            'configoption1' => $productData['configoption1'] ?? null,
            'configoption2' => $productData['configoption2'] ?? null,
            'configoption3' => $productData['configoption3'] ?? null,
            'configoption4' => $productData['configoption4'] ?? null,
            'configoption5' => $productData['configoption5'] ?? null,
            'configoption6' => $productData['configoption6'] ?? null,
            'configoption7' => $productData['configoption7'] ?? null,
            'configoption8' => $productData['configoption8'] ?? null,
            'configoption9' => $productData['configoption9'] ?? null,
            'configoption10' => $productData['configoption10'] ?? null,
            'configoption11' => $productData['configoption11'] ?? null,
            'configoption12' => $productData['configoption12'] ?? null,
            'configoption13' => $productData['configoption13'] ?? null,
            'configoption14' => $productData['configoption14'] ?? null,
            'configoption15' => $productData['configoption15'] ?? null,
            'configoption16' => $productData['configoption16'] ?? null,
            'configoption17' => $productData['configoption17'] ?? null,
            'configoption18' => $productData['configoption18'] ?? null,
            'configoption19' => $productData['configoption19'] ?? null,
            'configoption20' => $productData['configoption20'] ?? null,
            'configoption21' => $productData['configoption21'] ?? null,
            'configoption22' => $productData['configoption22'] ?? null,
            'configoption23' => $productData['configoption23'] ?? null,
            'configoption24' => $productData['configoption24'] ?? null,
            'synced_at' => now(),
        ]);
    }

    /**
     * إضافة منتج في WHMCS (AddProduct)
     * المعاملات: name, type, gid, description, paytype, pricing (مصفوفة حسب عملة، مثل USD => [monthly, quarterly, ...])
     *
     * @param array $data
     * @return array ['success' => bool, 'pid' => int|null, 'message' => string]
     */
    public function addProduct(array $data): array
    {
        $params = [
            'name' => $data['name'] ?? '',
            'type' => $data['type'] ?? 'hostingaccount',
            'gid' => (int) ($data['gid'] ?? 1),
            'description' => $data['description'] ?? '',
            'paytype' => $data['paytype'] ?? 'recurring',
        ];
        $pricing = $data['pricing'] ?? [];
        if (is_array($pricing)) {
            foreach ($pricing as $currencyCode => $cycles) {
                if (! is_array($cycles)) {
                    continue;
                }
                foreach ($cycles as $key => $value) {
                    if ($value !== '' && $value !== null) {
                        $params[$key] = $value;
                    }
                }
                break;
            }
        }
        $response = $this->request('AddProduct', $params);
        if ($response['success'] && isset($response['data']['pid'])) {
            $pid = (int) $response['data']['pid'];
            Cache::forget('whmcs_products_page_1_limit_25');
            return ['success' => true, 'pid' => $pid];
        }
        return [
            'success' => false,
            'pid' => null,
            'message' => $response['message'] ?? 'Unknown error',
        ];
    }

    /**
     * تحديث منتج في WHMCS (UpdateProduct)
     *
     * @param int|string $pid معرف المنتج في WHMCS
     * @param array $data الحقول القابلة للتحديث: name, type, gid, description, paytype, pricing, ...
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateProduct($pid, array $data): array
    {
        $params = ['pid' => (int) $pid];
        $allowed = ['name', 'type', 'gid', 'description', 'paytype', 'status', 'hidden'];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $params[$key] = $data[$key];
            }
        }
        $pricing = $data['pricing'] ?? null;
        if (is_array($pricing)) {
            foreach ($pricing as $currencyCode => $cycles) {
                if (is_array($cycles)) {
                    foreach ($cycles as $k => $v) {
                        if ($v !== '' && $v !== null) {
                            $params[$k] = $v;
                        }
                    }
                    break;
                }
            }
        }
        $response = $this->request('UpdateProduct', $params);
        if ($response['success']) {
            Cache::forget('whmcs_products_page_1_limit_25');
        }
        return $response;
    }

    /**
     * حذف منتج من WHMCS (DeleteProduct إن وُجد). إن لم يكن مدعوماً من الواجهة يُعاد success=false ويُترك الحذف للمحلي فقط.
     *
     * @param int|string $pid
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteProduct($pid): array
    {
        if (! static::isActionAllowed('DeleteProduct')) {
            return ['success' => false, 'message' => 'Action not allowed: DeleteProduct'];
        }
        $response = $this->request('DeleteProduct', ['pid' => (int) $pid]);
        if ($response['success']) {
            Cache::forget('whmcs_products_page_1_limit_25');
        }
        return $response;
    }

    /**
     * إنشاء طلب في WHMCS (AddOrder)
     * المعاملات: clientid, pid, billingcycle (Monthly, Quarterly, Semi-Annually, Annually, Biennially, Triennially), paymentmethod اختياري، noemail اختياري
     *
     * @param array $params
     * @return array ['success' => bool, 'orderid' => int|null, 'message' => string]
     */
    public function addOrder(array $params): array
    {
        $billingCycleMap = [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semiannually' => 'Semi-Annually',
            'annually' => 'Annually',
            'biennially' => 'Biennially',
            'triennially' => 'Triennially',
        ];
        $billingCycle = $params['billingcycle'] ?? $params['billing_cycle'] ?? 'Monthly';
        if (is_string($billingCycle) && isset($billingCycleMap[strtolower($billingCycle)])) {
            $billingCycle = $billingCycleMap[strtolower($billingCycle)];
        }
        $requestParams = [
            'clientid' => (int) ($params['clientid'] ?? 0),
            'pid' => [(int) ($params['pid'] ?? 0)],
            'billingcycle' => [$billingCycle],
            'paymentmethod' => $params['paymentmethod'] ?? '',
            'noemail' => ! empty($params['noemail']),
        ];
        $response = $this->request('AddOrder', $requestParams);
        $orderId = $response['data']['orderid'] ?? $response['data']['id'] ?? null;
        if ($response['success'] && $orderId !== null) {
            return [
                'success' => true,
                'orderid' => (int) $orderId,
                'message' => $response['message'] ?? '',
            ];
        }
        return [
            'success' => false,
            'orderid' => null,
            'message' => $response['message'] ?? 'Unknown error',
        ];
    }

    /**
     * التحقق من توفر النطاق (DomainWhois)
     *
     * @param string $domain النطاق الكامل مثل example.com
     * @return array ['success' => bool, 'status' => 'available'|'unavailable', 'whois' => string, 'message' => string]
     */
    public function domainWhois(string $domain): array
    {
        $domain = trim($domain);
        if ($domain === '') {
            return ['success' => false, 'status' => 'unavailable', 'whois' => '', 'message' => 'Domain is required'];
        }
        $response = $this->request('DomainWhois', ['domain' => $domain]);
        if ($response['success']) {
            $status = $response['data']['status'] ?? 'unavailable';
            $whois = $response['data']['whois'] ?? '';
            return [
                'success' => true,
                'status' => $status === 'available' ? 'available' : 'unavailable',
                'whois' => $whois,
                'message' => '',
            ];
        }
        return [
            'success' => false,
            'status' => 'unavailable',
            'whois' => '',
            'message' => $response['message'] ?? 'Unknown error',
        ];
    }

    /**
     * جلب أسعار TLDs والخيارات (GetTLDPricing)
     *
     * @param int|null $currencyId معرف العملة (مثلاً 1 لـ USD)
     * @param int|null $clientId معرف العميل (يُفضّل على currencyId إن وُجد)
     * @return array ['success' => bool, 'currency' => array, 'pricing' => array, 'message' => string]
     */
    public function getTldPricing(?int $currencyId = null, ?int $clientId = null): array
    {
        $params = [];
        if ($clientId !== null && $clientId > 0) {
            $params['clientid'] = $clientId;
        } elseif ($currencyId !== null && $currencyId > 0) {
            $params['currencyid'] = $currencyId;
        } else {
            $params['currencyid'] = (int) config('whmcs.default_currency', 1);
        }
        $response = $this->request('GetTLDPricing', $params);
        if ($response['success']) {
            return [
                'success' => true,
                'currency' => $response['data']['currency'] ?? [],
                'pricing' => $response['data']['pricing'] ?? [],
                'message' => '',
            ];
        }
        return [
            'success' => false,
            'currency' => [],
            'pricing' => [],
            'message' => $response['message'] ?? 'Unknown error',
        ];
    }

    /**
     * تنسيق سعر من مصفوفة أسعار النطاق (مثلاً ['1' => '14.95']) مع بادئة العملة
     *
     * @param array $priceArray
     * @param string $currencySuffix مثل " USD" أو ""
     * @return string
     */
    public static function formatDomainPrice(array $priceArray, string $currencySuffix = ''): string
    {
        if (empty($priceArray) || ! is_array($priceArray)) {
            return '—';
        }
        $first = reset($priceArray);
        if (is_array($first)) {
            $first = reset($first);
        }
        if ($first === null || $first === false || $first === '') {
            return '—';
        }
        return (string) $first . $currencySuffix;
    }

    /**
     * مزامنة المنتجات مع WHMCS
     *
     * @return int
     */
    public function syncProducts()
    {
        $count = 0;
        $page = 1;
        $limit = 100;
        // مسح كاش المنتجات لضمان جلب بيانات حديثة من WHMCS
        for ($p = 1; $p <= 5; $p++) {
            Cache::forget("whmcs_products_page_{$p}_limit_{$limit}");
        }

        do {
            $products = $this->getProducts($limit, $page);
            
            if (empty($products)) {
                break;
            }
            
            foreach ($products as $productData) {
                $pid = (int) ($productData['pid'] ?? $productData['id'] ?? 0);
                if ($pid <= 0) {
                    continue;
                }
                $product = \App\Models\Product::updateOrCreate(
                    ['whmcs_id' => $pid],
                    [
                        'type' => $productData['type'] ?? 'hostingaccount',
                        'gid' => (int) ($productData['gid'] ?? 1),
                        'name' => $productData['name'] ?? 'منتج بدون اسم',
                        'description' => $productData['description'] ?? '',
                        'paytype' => $productData['paytype'] ?? 'recurring',
                        'pricing' => is_array($productData['pricing'] ?? null) ? $productData['pricing'] : (is_object($productData['pricing'] ?? null) ? (array) $productData['pricing'] : []),
                        'currency' => (int) ($productData['currency'] ?? 1),
                        'showdomainoptions' => $this->toBool($productData['showdomainoptions'] ?? null, false),
                        'stockcontrol' => $this->toBool($productData['stockcontrol'] ?? null, false),
                        'qty' => (int) ($productData['qty'] ?? 0),
                        'prorata' => $this->toBool($productData['prorata'] ?? null, false),
                        'proratadate' => $productData['proratadate'] ?? null,
                        'proratachargenextmonth' => $this->toBool($productData['proratachargenextmonth'] ?? null, false),
                        'hidden' => $this->toBool($productData['hidden'] ?? null, false),
                        'tax' => $this->toBool($productData['tax'] ?? null, true),
                        'allowqty' => $this->toBool($productData['allowqty'] ?? null, false),
                        'recurring' => $this->toBool($productData['recurring'] ?? null, true),
                        'autoterminate' => $this->toBool($productData['autoterminate'] ?? null, true),
                        'autorenew' => $this->toBool($productData['autorenew'] ?? null, true),
                        'servertype' => $productData['servertype'] ?? null,
                        'servergroup' => $productData['servergroup'] ?? null,
                        'configoption1' => $productData['configoption1'] ?? null,
                        'configoption2' => $productData['configoption2'] ?? null,
                        'configoption3' => $productData['configoption3'] ?? null,
                        'configoption4' => $productData['configoption4'] ?? null,
                        'configoption5' => $productData['configoption5'] ?? null,
                        'configoption6' => $productData['configoption6'] ?? null,
                        'configoption7' => $productData['configoption7'] ?? null,
                        'configoption8' => $productData['configoption8'] ?? null,
                        'configoption9' => $productData['configoption9'] ?? null,
                        'configoption10' => $productData['configoption10'] ?? null,
                        'configoption11' => $productData['configoption11'] ?? null,
                        'configoption12' => $productData['configoption12'] ?? null,
                        'configoption13' => $productData['configoption13'] ?? null,
                        'configoption14' => $productData['configoption14'] ?? null,
                        'configoption15' => $productData['configoption15'] ?? null,
                        'configoption16' => $productData['configoption16'] ?? null,
                        'configoption17' => $productData['configoption17'] ?? null,
                        'configoption18' => $productData['configoption18'] ?? null,
                        'configoption19' => $productData['configoption19'] ?? null,
                        'configoption20' => $productData['configoption20'] ?? null,
                        'configoption21' => $productData['configoption21'] ?? null,
                        'configoption22' => $productData['configoption22'] ?? null,
                        'configoption23' => $productData['configoption23'] ?? null,
                        'configoption24' => $productData['configoption24'] ?? null,
                        'status' => $productData['status'] ?? 'Active',
                        'product_group' => $productData['product_group'] ?? null,
                        'synced_at' => now(),
                    ]
                );
                
                $count++;
            }
            
            $page++;
        } while (count($products) == $limit);
        
        return $count;
    }
    
    /**
     * مزامنة الفواتير مع WHMCS
     *
     * @return int
     */
    public function syncInvoices()
    {
        $count = 0;
        $page = 1;
        $limit = 100;
        
        do {
            $invoices = $this->getInvoices($limit, $page);
            
            if (empty($invoices)) {
                break;
            }
            
            foreach ($invoices as $invoiceData) {
                $invoice = \App\Models\Invoice::updateOrCreate(
                    ['whmcs_id' => $invoiceData['id']],
                    [
                        'whmcs_client_id' => $invoiceData['userid'],
                        'invoicenum' => $invoiceData['invoicenum'] ?? (string) ($invoiceData['id'] ?? ''),
                        'date' => $invoiceData['date'] ? new \DateTime($invoiceData['date']) : now(),
                        'duedate' => $invoiceData['duedate'] ? new \DateTime($invoiceData['duedate']) : null,
                        'datepaid' => $invoiceData['datepaid'] ? new \DateTime($invoiceData['datepaid']) : null,
                        'subtotal' => $invoiceData['subtotal'] ?? 0,
                        'credit' => $invoiceData['credit'] ?? 0,
                        'tax' => $invoiceData['tax'] ?? 0,
                        'taxrate' => $invoiceData['taxrate'] ?? 0,
                        'taxrate2' => $invoiceData['taxrate2'] ?? 0,
                        'total' => $invoiceData['total'] ?? 0,
                        'status' => $invoiceData['status'] ?? 'Unpaid',
                        'paymentmethod' => $invoiceData['paymentmethod'] ?? '',
                        'notes' => $invoiceData['notes'] ?? '',
                        'synced_at' => now(),
                    ]
                );
                
                // مزامنة عناصر الفاتورة
                if (isset($invoiceData['items']['item'])) {
                    foreach ($invoiceData['items']['item'] as $itemData) {
                        \App\Models\InvoiceItem::updateOrCreate(
                            ['whmcs_id' => $itemData['id']],
                            [
                                'invoice_id' => $invoice->id,
                                'type' => $itemData['type'] ?? '',
                                'description' => $itemData['description'] ?? '',
                                'amount' => $itemData['amount'] ?? 0,
                                'taxed' => $itemData['taxed'] ?? 0,
                                'taxrate' => $itemData['taxrate'] ?? 0,
                            ]
                        );
                    }
                }
                
                $count++;
            }
            
            $page++;
        } while (count($invoices) == $limit);
        
        return $count;
    }
    
    /**
     * مزامنة التذاكر مع WHMCS
     *
     * @return int
     */
    public function syncTickets()
    {
        $count = 0;
        $page = 1;
        $limit = 100;
        
        do {
            $tickets = $this->getTickets($limit, $page);
            
            if (empty($tickets)) {
                break;
            }
            
            foreach ($tickets as $ticketData) {
                $ticket = \App\Models\Ticket::updateOrCreate(
                    ['whmcs_id' => $ticketData['id']],
                    [
                        'whmcs_client_id' => $ticketData['userid'],
                        'tid' => $ticketData['tid'] ?? null,
                        'deptid' => $ticketData['deptid'] ?? 1,
                        'userid' => $ticketData['userid'],
                        'name' => $ticketData['name'],
                        'email' => $ticketData['email'],
                        'subject' => $ticketData['subject'],
                        'message' => $ticketData['message'] ?? '',
                        'status' => $ticketData['status'] ?? 'Open',
                        'priority' => $ticketData['priority'] ?? 'Medium',
                        'urgency' => $ticketData['urgency'] ?? 'Medium',
                        'department' => $ticketData['department'] ?? 'Support',
                        'admin' => $ticketData['admin'] ?? '',
                        'lastreply' => $ticketData['lastreply'] ? new \DateTime($ticketData['lastreply']) : null,
                        'lastadminreply' => $ticketData['lastadminreply'] ? new \DateTime($ticketData['lastadminreply']) : null,
                        'date' => $ticketData['date'] ? new \DateTime($ticketData['date']) : now(),
                        'lastmodified' => $ticketData['lastmodified'] ? new \DateTime($ticketData['lastmodified']) : null,
                        'service' => $ticketData['service'] ?? null,
                        'synced_at' => now(),
                    ]
                );
                
                // مزامنة ردود التذكرة
                if (isset($ticketData['replies']['reply'])) {
                    foreach ($ticketData['replies']['reply'] as $replyData) {
                        \App\Models\TicketReply::updateOrCreate(
                            ['whmcs_id' => $replyData['id']],
                            [
                                'ticket_id' => $ticket->id,
                                'admin' => $replyData['admin'] ?? '',
                                'name' => $replyData['name'] ?? '',
                                'email' => $replyData['email'] ?? '',
                                'date' => $replyData['date'] ? new \DateTime($replyData['date']) : now(),
                                'message' => $replyData['message'] ?? '',
                                'attachment' => $replyData['attachment'] ?? '',
                            ]
                        );
                    }
                }
                
                // مزامنة ملاحظات التذكرة
                if (isset($ticketData['notes']['note'])) {
                    foreach ($ticketData['notes']['note'] as $noteData) {
                        \App\Models\TicketNote::updateOrCreate(
                            ['whmcs_id' => $noteData['id']],
                            [
                                'ticket_id' => $ticket->id,
                                'admin' => $noteData['admin'] ?? '',
                                'date' => $noteData['date'] ? new \DateTime($noteData['date']) : now(),
                                'message' => $noteData['message'] ?? '',
                                'attachment' => $noteData['attachment'] ?? '',
                            ]
                        );
                    }
                }
                
                $count++;
            }
            
            $page++;
        } while (count($tickets) == $limit);
        
        return $count;
    }

    /**
     * مزامنة كاملة للنظام: عملاء → خدمات العملاء + جهات اتصال → فواتير → تذاكر → منتجات
     *
     * @return array ['customers' => int, 'products' => int, 'invoices' => int, 'tickets' => int, 'catalog_products' => int]
     */
    public function fullSync(): array
    {
        $stats = ['customers' => 0, 'products' => 0, 'invoices' => 0, 'tickets' => 0, 'catalog_products' => 0];
        $stats['customers'] = $this->syncCustomers();
        $customers = \App\Models\Customer::whereNotNull('whmcs_id')->get();
        foreach ($customers as $customer) {
            $this->syncCustomerProducts($customer);
            $this->syncCustomerContacts($customer);
        }
        $stats['invoices'] = $this->syncInvoices();
        $stats['tickets'] = $this->syncTickets();
        $stats['catalog_products'] = $this->syncProducts();
        return $stats;
    }

    /**
     * مزامنة كاملة لعميل واحد: تفاصيله + منتجاته + جهات اتصاله
     *
     * @param \App\Models\Customer $customer
     * @return array ['customer' => true, 'products' => int, 'contacts' => int]
     */
    public function fullSyncCustomer(\App\Models\Customer $customer): array
    {
        $stats = ['customer' => false, 'products' => 0, 'contacts' => 0];
        if (! $customer->whmcs_id) {
            return $stats;
        }
        $this->syncCustomer($customer);
        $stats['customer'] = true;
        $stats['products'] = $this->syncCustomerProducts($customer);
        $stats['contacts'] = $this->syncCustomerContacts($customer);
        return $stats;
    }
}