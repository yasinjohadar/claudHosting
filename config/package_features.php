<?php

/**
 * أيقونات ميزات الباقات (Font Awesome 5/6 — fas).
 * المفتاح يُخزَّن في package_features[].icon
 */
return [
    'icons' => [
        'storage' => ['class' => 'fa-hdd', 'label' => 'مساحة تخزين'],
        'bandwidth' => ['class' => 'fa-exchange-alt', 'label' => 'نقل بيانات'],
        'ssl' => ['class' => 'fa-lock', 'label' => 'شهادة SSL'],
        'email' => ['class' => 'fa-envelope', 'label' => 'بريد إلكتروني'],
        'domains' => ['class' => 'fa-globe', 'label' => 'نطاقات'],
        'database' => ['class' => 'fa-database', 'label' => 'قواعد بيانات'],
        'backup' => ['class' => 'fa-cloud-upload-alt', 'label' => 'نسخ احتياطي'],
        'cpu' => ['class' => 'fa-microchip', 'label' => 'معالج / أداء'],
        'ram' => ['class' => 'fa-layer-group', 'label' => 'ذاكرة RAM'],
        'support' => ['class' => 'fa-headset', 'label' => 'دعم فني'],
        'panel' => ['class' => 'fa-cogs', 'label' => 'لوحة تحكم'],
        'uptime' => ['class' => 'fa-clock', 'label' => 'وقت تشغيل'],
        'security' => ['class' => 'fa-shield-alt', 'label' => 'أمان'],
        'wordpress' => ['class' => 'fa-wordpress', 'label' => 'ووردبريس', 'brand' => true],
        'php' => ['class' => 'fa-code', 'label' => 'PHP / تطوير'],
        'unlimited' => ['class' => 'fa-infinity', 'label' => 'غير محدود'],
        'server' => ['class' => 'fa-server', 'label' => 'خادم / استضافة'],
        'check' => ['class' => 'fa-check', 'label' => 'ميزة عامة'],
    ],

    'max_items' => 20,
    'max_text_length' => 500,
];
