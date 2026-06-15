<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/dashboard/language.php';
?>

<div class="modal-header">
    <h5 class="modal-title"><?php echo e(dashboard_lang('about_title')); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">
    <div class="d-flex flex-column gap-3">
        <div>
            <div class="text-secondary small"><?php echo e(dashboard_lang('about_app')); ?></div>
            <div class="fw-bold"><?php echo e(app_name()); ?></div>
        </div>

        <div>
            <div class="text-secondary small"><?php echo e(dashboard_lang('about_env')); ?></div>
            <div class="fw-bold"><?php echo e(APP_ENV); ?></div>
        </div>

        <div>
            <div class="text-secondary small"><?php echo e(dashboard_lang('about_debug')); ?></div>
            <div class="fw-bold"><?php echo APP_DEBUG ? e(dashboard_lang('about_debug_on')) : e(dashboard_lang('about_debug_off')); ?></div>
        </div>

        <div>
            <div class="text-secondary small"><?php echo e(dashboard_lang('about_description')); ?></div>
            <div>
                <?php echo e(dashboard_lang('about_text')); ?>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(dashboard_lang('close')); ?></button>
</div>
