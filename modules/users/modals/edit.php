<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/users/language.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    ?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo e(users_lang('edit_user')); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="alert alert-danger mb-0">
            <?php echo e(users_lang('invalid_user_id')); ?>
        </div>
    </div>
    <?php
    exit;
}

$roles = [];
$user = null;
$lockSchemaReady = kirpi_auth_lock_schema_ready();

try {
    $stmt = db()->prepare("
        SELECT
            u.id,
            u.role_id,
            u.name,
            u.email,
            u.avatar,
            u.is_active,
            " . ($lockSchemaReady ? "u.lock_enabled" : "0 AS lock_enabled") . "
        FROM users u
        WHERE u.id = :id
        LIMIT 1
    ");
    $stmt->execute([
        ':id' => $id,
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new RuntimeException('User not found.');
    }

    $roles = get_roles_for_select((int) ($user['role_id'] ?? 0), true);
} catch (Throwable $e) {
    error_log('users edit modal error: ' . $e->getMessage());
    ?>
    <div class="modal-header">
        <h5 class="modal-title"><?php echo e(users_lang('edit_user')); ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="alert alert-danger mb-0">
            <?php echo e(users_lang('user_data_load_error')); ?>
        </div>
    </div>
    <?php
    exit;
}

$avatarUrl = !empty($user['avatar'])
    ? base_url('uploads/avatars/' . ltrim($user['avatar'], '/'))
    : null;

$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
$canDropSession = check_permission('users.session.drop');
$canResetLockKey = check_permission('users.lock.reset');
?>

<div class="modal-header">
    <h5 class="modal-title"><?php echo e(users_lang('edit_user')); ?></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<form
    id="users-edit-form"
    action="<?php echo base_url('users/actions/update'); ?>"
    method="post"
    enctype="multipart/form-data"
    data-ajax="true"
    data-close-modal="true"
>
    <div class="modal-body">
        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">

        <div id="users-edit-alert-area"></div>

        <div class="row g-3">
            <div class="col-12">
                <div class="d-flex align-items-center gap-3">
                    <?php if ($avatarUrl): ?>
                        <span
                            class="avatar avatar-xl"
                            style="background-image: url('<?php echo e($avatarUrl); ?>')"
                        ></span>
                    <?php else: ?>
                        <span class="avatar avatar-xl"><?php echo e($initial); ?></span>
                    <?php endif; ?>

                    <div>
                        <div class="fw-bold"><?php echo e($user['name']); ?></div>
                        <div class="text-secondary"><?php echo e($user['email']); ?></div>
                        <div class="mt-1">
                            <span class="badge <?php echo (int) ($user['lock_enabled'] ?? 0) === 1 ? 'bg-yellow-lt' : 'bg-secondary-lt'; ?>">
                                <?php echo (int) ($user['lock_enabled'] ?? 0) === 1 ? e(users_lang('lock_enabled')) : e(users_lang('lock_disabled')); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-8">
                <label class="form-label form-required"><?php echo e(users_lang('name_surname')); ?></label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="<?php echo e($user['name']); ?>"
                    required
                >
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label"><?php echo e(users_lang('role')); ?></label>
                <select name="role_id" class="form-select">
                    <option value=""><?php echo e(users_lang('select_role')); ?></option>
                    <?php foreach ($roles as $role): ?>
                        <option
                            value="<?php echo (int) $role['id']; ?>"
                            <?php echo (int) $user['role_id'] === (int) $role['id'] ? 'selected' : ''; ?>
                        >
                            <?php echo e($role['name'] . ((int) ($role['is_active'] ?? 1) !== 1 ? users_lang('status_inactive_suffix') : '')); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ((int) ($user['role_id'] ?? 0) > 0): ?>
                    <small class="form-hint"><?php echo e(users_lang('passive_role_info_hint')); ?></small>
                <?php endif; ?>
            </div>

            <div class="col-12">
                <label class="form-label form-required"><?php echo e(users_lang('email')); ?></label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo e($user['email']); ?>"
                    required
                >
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label"><?php echo e(users_lang('new_password')); ?></label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="<?php echo e(users_lang('password_optional_placeholder')); ?>"
                >
                <small class="form-hint"><?php echo e(users_lang('password_optional_hint')); ?></small>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label"><?php echo e(users_lang('new_password_repeat')); ?></label>
                <input
                    type="password"
                    name="password_confirm"
                    class="form-control"
                    placeholder="<?php echo e(users_lang('password_optional_placeholder')); ?>"
                >
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label"><?php echo e(users_lang('profile_image')); ?></label>
                <input
                    type="file"
                    name="avatar"
                    class="form-control"
                    accept=".jpg,.jpeg,.png,.webp"
                >
                <small class="form-hint"><?php echo e(users_lang('profile_image_replace_hint')); ?></small>
            </div>

            <div class="col-12 col-md-6 d-flex align-items-end">
                <label class="form-check form-switch m-0">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="form-check-input"
                        <?php echo (int) $user['is_active'] === 1 ? 'checked' : ''; ?>
                    >
                    <span class="form-check-label"><?php echo e(users_lang('user_active_switch')); ?></span>
                </label>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <?php if ($canDropSession || $canResetLockKey): ?>
            <div class="me-auto d-flex gap-2">
                <?php if ($canDropSession): ?>
                    <a href="#" class="btn btn-outline-warning" data-confirm="<?php echo e(users_lang('drop_session_confirm')); ?>" data-form="users-drop-session-form-<?php echo (int) $user['id']; ?>">
                        <?php echo e(users_lang('drop_session')); ?>
                    </a>
                <?php endif; ?>

                <?php if ($canResetLockKey): ?>
                    <a href="#" class="btn btn-outline-secondary" data-confirm="<?php echo e(users_lang('reset_key_confirm')); ?>" data-form="users-reset-lock-form-<?php echo (int) $user['id']; ?>">
                        <?php echo e(users_lang('reset_key')); ?>
                    </a>
                <?php endif; ?>
            </div>
            <button type="button" class="btn" data-bs-dismiss="modal"><?php echo e(users_lang('cancel')); ?></button>
        <?php else: ?>
            <button type="button" class="btn me-auto" data-bs-dismiss="modal"><?php echo e(users_lang('cancel')); ?></button>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary" id="users-edit-submit-button"><?php echo e(users_lang('update')); ?></button>
    </div>
</form>

<?php if ($canDropSession): ?>
    <form id="users-drop-session-form-<?php echo (int) $user['id']; ?>" action="<?php echo base_url('users/actions/drop-session'); ?>" method="post" data-ajax="true" class="d-none">
        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
    </form>
<?php endif; ?>

<?php if ($canResetLockKey): ?>
    <form id="users-reset-lock-form-<?php echo (int) $user['id']; ?>" action="<?php echo base_url('users/actions/reset-lock-key'); ?>" method="post" data-ajax="true" class="d-none">
        <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
        <input type="hidden" name="id" value="<?php echo (int) $user['id']; ?>">
    </form>
<?php endif; ?>
