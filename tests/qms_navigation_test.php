<?php

define('BASE_PATH', dirname(__DIR__));
define('KIRPI_CORE_ENTRY', true);
require BASE_PATH . '/core/config.php';
require BASE_PATH . '/core/database.php';
require BASE_PATH . '/core/functions.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) $failures[] = $message;
};

$menu = kirpi_navigation_menu_tree();
$management = null;
$system = null;
foreach ($menu as $item) {
    if (($item['title'] ?? '') === settings_lang('nav_management')) $management = $item;
    if (($item['title'] ?? '') === settings_lang('nav_system_management')) $system = $item;
}

$assert(is_array($management), 'QMS Management root menu is missing.');
$assert(is_array($system), 'System root menu is missing.');
$managementUrls = array_column($management['children'] ?? [], 'url');
$assert(in_array('organization/view', $managementUrls, true), 'Organization must be under Management.');
$assert(in_array('governance/view', $managementUrls, true), 'Governance must be under Management.');
$assert(!isset($management['url']), 'Management must be a dropdown menu.');

$systemGroupTitles = array_column($system['children'] ?? [], 'title');
$assert(in_array(settings_lang('nav_system'), $systemGroupTitles, true), 'Settings group must be under System.');
$menuManagementPage = (string) file_get_contents(BASE_PATH . '/modules/settings/pages/menu_management.php');
$menuExport = (string) file_get_contents(BASE_PATH . '/modules/settings/actions/export.php');
$assert(str_contains($menuManagementPage, "settings_lang('nav_system_management')"), 'Menu management page must label the fixed root as System.');
$assert(str_contains($menuExport, "settings_lang('nav_system_management')"), 'Menu export must label the fixed root as System.');

if ($failures) {
    foreach ($failures as $failure) fwrite(STDERR, "FAIL: {$failure}\n");
    exit(1);
}
fwrite(STDOUT, "QMS navigation: PASS\n");
