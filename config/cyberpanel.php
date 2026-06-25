<?php

return [

    'keys' => [
        'host' => 'cyberpanel_host',
        'port' => 'cyberpanel_port',
        'admin_user' => 'cyberpanel_admin_user',
        'admin_password' => 'cyberpanel_admin_password',
        'api_token' => 'cyberpanel_api_token',
        'api_style' => 'cyberpanel_api_style',
        'verify_ssl' => 'cyberpanel_verify_ssl',
        'default_package' => 'cyberpanel_default_package',
        'default_php_version' => 'cyberpanel_default_php_version',
        'default_owner' => 'cyberpanel_default_owner',
        'default_domain_suffix' => 'cyberpanel_default_domain_suffix',
        'timeout' => 'cyberpanel_timeout',
        'renewal_amount' => 'cyberpanel_renewal_amount',
        'invoice_due_days' => 'cyberpanel_invoice_due_days',
        'subscription_years' => 'cyberpanel_subscription_years',
    ],

    'defaults' => [
        'host' => '',
        'port' => '8090',
        'admin_user' => 'admin',
        'admin_password' => '',
        'api_token' => '',
        'api_style' => 'cloud',
        'verify_ssl' => '1',
        'default_package' => 'Default',
        'default_php_version' => 'PHP 8.3',
        'default_owner' => 'admin',
        'default_domain_suffix' => '',
        'timeout' => '60',
        'renewal_amount' => '0',
        'invoice_due_days' => '7',
        'subscription_years' => '1',
    ],

    /** cloud = POST /cloudAPI/ with controller; legacy = POST /api/{action} */
    'api_styles' => ['cloud', 'legacy'],

];
