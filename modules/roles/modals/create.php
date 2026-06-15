<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/roles/language.php';
?>

<div class="modal-header">
    <h5 class="modal-title"><?php echo e(roles_lang('new_role')); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<form
    id="roles-create-form"
    action="<?php echo base_url('roles/actions/create'); ?>"
    method="post"
    data-ajax="true"
    data-close-modal="true"
>
    <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">

        <div class="mb-3">
            <label class="form-label form-required"><?php echo e(roles_lang('role_name')); ?></label>
            <input
                type="text"
                name="name"
                class="form-control"
                maxlength="100"
                required
            >
            <small class="form-hint"><?php echo e(roles_lang('role_name_hint')); ?></small>
        </div>

        <div>
            <label class="form-check form-switch m-0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="form-check-input"
                    checked
                >
                <span class="form-check-label"><?php echo e(roles_lang('role_active_switch')); ?></span>
            </label>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(roles_lang('cancel')); ?></button>
        <button type="submit" class="btn btn-primary"><?php echo e(roles_lang('save')); ?></button>
    </div>
</form>
