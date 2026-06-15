<?php
if (!defined('KIRPI_CORE_ENTRY')) exit;
require_once BASE_PATH . '/modules/organization/language.php';
require_once BASE_PATH . '/modules/organization/helpers.php';
$options = organization_select_options();
$unitsByCompany = [];
foreach ($options['units'] as $unit) $unitsByCompany[(int) $unit['company_id']][] = $unit;
$renderUnits = static function (array $units, ?int $parentId = null) use (&$renderUnits): string {
    $children = array_values(array_filter($units, static fn(array $unit): bool => ($unit['parent_unit_id'] === null ? null : (int) $unit['parent_unit_id']) === $parentId));
    if (!$children) return '';
    $html = '<ul class="list-unstyled ms-3 mb-0 border-start ps-3">';
    foreach ($children as $unit) {
        $html .= '<li class="py-2"><div class="d-flex align-items-center gap-2"><i class="ti ti-corner-down-right text-secondary"></i><span class="badge bg-secondary-lt">' . e(organization_lang((string) $unit['unit_type'], (string) $unit['unit_type'])) . '</span><span class="fw-medium">' . e((string) $unit['unit_name']) . '</span><span class="text-secondary small">' . e((string) $unit['unit_code']) . '</span></div>';
        $html .= $renderUnits($units, (int) $unit['id']);
        $html .= '</li>';
    }
    return $html . '</ul>';
};
?>
<div class="modal-header"><h5 class="modal-title"><?php echo e(organization_lang('hierarchy')); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body" id="organization-tree-view">
<?php foreach ($options['companies'] as $company): $companyUnits = $unitsByCompany[(int) $company['id']] ?? []; ?>
  <div class="card mb-3"><div class="card-header"><div><div class="fw-bold"><?php echo e($company['company_name']); ?></div><div class="text-secondary small"><?php echo e($company['company_code']); ?></div></div></div><div class="card-body py-2">
    <?php echo $companyUnits ? $renderUnits($companyUnits) : '<div class="text-secondary py-2">' . e(organization_lang('no_units')) . '</div>'; ?>
  </div></div>
<?php endforeach; ?>
</div>
