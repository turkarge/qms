<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/settings/language.php';

$type = strtolower(trim((string) ($_GET['type'] ?? 'modules')));
$format = trim((string) ($_GET['format'] ?? 'csv'));

if ($type === 'menu') {
    $menuItems = kirpi_collect_module_menu_items();
    $rows = [
        [
            'fixed',
            'Dashboard',
            '',
            'core',
            'top',
            'fixed',
            1,
            'dashboard/view',
            'dashboard.view',
            settings_lang('menu_fixed_dashboard'),
        ],
        [
            'fixed',
            settings_lang('nav_management'),
            '',
            'core',
            'management',
            'fixed',
            999,
            '',
            '',
            settings_lang('menu_fixed_management'),
        ],
    ];

    foreach ($menuItems as $item) {
        $rows[] = [
            'module',
            (string) ($item['title'] ?? ''),
            (string) ($item['title_key'] ?? ''),
            (string) ($item['module'] ?? ''),
            (string) ($item['placement'] ?? 'management'),
            (string) ($item['group'] ?? 'default'),
            (int) ($item['weight'] ?? 500),
            (string) ($item['url'] ?? ''),
            (string) ($item['permission'] ?? ''),
            '',
        ];
    }

    kirpi_export_response($format, 'menu-registry-' . date('Ymd-His'), [
        settings_lang('type'),
        settings_lang('name'),
        settings_lang('title_key'),
        settings_lang('module'),
        settings_lang('placement'),
        settings_lang('group'),
        settings_lang('order'),
        settings_lang('route'),
        settings_lang('permission'),
        settings_lang('description'),
    ], $rows);
}

$modules = kirpi_list_modules();
$requiredByMap = [];

foreach ($modules as $candidateModule) {
    $candidateKey = (string) ($candidateModule['key'] ?? '');
    if ($candidateKey !== '') {
        $requiredByMap[$candidateKey] = [];
    }
}

foreach ($modules as $candidateModule) {
    if (empty($candidateModule['enabled'])) {
        continue;
    }

    $ownerKey = (string) ($candidateModule['key'] ?? '');
    foreach (array_map('strval', (array) ($candidateModule['requires'] ?? [])) as $requiredKey) {
        if ($requiredKey !== '' && array_key_exists($requiredKey, $requiredByMap)) {
            $requiredByMap[$requiredKey][] = $ownerKey;
        }
    }
}

$menuItems = kirpi_collect_module_menu_items();
$menuCountByModule = [];
foreach ($menuItems as $item) {
    $moduleKey = (string) ($item['module'] ?? '');
    if ($moduleKey === '') {
        continue;
    }
    $menuCountByModule[$moduleKey] = ($menuCountByModule[$moduleKey] ?? 0) + 1;
}

$rows = [];
foreach ($modules as $module) {
    $moduleKey = (string) ($module['key'] ?? '');
    $requires = array_values(array_unique(array_map('strval', (array) ($module['requires'] ?? []))));
    $requiredBy = array_values(array_unique(array_map('strval', (array) ($requiredByMap[$moduleKey] ?? []))));

    $rows[] = [
        $moduleKey,
        (string) ($module['name'] ?? $moduleKey),
        (string) ($module['description'] ?? ''),
        (string) ($module['version'] ?? '1.0.0'),
        (int) ($module['load_order'] ?? 100),
        !empty($module['core']) ? settings_lang('core') : settings_lang('plugin'),
        !empty($module['enabled']) ? settings_lang('active') : settings_lang('passive'),
        implode(', ', $requires),
        implode(', ', $requiredBy),
        (int) ($menuCountByModule[$moduleKey] ?? 0),
    ];
}

kirpi_export_response($format, 'module-registry-' . date('Ymd-His'), [
    'Key',
    settings_lang('name'),
    settings_lang('description'),
    settings_lang('version'),
    settings_lang('order'),
    settings_lang('type'),
    settings_lang('status'),
    settings_lang('dependency'),
    settings_lang('dependent_modules'),
    settings_lang('menu_count'),
], $rows);
