<?php

/**
 * Client portal sidebar menu.
 */
return [
    [
        'type' => 'link',
        'label' => 'الرئيسية',
        'route' => 'client.dashboard',
        'icon' => 'fe fe-home',
        'color' => 'primary',
        'active' => 'client.dashboard',
    ],
    [
        'type' => 'link',
        'label' => 'WordPress',
        'route' => 'client.wordpress-sites.index',
        'icon' => 'fe fe-globe',
        'color' => 'info',
        'active' => 'client.wordpress-sites.*',
        // Hidden unless the client actually has Coolify WordPress sites — the page it
        // opens would otherwise be permanently empty. Resolved by ClientMenuVisibility.
        'visible' => 'has_wordpress_sites',
    ],
    // Coolify is hidden from the client sidebar. Its pages and routes still exist —
    // restore this entry to bring the link back.
    // [
    //     'type' => 'link',
    //     'label' => 'Coolify',
    //     'route' => 'client.services',
    //     'url_hash' => '#projects',
    //     'icon' => 'fe fe-layers',
    //     'color' => 'purple',
    //     'active' => 'client.coolify.projects.*',
    // ],
    [
        'type' => 'link',
        'label' => 'الخدمات',
        'route' => 'client.services',
        'icon' => 'fe fe-grid',
        'color' => 'success',
        'active' => 'client.services',
    ],
    [
        'type' => 'link',
        'label' => 'الفواتير',
        'route' => 'client.invoices.index',
        'icon' => 'fe fe-file-text',
        'color' => 'warning',
        'active' => 'client.invoices.*',
    ],
    [
        'type' => 'link',
        'label' => 'المدفوعات',
        'route' => 'client.payments.index',
        'icon' => 'fe fe-credit-card',
        'color' => 'teal',
        'active' => 'client.payments.*',
    ],
    [
        'type' => 'link',
        'label' => 'التذاكر',
        'route' => 'client.tickets.index',
        'icon' => 'fe fe-headphones',
        'color' => 'danger',
        'active' => 'client.tickets.*',
    ],
    [
        'type' => 'link',
        'label' => 'الملف الشخصي',
        'route' => 'client.profile.show',
        'icon' => 'fe fe-user',
        'color' => 'primary',
        'active' => 'client.profile.*',
    ],
    [
        'type' => 'external',
        'label' => 'الموقع العام',
        'url' => '/',
        'icon' => 'fe fe-external-link',
        'color' => 'secondary',
    ],
];
