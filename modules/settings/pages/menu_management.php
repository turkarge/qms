<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/settings/language.php';

$moduleMenus = kirpi_collect_module_menu_items();
$topMenus = array_values(array_filter($moduleMenus, static fn(array $item): bool => ($item['placement'] ?? 'management') === 'top'));
$managementMenus = array_values(array_filter($moduleMenus, static fn(array $item): bool => ($item['placement'] ?? 'management') !== 'top'));
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(settings_lang('system_management')); ?></div>
                <h2 class="page-title"><?php echo e(settings_lang('menu_management')); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="alert alert-info mb-4">
            <?php echo e(settings_lang('menu_management_note')); ?>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(settings_lang('fixed_menu_items')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Sabit Menü Öğeleri" class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th><?php echo e(settings_lang('name')); ?></th>
                            <th><?php echo e(settings_lang('order')); ?></th>
                            <th><?php echo e(settings_lang('description')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Dashboard</td>
                            <td>1</td>
                            <td><?php echo e(settings_lang('menu_fixed_dashboard')); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo e(settings_lang('nav_management')); ?></td>
                            <td>999</td>
                            <td><?php echo e(settings_lang('menu_fixed_management')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(settings_lang('top_menu_items')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Üst Menü Öğeleri" class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th><?php echo e(settings_lang('name')); ?></th>
                            <th><?php echo e(settings_lang('title_key')); ?></th>
                            <th><?php echo e(settings_lang('module')); ?></th>
                            <th><?php echo e(settings_lang('placement')); ?></th>
                            <th><?php echo e(settings_lang('group')); ?></th>
                            <th><?php echo e(settings_lang('order')); ?></th>
                            <th><?php echo e(settings_lang('route')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topMenus)): ?>
                            <tr>
                                <td colspan="7" class="text-secondary"><?php echo e(settings_lang('no_menu_item')); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topMenus as $menuItem): ?>
                                <tr>
                                    <td><?php echo e((string) ($menuItem['title'] ?? '')); ?></td>
                                    <td><code><?php echo e((string) ($menuItem['title_key'] ?? '')); ?></code></td>
                                    <td><code><?php echo e((string) ($menuItem['module'] ?? '')); ?></code></td>
                                    <td><?php echo e((string) ($menuItem['placement'] ?? '')); ?></td>
                                    <td><?php echo e((string) ($menuItem['group'] ?? 'default')); ?></td>
                                    <td><?php echo (int) ($menuItem['weight'] ?? 500); ?></td>
                                    <td><code><?php echo e((string) ($menuItem['url'] ?? '')); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(settings_lang('management_menu_items')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Yönetim Menüsü Öğeleri" class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th><?php echo e(settings_lang('name')); ?></th>
                            <th><?php echo e(settings_lang('title_key')); ?></th>
                            <th><?php echo e(settings_lang('module')); ?></th>
                            <th><?php echo e(settings_lang('placement')); ?></th>
                            <th><?php echo e(settings_lang('group')); ?></th>
                            <th><?php echo e(settings_lang('order')); ?></th>
                            <th><?php echo e(settings_lang('route')); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($managementMenus)): ?>
                            <tr>
                                <td colspan="7" class="text-secondary"><?php echo e(settings_lang('no_menu_item')); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($managementMenus as $menuItem): ?>
                                <tr>
                                    <td><?php echo e((string) ($menuItem['title'] ?? '')); ?></td>
                                    <td><code><?php echo e((string) ($menuItem['title_key'] ?? '')); ?></code></td>
                                    <td><code><?php echo e((string) ($menuItem['module'] ?? '')); ?></code></td>
                                    <td><?php echo e((string) ($menuItem['placement'] ?? '')); ?></td>
                                    <td><?php echo e((string) ($menuItem['group'] ?? 'default')); ?></td>
                                    <td><?php echo (int) ($menuItem['weight'] ?? 500); ?></td>
                                    <td><code><?php echo e((string) ($menuItem['url'] ?? '')); ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
