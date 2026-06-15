<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/roles/language.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    ?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo e(roles_lang('edit_role')); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="alert alert-danger mb-0">
            <?php echo e(roles_lang('invalid_role_id')); ?>
        </div>
    </div>
    <?php
    exit;
}

$role = null;
try {
    $stmt = db()->prepare("
        SELECT id, name, is_active
        FROM roles
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $id,
    ]);

    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        throw new RuntimeException('Role not found.');
    }
} catch (Throwable $e) {
    error_log('roles edit modal error: ' . $e->getMessage());
    ?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo e(roles_lang('edit_role')); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="alert alert-danger mb-0">
            <?php echo e(roles_lang('role_data_load_error')); ?>
        </div>
    </div>
    <?php
    exit;
}
?>

<div class="modal-header">
    <h5 class="modal-title"><?php echo e(roles_lang('edit_role')); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<form
    id="roles-edit-form"
    action="<?php echo base_url('roles/actions/update'); ?>"
    method="post"
    data-ajax="true"
    data-close-modal="true"
>
    <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $role['id']; ?>">

        <div class="mb-3">
            <label class="form-label form-required"><?php echo e(roles_lang('role_name')); ?></label>
            <input
                type="text"
                name="name"
                class="form-control"
                maxlength="100"
                value="<?php echo e($role['name']); ?>"
                required
            >
            <?php if (($role['name'] ?? '') === 'Super Admin'): ?>
                <small class="form-hint"><?php echo e(roles_lang('super_admin_name_hint')); ?></small>
            <?php endif; ?>
        </div>

        <div>
            <label class="form-check form-switch m-0">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    class="form-check-input"
                    <?php echo (int) $role['is_active'] === 1 ? 'checked' : ''; ?>
                >
                <span class="form-check-label"><?php echo e(roles_lang('role_active_switch')); ?></span>
            </label>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(roles_lang('cancel')); ?></button>
        <button type="submit" class="btn btn-primary"><?php echo e(roles_lang('update')); ?></button>
    </div>
</form>
