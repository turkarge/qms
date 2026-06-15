<?php

function kirpi_module_translate(string $moduleKey, string $key, ?string $default = null): string
{
    $moduleKey = trim($moduleKey);
    $key = trim($key);
    if ($moduleKey === '' || $key === '') {
        return $default ?? $key;
    }

    static $loadedLanguageFiles = [];
    if (!isset($loadedLanguageFiles[$moduleKey])) {
        $langFile = BASE_PATH . '/modules/' . $moduleKey . '/language.php';
        if (is_file($langFile)) {
            require_once $langFile;
        }
        $loadedLanguageFiles[$moduleKey] = true;
    }

    $langFunction = $moduleKey . '_lang';
    if (function_exists($langFunction)) {
        $translated = $langFunction($key, $default ?? $key);
        if (is_string($translated) && trim($translated) !== '') {
            return $translated;
        }
    }

    return $default ?? $key;
}

function kirpi_navigation_resolve_item_title(string $moduleKey, array $menuItem): string
{
    $fallbackTitle = trim((string) ($menuItem['title'] ?? ''));
    $titleKey = trim((string) ($menuItem['title_key'] ?? ''));

    if ($titleKey !== '') {
        return kirpi_module_translate($moduleKey, $titleKey, $fallbackTitle !== '' ? $fallbackTitle : $titleKey);
    }

    return $fallbackTitle;
}

function kirpi_navigation_group_meta(string $groupKey): array
{
    $groupKey = strtolower(trim($groupKey));

    $meta = [
        'title' => '',
        'icon' => 'ti ti-point',
        'weight' => 500,
    ];

    if ($groupKey === 'monitoring') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_monitoring', 'Monitoring / Izleme');
        $meta['icon'] = 'ti ti-radar';
        $meta['weight'] = 900;
    }

    if ($groupKey === 'access') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_access_management', 'Erisim Yonetimi');
        $meta['icon'] = 'ti ti-users-group';
        $meta['weight'] = 100;
    }

    if ($groupKey === 'management') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_management', 'Yönetim');
        $meta['icon'] = 'ti ti-building-cog';
        $meta['weight'] = 100;
    }

    if ($groupKey === 'communication') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_communication', 'Iletisim');
        $meta['icon'] = 'ti ti-message-circle';
        $meta['weight'] = 300;
    }

    if ($groupKey === 'content') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_content_management', 'İçerik Yönetimi');
        $meta['icon'] = 'ti ti-layout-dashboard';
        $meta['weight'] = 400;
    }

    if ($groupKey === 'system') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_system', 'Sistem');
        $meta['icon'] = 'ti ti-adjustments';
        $meta['weight'] = 700;
    }

    if ($groupKey === 'operations') {
        $meta['title'] = kirpi_module_translate('settings', 'nav_operations', 'Operasyon');
        $meta['icon'] = 'ti ti-tool';
        $meta['weight'] = 800;
    }

    if ($groupKey === 'intelligence') {
        $meta['title'] = kirpi_module_translate('ai', 'kirpi_intelligence', 'Kirpi Intelligence');
        $meta['icon'] = 'ti ti-brain';
        $meta['weight'] = 600;
    }

    return $meta;
}

function kirpi_collect_module_menu_items(): array
{
    $items = [];

    foreach (kirpi_list_modules() as $module) {
        if (($module['enabled'] ?? true) !== true) {
            continue;
        }

        $moduleKey = (string) ($module['key'] ?? '');
        $moduleMenus = (array) ($module['menu'] ?? []);
        foreach ($moduleMenus as $menuItem) {
            if (!is_array($menuItem)) {
                continue;
            }

            $title = trim((string) ($menuItem['title'] ?? ''));
            $titleKey = trim((string) ($menuItem['title_key'] ?? ''));
            $url = trim((string) ($menuItem['url'] ?? ''));
            if (($title === '' && $titleKey === '') || $url === '') {
                continue;
            }

            $resolvedTitle = kirpi_navigation_resolve_item_title($moduleKey, $menuItem);
            if ($resolvedTitle === '') {
                continue;
            }

            $items[] = [
                'module' => $moduleKey,
                'title' => $resolvedTitle,
                'title_key' => $titleKey,
                'icon' => trim((string) ($menuItem['icon'] ?? 'ti ti-point')),
                'url' => $url,
                'permission' => isset($menuItem['permission']) && trim((string) $menuItem['permission']) !== ''
                    ? trim((string) $menuItem['permission'])
                    : null,
                'placement' => strtolower(trim((string) ($menuItem['placement'] ?? 'management'))),
                'group' => strtolower(trim((string) ($menuItem['group'] ?? 'default'))),
                'weight' => (int) ($menuItem['weight'] ?? 500),
            ];
        }
    }

    usort($items, static function (array $a, array $b): int {
        $weightCompare = ((int) ($a['weight'] ?? 500)) <=> ((int) ($b['weight'] ?? 500));
        if ($weightCompare !== 0) {
            return $weightCompare;
        }

        return strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
    });

    return $items;
}

function kirpi_navigation_menu_tree(): array
{
    $topItems = [];
    $topGroups = [];
    $managementDirectItems = [];
    $managementGroups = [];

    foreach (kirpi_collect_module_menu_items() as $item) {
        $placement = (string) ($item['placement'] ?? 'management');
        if ($placement === 'top') {
            $topItem = [
                'title' => (string) ($item['title'] ?? ''),
                'icon' => (string) ($item['icon'] ?? 'ti ti-point'),
                'url' => (string) ($item['url'] ?? ''),
                'permission' => $item['permission'] ?? null,
                'weight' => (int) ($item['weight'] ?? 500),
            ];
            $groupKey = (string) ($item['group'] ?? 'default');
            if ($groupKey === '' || $groupKey === 'default') {
                $topItems[] = $topItem;
            } else {
                if (!isset($topGroups[$groupKey])) {
                    $groupMeta = kirpi_navigation_group_meta($groupKey);
                    $topGroups[$groupKey] = [
                        'title' => $groupMeta['title'] !== '' ? $groupMeta['title'] : ucfirst($groupKey),
                        'icon' => $groupMeta['icon'],
                        'weight' => (int) ($groupMeta['weight'] ?? 500),
                        'children' => [],
                    ];
                }
                $topGroups[$groupKey]['children'][] = $topItem;
            }
            continue;
        }

        $groupKey = (string) ($item['group'] ?? 'default');
        if ($groupKey === '' || $groupKey === 'default') {
            $managementDirectItems[] = [
                'title' => (string) ($item['title'] ?? ''),
                'icon' => (string) ($item['icon'] ?? 'ti ti-point'),
                'url' => (string) ($item['url'] ?? ''),
                'permission' => $item['permission'] ?? null,
                'weight' => (int) ($item['weight'] ?? 500),
            ];
            continue;
        }

        if (!isset($managementGroups[$groupKey])) {
            $groupMeta = kirpi_navigation_group_meta($groupKey);
            $managementGroups[$groupKey] = [
                'title' => $groupMeta['title'] !== '' ? $groupMeta['title'] : ucfirst($groupKey),
                'icon' => $groupMeta['icon'],
                'weight' => (int) ($groupMeta['weight'] ?? 500),
                'children' => [],
            ];
        }

        $managementGroups[$groupKey]['children'][] = [
            'title' => (string) ($item['title'] ?? ''),
            'icon' => (string) ($item['icon'] ?? 'ti ti-point'),
            'url' => (string) ($item['url'] ?? ''),
            'permission' => $item['permission'] ?? null,
            'weight' => (int) ($item['weight'] ?? 500),
        ];
    }

    usort($topItems, static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));
    foreach ($topGroups as &$topGroup) {
        usort($topGroup['children'], static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));
    }
    unset($topGroup);
    uasort($topGroups, static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));
    usort($managementDirectItems, static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));

    foreach ($managementGroups as &$groupItem) {
        usort($groupItem['children'], static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));
    }
    unset($groupItem);

    uasort($managementGroups, static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));

    $managementChildren = $managementDirectItems;
    foreach ($managementGroups as $group) {
        $managementChildren[] = [
            'title' => $group['title'],
            'icon' => $group['icon'],
            'children' => $group['children'],
            'weight' => (int) ($group['weight'] ?? 500),
        ];
    }

    usort($managementChildren, static fn(array $a, array $b): int => (($a['weight'] ?? 500) <=> ($b['weight'] ?? 500)) ?: strcmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? '')));

    $menu = [
        [
            'title' => kirpi_module_translate('dashboard', 'dashboard', 'Dashboard'),
            'icon' => 'ti ti-home',
            'url' => 'dashboard/view',
            'permission' => null,
            'weight' => 1,
        ],
    ];

    foreach ($topItems as $item) {
        $menu[] = $item;
    }

    foreach ($topGroups as $group) {
        $menu[] = [
            'title' => $group['title'],
            'icon' => $group['icon'],
            'children' => $group['children'],
            'weight' => (int) ($group['weight'] ?? 500),
        ];
    }

    $menu[] = [
        'title' => kirpi_module_translate('settings', 'nav_system_management', 'Sistem'),
        'icon' => 'ti ti-settings',
        'children' => $managementChildren,
        'weight' => 999,
    ];

    return $menu;
}
