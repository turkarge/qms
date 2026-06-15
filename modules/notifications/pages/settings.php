<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/notifications/language.php';

$currentUser = current_user();
$userId = (int) ($currentUser['id'] ?? 0);
$settingsTableReady = db_table_exists('notification_settings');
$settings = [
    'email_enabled' => 1,
    'in_app_enabled' => 1,
];

if ($settingsTableReady && $userId > 0) {
    try {
        $stmt = db()->prepare("
            SELECT email_enabled, in_app_enabled
            FROM notification_settings
            WHERE user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([
            ':user_id' => $userId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $settings = $row;
        }
    } catch (Throwable $e) {
        error_log('notification settings page error: ' . $e->getMessage());
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(notifications_lang('settings_center')); ?></div>
                <h2 class="page-title"><?php echo e(notifications_lang('settings_title')); ?></h2>
            </div>

            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="<?php echo base_url('notifications/list'); ?>" class="btn"><?php echo e(notifications_lang('back_to_list')); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$settingsTableReady): ?>
            <div class="alert alert-warning">
                <?php echo e(notifications_lang('settings_table_missing')); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form
                id="notifications-settings-form"
                action="<?php echo base_url('notifications/actions/settings-update'); ?>"
                method="post"
                data-ajax="true"
            >
                <div class="card-body">
                    <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-check form-switch m-0">
                                <input
                                    type="checkbox"
                                    name="email_enabled"
                                    value="1"
                                    class="form-check-input"
                                    <?php echo (int) ($settings['email_enabled'] ?? 0) === 1 ? 'checked' : ''; ?>
                                    <?php echo !$settingsTableReady ? 'disabled' : ''; ?>
                                >
                                <span class="form-check-label"><?php echo e(notifications_lang('email_enabled')); ?></span>
                            </label>
                        </div>

                        <div class="col-12">
                            <label class="form-check form-switch m-0">
                                <input
                                    type="checkbox"
                                    name="in_app_enabled"
                                    value="1"
                                    class="form-check-input"
                                    <?php echo (int) ($settings['in_app_enabled'] ?? 0) === 1 ? 'checked' : ''; ?>
                                    <?php echo !$settingsTableReady ? 'disabled' : ''; ?>
                                >
                                <span class="form-check-label"><?php echo e(notifications_lang('in_app_enabled')); ?></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary" <?php echo !$settingsTableReady ? 'disabled' : ''; ?>>
                        <?php echo e(notifications_lang('save_settings')); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
