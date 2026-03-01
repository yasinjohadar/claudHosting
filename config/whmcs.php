<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WHMCS API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the WHMCS API settings for your application.
    |
    */
    
    // URL لـ WHMCS API
    'api_url' => env('WHMCS_API_URL', 'https://your-whmcs-domain.com/includes/api.php'),
    
    // معرف API
    'api_identifier' => env('WHMCS_API_IDENTIFIER', ''),
    
    // مفتاح API السري
    'api_secret' => env('WHMCS_API_SECRET', ''),
    
    // Access Token (بديل لـ Identifier و Secret)
    'access_token' => env('WHMCS_ACCESS_TOKEN', ''),

    // مفتاح تجاوز قيد الـ IP: يُضاف في WHMCS configuration.php كـ $api_access_key
    'access_key' => env('WHMCS_API_ACCESS_KEY', ''),
    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | إعدادات التخزين المؤقت لبيانات WHMCS
    |
    */
    
    // مدة التخزين المؤقت للعملاء (بالدقائق)
    'customers_cache_duration' => env('WHMCS_CUSTOMERS_CACHE_DURATION', 15),
    
    // مدة التخزين المؤقت للمنتجات (بالدقائق)
    'products_cache_duration' => env('WHMCS_PRODUCTS_CACHE_DURATION', 60),
    
    // مدة التخزين المؤقت للفواتير (بالدقائق)
    'invoices_cache_duration' => env('WHMCS_INVOICES_CACHE_DURATION', 10),
    
    // مدة التخزين المؤقت للتذاكر (بالدقائق)
    'tickets_cache_duration' => env('WHMCS_TICKETS_CACHE_DURATION', 10),
    
    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | إعدادات التصفح الافتراضية لعرض البيانات
    |
    */
    
    // عدد العناصر المعروضة في الصفحة الواحدة
    'per_page' => env('WHMCS_PER_PAGE', 25),
    
    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | العملة الافتراضية للنظام
    |
    */
    
    'default_currency' => env('WHMCS_DEFAULT_CURRENCY', 1),
    
    /*
    |--------------------------------------------------------------------------
    | Default Client Group
    |--------------------------------------------------------------------------
    |
    | مجموعة العملاء الافتراضية عند إنشاء عميل جديد
    |
    */
    
    'default_client_group' => env('WHMCS_DEFAULT_CLIENT_GROUP', 1),
    
    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | الدولة الافتراضية عند إنشاء عميل جديد
    |
    */
    
    'default_country' => env('WHMCS_DEFAULT_COUNTRY', 'US'),

    /*
    |--------------------------------------------------------------------------
    | Order / Cart URL (Frontend)
    |--------------------------------------------------------------------------
    | رابط صفحة الطلب/السلة في WHMCS للفرونت اند (مثلاً cart.php أو order form)
    |
    */
    'order_url' => env('WHMCS_ORDER_URL', 'https://your-whmcs.com/cart.php'),
];