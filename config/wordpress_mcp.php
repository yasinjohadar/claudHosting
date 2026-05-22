<?php

return [

    /** إضافة MCP Server على WordPress (REST /wp-json/mcp/v1/mcp) */
    'mcp_server_plugin_url' => env(
        'WORDPRESS_MCP_SERVER_PLUGIN_URL',
        'https://github.com/mcp-wp/mcp-server/archive/refs/heads/main.zip'
    ),

    /** حزمة WP-CLI للأوامر wp ai و wp mcp (يتطلب WP-CLI 2.11+) */
    'wp_cli_ai_package' => env('WORDPRESS_WP_CLI_AI_PACKAGE', 'mcp-wp/ai-command:dev-main'),

    /** اسم السيرفر في wp mcp server add */
    'mcp_server_alias' => env('WORDPRESS_MCP_SERVER_ALIAS', 'claudhosting'),

    /** مستخدم WordPress لإنشاء Application Password */
    'application_password_user_id' => (int) env('WORDPRESS_MCP_APP_PASSWORD_USER_ID', 1),

    'application_password_label' => env('WORDPRESS_MCP_APP_PASSWORD_LABEL', 'ClaudHosting MCP'),

    /**
     * إعداد Cursor MCP (انسخ إلى Cursor Settings → MCP).
     * بعد التركيب من اللوحة، استبدل SITE_URL و APP_PASSWORD من metadata الموقع.
     */
    'cursor_mcp_template' => [
        'wordpress-remote' => [
            'command' => 'npx',
            'args' => ['-y', '@automattic/mcp-wordpress-remote@latest'],
            'env' => [
                'WP_API_URL' => 'https://YOUR-SITE/wp-json',
                'WP_USERNAME' => 'admin',
                'WP_APP_PASSWORD' => 'xxxx xxxx xxxx xxxx xxxx xxxx',
            ],
        ],
    ],

];
