<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}
?>
<footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item"><a href="#" target="_blank" class="link-secondary" rel="noopener">Dokümantasyon</a></li>
                  <li class="list-inline-item"><a href="#" class="link-secondary">Lisans</a></li>
                  <li class="list-inline-item">Framework v<?php echo e(app_ver()); ?></li>
                </ul>
              </div>
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">
                    Copyright © 2026
                    <a href="https://www.kirpinetwork.com"  target="_blank" class="link-secondary">Kirpi Network</a>. Tüm hakları saklıdır.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
        </div>
</div>

<?php
$canUseAiLauncher = check_permission('ai.view');
$canUseAiQueryFlow = check_permission('ai.schema.manage');
$canManageAiAdapters = check_permission('ai.adapters.manage');
$canViewAiAudit = check_permission('ai.audit.view');

if ($canUseAiLauncher):
?>
<div class="kirpi-ai-launcher" data-ai-launcher>
    <div class="kirpi-ai-launcher__panel" data-ai-launcher-panel aria-hidden="true">
        <div class="kirpi-ai-launcher__panel-header">
            <div>
                <div class="kirpi-ai-launcher__eyebrow">Kirpi AI</div>
                <div class="kirpi-ai-launcher__title">Kirpi Intelligence</div>
            </div>
            <button type="button" class="kirpi-ai-launcher__close" data-ai-launcher-close aria-label="Kapat">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="kirpi-ai-launcher__body">
            <div class="kirpi-ai-launcher__text">
                AI altyapısı hazır. Güvenli sorgu akışını başlatabilir veya provider ayarlarını kontrol edebilirsiniz.
            </div>
            <div class="kirpi-ai-launcher__actions">
                <?php if ($canUseAiQueryFlow): ?>
                    <a href="<?php echo base_url('ai/query-flow'); ?>" class="kirpi-ai-launcher__action kirpi-ai-launcher__action--primary">
                        <i class="ti ti-git-branch"></i>
                        <span>Query Flow</span>
                    </a>
                <?php endif; ?>
                <?php if ($canManageAiAdapters): ?>
                    <a href="<?php echo base_url('ai/providers'); ?>" class="kirpi-ai-launcher__action">
                        <i class="ti ti-plug-connected"></i>
                        <span>Provider Ayarları</span>
                    </a>
                <?php endif; ?>
                <?php if ($canViewAiAudit): ?>
                    <a href="<?php echo base_url('ai/audit'); ?>" class="kirpi-ai-launcher__action">
                        <i class="ti ti-history"></i>
                        <span>Audit Log</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <button type="button" class="kirpi-ai-launcher__bubble" data-ai-launcher-toggle aria-expanded="false" aria-label="Kirpi Intelligence">
        <span class="kirpi-ai-launcher__ring"></span>
        <img src="<?php echo asset_url('img/ai-assistant.gif'); ?>" alt="" class="kirpi-ai-launcher__image">
        <span class="kirpi-ai-launcher__label">AI</span>
    </button>
</div>
<?php endif; ?>

<script src="<?php echo asset_url('js/jquery-3.7.1.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/tabler.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/toastr.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/app.js'); ?>"></script>
<script src="<?php echo asset_url('js/report-table.js'); ?>"></script>
<script src="<?php echo asset_url('js/pwa.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/jszip.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.buttons.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/buttons.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/buttons.html5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/buttons.print.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/buttons.colVis.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.responsive.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/responsive.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.select.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/select.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.colReorder.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/colReorder.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.fixedHeader.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/fixedHeader.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/dataTables.keyTable.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/datatables/js/keyTable.bootstrap5.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/kirpi-table.js'); ?>"></script>
<?php
global $current_route;
$route_file = $current_route['file'] ?? null;
if ($route_file === 'modules/documents/pages/view.php'):
?>
<script src="<?php echo asset_url('vendor/filepond/filepond.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/filepond/filepond-plugin-file-validate-type.min.js'); ?>"></script>
<script src="<?php echo asset_url('vendor/filepond/filepond-plugin-file-validate-size.min.js'); ?>"></script>
<?php endif; ?>
<!-- Cloudflare Web Analytics --><script defer src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='{"token": "7356366510c54c86a154d277ed978201"}'></script><!-- End Cloudflare Web Analytics -->

<?php
$page_script = resolve_page_script($route_file);

if ($page_script):
?>
<script src="<?php echo page_script_url($page_script); ?>"></script>
<?php endif; ?>

<?php
require BASE_PATH . '/layouts/main_modal.php';
require BASE_PATH . '/layouts/confirm_modal.php';
require BASE_PATH . '/layouts/secondary_modal.php';
?>

</body>
</html>
