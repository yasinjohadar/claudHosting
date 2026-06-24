<?php

return [

    'group' => 'infrastructure',

    'keys' => [
        'contabo_client_id' => 'infra_contabo_client_id',
        'contabo_client_secret' => 'infra_contabo_client_secret',
        'contabo_api_user' => 'infra_contabo_api_user',
        'contabo_api_password' => 'infra_contabo_api_password',
        'hetzner_api_token' => 'infra_hetzner_api_token',
        'digitalocean_api_token' => 'infra_digitalocean_api_token',
        'ovh_application_key' => 'infra_ovh_application_key',
        'ovh_application_secret' => 'infra_ovh_application_secret',
        'ovh_consumer_key' => 'infra_ovh_consumer_key',
        'ovh_endpoint' => 'infra_ovh_endpoint',
        'netcup_customer_number' => 'infra_netcup_customer_number',
        'netcup_api_password' => 'infra_netcup_api_password',
        'netcup_refresh_token' => 'infra_netcup_refresh_token',
        'netcup_scp_user_id' => 'infra_netcup_scp_user_id',
        'netcup_client_id' => 'infra_netcup_client_id',
        'netcup_client_secret' => 'infra_netcup_client_secret',
    ],

    'defaults' => [
        'contabo_client_id' => '',
        'contabo_client_secret' => '',
        'contabo_api_user' => '',
        'contabo_api_password' => '',
        'hetzner_api_token' => '',
        'digitalocean_api_token' => '',
        'ovh_application_key' => '',
        'ovh_application_secret' => '',
        'ovh_consumer_key' => '',
        'ovh_endpoint' => 'ovh-eu',
        'netcup_customer_number' => '',
        'netcup_api_password' => '',
        'netcup_refresh_token' => '',
        'netcup_scp_user_id' => '',
        'netcup_client_id' => '',
        'netcup_client_secret' => '',
    ],

    'contabo' => [
        'auth_url' => 'https://auth.contabo.com/auth/realms/contabo/protocol/openid-connect/token',
        'api_base' => 'https://api.contabo.com/v1',
    ],

    'hetzner' => [
        'api_base' => 'https://api.hetzner.cloud/v1',
    ],

    'digitalocean' => [
        'api_base' => 'https://api.digitalocean.com/v2',
    ],

    'ovh' => [
        'endpoints' => [
            'ovh-eu' => 'ovh-eu',
            'ovh-us' => 'ovh-us',
            'ovh-ca' => 'ovh-ca',
        ],
    ],

    'netcup' => [
        'api_base' => env('NETCUP_SCP_API_BASE', 'https://www.servercontrolpanel.de/scp-core/api/v1'),
        'token_url' => env('NETCUP_SCP_TOKEN_URL', 'https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/token'),
        'device_auth_url' => env('NETCUP_SCP_DEVICE_AUTH_URL', 'https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/auth/device'),
        'revoke_url' => env('NETCUP_SCP_REVOKE_URL', 'https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/revoke'),
        'userinfo_url' => env('NETCUP_SCP_USERINFO_URL', 'https://www.servercontrolpanel.de/realms/scp/protocol/openid-connect/userinfo'),
        'oauth_client_id' => env('NETCUP_SCP_OAUTH_CLIENT_ID', 'scp'),
        'ping_url' => env('NETCUP_SCP_PING_URL', 'https://www.servercontrolpanel.de/scp-core/api/ping'),
        'task_poll_interval' => (int) env('NETCUP_TASK_POLL_INTERVAL', 3),
        'task_wait_timeout' => (int) env('NETCUP_TASK_WAIT_TIMEOUT', 300),
        'metrics_default_hours' => (int) env('NETCUP_METRICS_DEFAULT_HOURS', 6),
    ],

    'metrics_cache_seconds' => (int) env('INFRA_METRICS_CACHE_SECONDS', 8),
    'metrics_refresh_seconds' => (int) env('INFRA_METRICS_REFRESH_SECONDS', 10),
    'metrics_snapshot_interval_minutes' => (int) env('INFRA_METRICS_SNAPSHOT_INTERVAL', 5),
    'metrics_retention_days' => (int) env('INFRA_METRICS_RETENTION_DAYS', 7),

];
