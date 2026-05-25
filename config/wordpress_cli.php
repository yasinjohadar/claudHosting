<?php

return [

    'raw_cli' => [
        'max_length' => 512,
        'allowed_prefixes' => [
            'plugin', 'theme', 'core', 'user', 'cache', 'db', 'cron', 'option', 'post',
            'comment', 'media', 'maintenance-mode', 'rewrite', 'transient',
            'widget', 'menu', 'search-replace', 'cli', 'package', 'mcp', 'config', 'role',
            'cap', 'super-admin', 'site', 'language', 'import', 'export', 'eval-file',
        ],
        'blocked_patterns' => [
            '/\beval\b/i',
            '/\bdb\s+drop\b/i',
            '/\bplugin\s+delete\s+--all\b/i',
            '/\btheme\s+delete\s+--all\b/i',
            '/[;&|`$]/',
            '/\$\(/',
            '/\.\./',
        ],
        'dangerous_patterns' => [
            '/\bdelete\b/i',
            '/\bdrop\b/i',
            '/\bsearch-replace\b/i',
            '/\bflush\b/i',
            '/\buninstall\b/i',
            '/\bremove\b/i',
        ],
    ],

    'action_labels' => [
        'refresh_info' => 'جلب معلومات WordPress',
        'diagnose' => 'تشخيص الاتصال',
        'plugin_update_all' => 'تحديث كل الإضافات',
        'theme_update_all' => 'تحديث كل القوالب',
        'plugin_update' => 'تحديث إضافة',
        'theme_update' => 'تحديث قالب',
        'plugin_install' => 'تثبيت إضافة',
        'theme_install' => 'تثبيت قالب',
        'plugin_activate' => 'تفعيل إضافة',
        'plugin_deactivate' => 'إيقاف إضافة',
        'plugin_delete' => 'حذف إضافة',
        'theme_activate' => 'تفعيل قالب',
        'theme_delete' => 'حذف قالب',
        'core_update' => 'تحديث Core',
        'core_reinstall' => 'إعادة تثبيت Core',
        'bootstrap_mcp' => 'تركيب MCP',
        'db_export' => 'تصدير قاعدة البيانات',
        'db_repair' => 'إصلاح قاعدة البيانات',
        'raw_cli' => 'تشغيل WP-CLI',
        'docker_compose_pull' => 'سحب صور Docker',
        'docker_compose_restart' => 'إعادة تشغيل Docker',
    ],

    'quick_commands' => [
        'plugin list --status=active',
        'theme list',
        'core version',
        'core check-update',
        'user list --format=table',
        'cron event list',
        'option get siteurl',
        'cache flush',
        'rewrite flush',
        'maintenance-mode status',
        'db check',
        'transient delete --all',
        'post list --post_type=page',
        'cli info',
    ],

    /*
    |--------------------------------------------------------------------------
    | Action registry
    | type: wp | host | special
    | async: queued via RunWordpressManagementJob
    | timeout: seconds for WP-CLI run
    | params: required param keys
    | confirm: default confirm message (null = none)
    | dangerous: requires confirm_dangerous when raw_cli
    |--------------------------------------------------------------------------
    */
    'actions' => [
        // --- existing / core ---
        'refresh_info' => ['type' => 'special', 'handler' => 'refresh_info', 'async' => false],
        'diagnose' => ['type' => 'special', 'handler' => 'diagnose', 'async' => true],
        'bootstrap_mcp' => ['type' => 'special', 'handler' => 'bootstrap_mcp', 'async' => true],
        'redis_apply_env' => ['type' => 'special', 'handler' => 'redis_apply_env', 'async' => false],
        'docker_compose_pull' => ['type' => 'special', 'handler' => 'docker_compose_pull', 'async' => true],

        'core_check_update' => ['type' => 'wp', 'command' => 'core check-update', 'timeout' => 120],
        'core_update_db' => ['type' => 'wp', 'command' => 'core update-db', 'timeout' => 120],
        'core_update' => ['type' => 'special', 'handler' => 'core_update', 'async' => true],
        'core_reinstall' => ['type' => 'special', 'handler' => 'core_reinstall', 'async' => true],

        'cache_flush' => ['type' => 'wp', 'command' => 'cache flush', 'timeout' => 60],
        'rewrite_flush' => ['type' => 'wp', 'command' => 'rewrite flush', 'timeout' => 60],
        'maintenance_activate' => ['type' => 'wp', 'command' => 'maintenance-mode activate', 'timeout' => 60],
        'maintenance_deactivate' => ['type' => 'wp', 'command' => 'maintenance-mode deactivate', 'timeout' => 60],

        // --- plugins ---
        'plugin_update_all' => ['type' => 'wp', 'command' => 'plugin update --all', 'timeout' => 900, 'async' => false],
        'plugin_update' => ['type' => 'wp', 'command' => 'plugin update {slug}', 'timeout' => 300, 'params' => ['slug'], 'async' => false],
        'plugin_install' => ['type' => 'wp', 'command' => 'plugin install {slug}', 'timeout' => 300, 'params' => ['slug'], 'async' => false],
        'plugin_activate' => ['type' => 'wp', 'command' => 'plugin activate {slug}', 'timeout' => 60, 'params' => ['slug']],
        'plugin_deactivate' => ['type' => 'wp', 'command' => 'plugin deactivate {slug}', 'timeout' => 60, 'params' => ['slug']],
        'plugin_delete' => ['type' => 'wp', 'command' => 'plugin delete {slug}', 'timeout' => 120, 'params' => ['slug'], 'confirm' => 'حذف الإضافة نهائياً؟', 'dangerous' => true],

        // --- themes ---
        'theme_update_all' => ['type' => 'wp', 'command' => 'theme update --all', 'timeout' => 600, 'async' => false],
        'theme_update' => ['type' => 'wp', 'command' => 'theme update {slug}', 'timeout' => 300, 'params' => ['slug'], 'async' => false],
        'theme_install' => ['type' => 'wp', 'command' => 'theme install {slug}', 'timeout' => 300, 'params' => ['slug'], 'async' => false],
        'theme_activate' => ['type' => 'wp', 'command' => 'theme activate {slug}', 'timeout' => 60, 'params' => ['slug']],
        'theme_delete' => ['type' => 'wp', 'command' => 'theme delete {slug}', 'timeout' => 120, 'params' => ['slug'], 'confirm' => 'حذف القالب نهائياً؟', 'dangerous' => true],

        // --- users ---
        'user_reset_password' => ['type' => 'special', 'handler' => 'user_reset_password', 'params' => ['login', 'password']],
        'user_create' => ['type' => 'special', 'handler' => 'user_create', 'params' => ['login', 'email']],
        'user_update_role' => ['type' => 'wp', 'command' => 'user set-role {login} {role}', 'timeout' => 60, 'params' => ['login', 'role']],
        'user_delete' => ['type' => 'wp', 'command' => 'user delete {user_id} --reassign=1', 'timeout' => 60, 'params' => ['user_id'], 'confirm' => 'حذف المستخدم؟', 'dangerous' => true],

        // --- database ---
        'db_export' => ['type' => 'wp', 'command' => 'db export -', 'timeout' => 600, 'async' => true],
        'db_check' => ['type' => 'wp', 'command' => 'db check', 'timeout' => 120],
        'db_repair' => ['type' => 'wp', 'command' => 'db repair', 'timeout' => 300, 'async' => true],
        'search_replace' => ['type' => 'special', 'handler' => 'search_replace', 'params' => ['old', 'new'], 'confirm' => 'تنفيذ search-replace على قاعدة البيانات؟', 'dangerous' => true],

        // --- content / options / cron ---
        'post_list' => ['type' => 'wp', 'command' => 'post list --format=table', 'timeout' => 120],
        'post_create' => ['type' => 'special', 'handler' => 'post_create', 'params' => ['title']],
        'post_delete' => ['type' => 'wp', 'command' => 'post delete {post_id} --force', 'timeout' => 60, 'params' => ['post_id'], 'confirm' => 'حذف المحتوى؟', 'dangerous' => true],
        'option_get' => ['type' => 'wp', 'command' => 'option get {option}', 'timeout' => 30, 'params' => ['option']],
        'option_update' => ['type' => 'wp', 'command' => 'option update {option} {value}', 'timeout' => 60, 'params' => ['option', 'value']],
        'cron_list' => ['type' => 'wp', 'command' => 'cron event list --format=table', 'timeout' => 60],
        'cron_run' => ['type' => 'wp', 'command' => 'cron event run {hook}', 'timeout' => 120, 'params' => ['hook']],
        'transient_delete_all' => ['type' => 'wp', 'command' => 'transient delete --all', 'timeout' => 120],

        // --- docker host ---
        'docker_compose_stop' => ['type' => 'special', 'handler' => 'docker_compose_lifecycle', 'lifecycle' => 'stop', 'async' => true, 'confirm' => 'إيقاف حاويات Docker؟'],
        'docker_compose_start' => ['type' => 'special', 'handler' => 'docker_compose_lifecycle', 'lifecycle' => 'start', 'async' => true],
        'docker_compose_restart' => ['type' => 'special', 'handler' => 'docker_compose_lifecycle', 'lifecycle' => 'restart', 'async' => true, 'confirm' => 'إعادة تشغيل حاويات Docker؟'],

        // --- raw ---
        'raw_cli' => ['type' => 'special', 'handler' => 'raw_cli', 'async' => true, 'params' => ['command']],
    ],

];
