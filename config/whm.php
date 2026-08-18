<?php

return [

    'keys' => [
        'host' => 'whm_host',
        'username' => 'whm_username',
        'api_token' => 'whm_api_token',
        'verify_ssl' => 'whm_verify_ssl',
        'default_package' => 'whm_default_package',
        'default_domain_suffix' => 'whm_default_domain_suffix',
        'timeout' => 'whm_timeout',
        'renewal_amount' => 'whm_renewal_amount',
        'invoice_due_days' => 'whm_invoice_due_days',
        'subscription_years' => 'whm_subscription_years',
        'ssh_host' => 'whm_ssh_host',
        'ssh_user' => 'whm_ssh_user',
        'ssh_port' => 'whm_ssh_port',
        'ssh_private_key' => 'whm_ssh_private_key',
        'ssh_private_key_path' => 'whm_ssh_private_key_path',
    ],

    'defaults' => [
        'host' => '',
        'username' => 'root',
        'api_token' => '',
        'verify_ssl' => '1',
        'default_package' => 'default',
        'default_domain_suffix' => '',
        'timeout' => '60',
        'renewal_amount' => '0',
        'invoice_due_days' => '7',
        'subscription_years' => '1',
        'ssh_host' => '',
        'ssh_user' => 'root',
        'ssh_port' => '22',
        'ssh_private_key' => '',
        'ssh_private_key_path' => '',
    ],

    /** Queue name for WHM WordPress management jobs */
    'wordpress_management_queue' => env('WHM_WORDPRESS_MANAGEMENT_QUEUE', 'default'),

    /** Cache for WHM server status (load, memory, disks) in seconds */
    'server_status_cache_seconds' => (int) env('WHM_SERVER_STATUS_CACHE', 60),

    /** Cache for per-account email deliverability (DKIM/SPF/PTR) in seconds */
    'email_deliverability_cache_seconds' => (int) env('WHM_EMAIL_DELIVERABILITY_CACHE', 300),

    /** Max domains inspected per account (protects against 100-subdomain accounts) */
    'email_deliverability_max_domains' => (int) env('WHM_EMAIL_DELIVERABILITY_MAX_DOMAINS', 25),

    /** Log the raw EmailAuth payload at debug level (for tightening the normalizer) */
    'email_deliverability_debug' => (bool) env('WHM_EMAIL_DELIVERABILITY_DEBUG', false),

    /** Allow a local gethostbyaddr() PTR lookup as a last-resort fallback */
    'email_deliverability_local_ptr' => (bool) env('WHM_EMAIL_DELIVERABILITY_LOCAL_PTR', true),

    /** Generate a DMARC record when the cPanel zone has none */
    'mail_dns_generate_dmarc' => (bool) env('WHM_MAIL_DNS_GENERATE_DMARC', true),

    /** Policy for generated DMARC records — only 'none' is safe unattended */
    'mail_dns_dmarc_policy' => env('WHM_MAIL_DNS_DMARC_POLICY', 'none'),

    /** Optional rua= mailbox for generated DMARC records ('' = omit rua entirely) */
    'mail_dns_dmarc_rua' => env('WHM_MAIL_DNS_DMARC_RUA', ''),

    /** Mirror AAAA for mail hosts (needs a working IPv6 PTR — off by default) */
    'mail_dns_include_ipv6' => (bool) env('WHM_MAIL_DNS_INCLUDE_IPV6', false),

    /** Minutes a previewed mail-DNS plan stays valid before it must be re-previewed */
    'mail_dns_plan_ttl_minutes' => (int) env('WHM_MAIL_DNS_PLAN_TTL', 15),

];
