<?php

$routes = require BASE_PATH . '/core/system_routes.php';

$modules = function_exists('kirpi_list_modules') ? kirpi_list_modules() : [];
if (empty($modules)) {
    $moduleDirs = glob(BASE_PATH . '/modules/*', GLOB_ONLYDIR) ?: [];
    foreach ($moduleDirs as $moduleDir) {
        $modules[] = [
            'key' => basename($moduleDir),
            'enabled' => true,
            '_dir' => $moduleDir,
        ];
    }
}

foreach ($modules as $module) {
    $moduleDir = (string) ($module['_dir'] ?? '');
    if ($moduleDir === '') {
        continue;
    }

    if (($module['enabled'] ?? true) !== true) {
        continue;
    }

    $moduleRouteFile = $moduleDir . '/routes.php';

    if (!is_file($moduleRouteFile)) {
        continue;
    }

    $moduleRoutes = require $moduleRouteFile;

    if (!is_array($moduleRoutes)) {
        continue;
    }

    foreach ($moduleRoutes as $routeKey => $routeDefinition) {
        if (isset($routes[$routeKey])) {
            throw new RuntimeException("Çakışan rota bulundu: {$routeKey}");
        }

        $routes[$routeKey] = $routeDefinition;
    }
}
