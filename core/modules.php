<?php

function kirpi_module_manifest_defaults(string $moduleKey): array
{
    $moduleKey = trim($moduleKey);

    return [
        'key' => $moduleKey,
        'name' => ucfirst($moduleKey),
        'description' => '',
        'version' => '1.0.0',
        'enabled' => true,
        'core' => true,
        'load_order' => 100,
        'requires' => [],
        'author' => 'Kirpi Core',
        'menu' => [],
    ];
}

function kirpi_load_module_manifest(string $moduleDir): array
{
    $moduleKey = basename($moduleDir);
    $manifest = kirpi_module_manifest_defaults($moduleKey);
    $manifestPath = rtrim($moduleDir, '/\\') . '/module.json';

    if (!is_file($manifestPath)) {
        $manifest['_source'] = 'defaults';
        return $manifest;
    }

    $raw = (string) file_get_contents($manifestPath);
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        $manifest['_source'] = 'defaults_invalid_json';
        return $manifest;
    }

    $merged = array_merge($manifest, $decoded);
    $merged['key'] = trim((string) ($merged['key'] ?? $moduleKey)) ?: $moduleKey;
    $merged['name'] = trim((string) ($merged['name'] ?? ucfirst($moduleKey))) ?: ucfirst($moduleKey);
    $merged['version'] = trim((string) ($merged['version'] ?? '1.0.0')) ?: '1.0.0';
    $merged['description'] = trim((string) ($merged['description'] ?? ''));
    $merged['enabled'] = filter_var($merged['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($merged['enabled'] === null) {
        $merged['enabled'] = true;
    }
    $merged['core'] = filter_var($merged['core'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($merged['core'] === null) {
        $merged['core'] = true;
    }
    $merged['load_order'] = max(0, (int) ($merged['load_order'] ?? 100));
    $merged['requires'] = is_array($merged['requires'] ?? null) ? array_values($merged['requires']) : [];
    $merged['author'] = trim((string) ($merged['author'] ?? 'Kirpi Core')) ?: 'Kirpi Core';
    $merged['menu'] = [];
    if (isset($decoded['menu']) && is_array($decoded['menu'])) {
        foreach ($decoded['menu'] as $menuItem) {
            if (!is_array($menuItem)) {
                continue;
            }

            $title = trim((string) ($menuItem['title'] ?? ''));
            $titleKey = trim((string) ($menuItem['title_key'] ?? ''));
            $url = trim((string) ($menuItem['url'] ?? ''));
            if (($title === '' && $titleKey === '') || $url === '') {
                continue;
            }

            $placement = strtolower(trim((string) ($menuItem['placement'] ?? 'management')));
            if (!in_array($placement, ['top', 'management'], true)) {
                $placement = 'management';
            }

            $group = strtolower(trim((string) ($menuItem['group'] ?? 'default')));
            if ($group === '') {
                $group = 'default';
            }

            $merged['menu'][] = [
                'title' => $title,
                'title_key' => $titleKey,
                'icon' => trim((string) ($menuItem['icon'] ?? 'ti ti-point')),
                'url' => $url,
                'permission' => isset($menuItem['permission']) ? trim((string) $menuItem['permission']) : null,
                'placement' => $placement,
                'group' => $group,
                'weight' => (int) ($menuItem['weight'] ?? 500),
            ];
        }
    }
    $merged['_source'] = 'module.json';

    return $merged;
}

function kirpi_discover_modules(): array
{
    $modulesPath = BASE_PATH . '/modules';
    $moduleDirs = glob($modulesPath . '/*', GLOB_ONLYDIR) ?: [];

    $modules = [];
    foreach ($moduleDirs as $moduleDir) {
        $manifest = kirpi_load_module_manifest($moduleDir);
        $manifest['_dir'] = $moduleDir;
        $modules[] = $manifest;
    }

    return $modules;
}

function kirpi_modules_registry_ready(): bool
{
    return db_table_exists('app_modules');
}

function kirpi_sync_module_registry(): void
{
    if (!kirpi_modules_registry_ready()) {
        return;
    }

    try {
        $discovered = kirpi_discover_modules();
        if (empty($discovered)) {
            return;
        }

        $upsertStmt = db()->prepare("
            INSERT INTO app_modules (
                module_key,
                module_name,
                installed_version,
                is_enabled,
                is_core,
                load_order
            ) VALUES (
                :module_key,
                :module_name,
                :installed_version,
                :is_enabled,
                :is_core,
                :load_order
            )
            ON DUPLICATE KEY UPDATE
                module_name = VALUES(module_name),
                installed_version = VALUES(installed_version),
                is_core = VALUES(is_core),
                load_order = VALUES(load_order),
                updated_at = CURRENT_TIMESTAMP
        ");

        foreach ($discovered as $module) {
            $upsertStmt->execute([
                ':module_key' => (string) ($module['key'] ?? ''),
                ':module_name' => mb_substr((string) ($module['name'] ?? ''), 0, 120),
                ':installed_version' => mb_substr((string) ($module['version'] ?? '1.0.0'), 0, 50),
                ':is_enabled' => !empty($module['enabled']) ? 1 : 0,
                ':is_core' => !empty($module['core']) ? 1 : 0,
                ':load_order' => (int) ($module['load_order'] ?? 100),
            ]);
        }
    } catch (Throwable $e) {
        error_log('module registry sync error: ' . $e->getMessage());
    }
}

function kirpi_module_registry_map(): array
{
    if (!kirpi_modules_registry_ready()) {
        return [];
    }

    try {
        $stmt = db()->query("
            SELECT
                module_key,
                module_name,
                installed_version,
                is_enabled,
                is_core,
                load_order,
                updated_at
            FROM app_modules
            ORDER BY load_order ASC, module_key ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('module registry read error: ' . $e->getMessage());
        return [];
    }

    $map = [];
    foreach ($rows as $row) {
        $key = (string) ($row['module_key'] ?? '');
        if ($key === '') {
            continue;
        }

        $map[$key] = $row;
    }

    return $map;
}

function kirpi_list_modules(): array
{
    $modules = kirpi_discover_modules();

    if (kirpi_modules_registry_ready()) {
        kirpi_sync_module_registry();
        $registry = kirpi_module_registry_map();

        foreach ($modules as &$module) {
            $key = (string) ($module['key'] ?? '');
            if ($key === '' || !isset($registry[$key])) {
                continue;
            }

            $reg = (array) $registry[$key];
            $module['name'] = (string) ($reg['module_name'] ?? $module['name'] ?? $key);
            $module['version'] = (string) ($reg['installed_version'] ?? $module['version'] ?? '1.0.0');
            $module['enabled'] = (int) ($reg['is_enabled'] ?? 1) === 1;
            $module['core'] = (int) ($reg['is_core'] ?? 1) === 1;
            $module['load_order'] = (int) ($reg['load_order'] ?? ($module['load_order'] ?? 100));
            $module['_registry_updated_at'] = (string) ($reg['updated_at'] ?? '');
        }
        unset($module);
    }

    usort($modules, static function (array $a, array $b): int {
        $orderCompare = ((int) ($a['load_order'] ?? 100)) <=> ((int) ($b['load_order'] ?? 100));
        if ($orderCompare !== 0) {
            return $orderCompare;
        }

        return strcmp((string) ($a['key'] ?? ''), (string) ($b['key'] ?? ''));
    });

    return $modules;
}

function kirpi_find_module(string $moduleKey): ?array
{
    $moduleKey = trim($moduleKey);
    if ($moduleKey === '') {
        return null;
    }

    foreach (kirpi_list_modules() as $module) {
        if ((string) ($module['key'] ?? '') === $moduleKey) {
            return $module;
        }
    }

    return null;
}

function kirpi_module_required_by_enabled_modules(string $moduleKey): array
{
    $requiredBy = [];

    foreach (kirpi_list_modules() as $module) {
        if ((string) ($module['key'] ?? '') === $moduleKey) {
            continue;
        }

        if (empty($module['enabled'])) {
            continue;
        }

        $requires = array_map('strval', (array) ($module['requires'] ?? []));
        if (in_array($moduleKey, $requires, true)) {
            $requiredBy[] = (string) ($module['key'] ?? '');
        }
    }

    return array_values(array_unique($requiredBy));
}

function kirpi_set_module_enabled(string $moduleKey, bool $enabled, ?int $updatedBy = null): array
{
    if (!kirpi_modules_registry_ready()) {
        return [
            'success' => false,
            'message' => 'Modül registry tablosu hazır değil.',
        ];
    }

    $module = kirpi_find_module($moduleKey);
    if (!$module) {
        return [
            'success' => false,
            'message' => 'Modül bulunamadı.',
        ];
    }

    if (!empty($module['core'])) {
        return [
            'success' => false,
            'message' => 'Core modül devre dışı bırakılamaz.',
        ];
    }

    if (!$enabled) {
        $requiredBy = kirpi_module_required_by_enabled_modules($moduleKey);
        if (!empty($requiredBy)) {
            return [
                'success' => false,
                'message' => 'Bu modul diger aktif moduller tarafindan kullaniliyor: ' . implode(', ', $requiredBy),
            ];
        }
    }

    try {
        $stmt = db()->prepare("
            UPDATE app_modules
            SET is_enabled = :is_enabled,
                updated_by = :updated_by,
                updated_at = CURRENT_TIMESTAMP
            WHERE module_key = :module_key
            LIMIT 1
        ");
        $stmt->execute([
            ':is_enabled' => $enabled ? 1 : 0,
            ':updated_by' => ($updatedBy ?? 0) > 0 ? $updatedBy : null,
            ':module_key' => $moduleKey,
        ]);

        if ($stmt->rowCount() <= 0) {
            return [
                'success' => false,
                'message' => 'Modül durumu güncellenemedi.',
            ];
        }

        return [
            'success' => true,
            'message' => $enabled ? 'Modül aktif edildi.' : 'Modül devre dışı bırakıldı.',
        ];
    } catch (Throwable $e) {
        error_log('module enable toggle error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Modül durumu güncellenirken hata oluştu.',
        ];
    }
}
