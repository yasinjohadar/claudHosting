<?php

return [

    'max_upload_bytes' => (int) env('COOLIFY_FILES_MAX_UPLOAD', 50 * 1024 * 1024),

    'max_read_bytes' => (int) env('COOLIFY_FILES_MAX_READ', 5 * 1024 * 1024),

    'max_list_entries' => 500,

    'host_temp_prefix' => '/tmp/claud-host',

    'local_temp_dir' => 'container-tmp',

    'protected_paths' => [
        'wp-config.php',
    ],

    'protected_delete_paths' => [
        '',
        '/',
        '.',
    ],

    'text_extensions' => [
        'php', 'css', 'scss', 'js', 'json', 'html', 'htm', 'txt', 'md', 'xml', 'yml', 'yaml',
        'ini', 'conf', 'htaccess', 'env', 'sql', 'log', 'blade.php',
    ],

];
