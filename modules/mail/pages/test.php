<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/mail/language.php';

$configStatus = kirpi_mail_config_status();
$currentUser = current_user();
$defaultRecipient = (string) ($currentUser['email'] ?? '');
$templateRoute = route_exists('template/email') && check_permission('template.view') ? 'template/email' : 'mail/templates';
$canManageTemplates = route_exists($templateRoute) && (check_permission('template.view') || check_permission('mail.view'));

$mailLogs = [];
if (db_table_exists('mail_logs')) {
    try {
        $stmt = db()->query("
            SELECT id, recipient_email, subject, transport, status, error_message, created_at
            FROM mail_logs
            ORDER BY id DESC
            LIMIT 20
        ");
        $mailLogs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('mail test page logs error: ' . $e->getMessage());
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(mail_lang('mail_center')); ?></div>
                <h2 class="page-title"><?php echo e(mail_lang('mail_test_status')); ?></h2>
            </div>
            <?php if ($canManageTemplates): ?>
                <div class="col-auto ms-auto d-print-none">
                    <a href="<?php echo base_url($templateRoute); ?>" class="btn btn-outline-primary">
                        <?php echo e(mail_lang('manage_templates')); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-4">
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(mail_lang('mail_configuration')); ?></h3>
                    </div>
                    <div class="table-responsive">
                        <table data-kirpi-table="compact" data-table-title="Mail Yapılandırması" class="table table-vcenter card-table table-striped">
                            <thead>
                            <tr>
                                <th><?php echo e(mail_lang('check')); ?></th>
                                <th><?php echo e(mail_lang('status')); ?></th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo e(mail_lang('transport')); ?></td>
                                    <td><code><?php echo e($configStatus['transport']); ?></code></td>
                                </tr>
                                <tr>
                                    <td>MAIL_HOST</td>
                                    <td><?php echo $configStatus['mail_host'] ? '<span class="badge bg-green-lt">' . e(mail_lang('defined')) . '</span>' : '<span class="badge bg-red-lt">' . e(mail_lang('empty')) . '</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td>MAIL_PORT</td>
                                    <td><?php echo $configStatus['mail_port'] ? '<span class="badge bg-green-lt">' . e(mail_lang('defined')) . '</span>' : '<span class="badge bg-red-lt">' . e(mail_lang('invalid')) . '</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td>MAIL_USERNAME</td>
                                    <td><?php echo $configStatus['mail_username'] ? '<span class="badge bg-green-lt">' . e(mail_lang('defined')) . '</span>' : '<span class="badge bg-orange-lt">' . e(mail_lang('empty')) . '</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td>MAIL_PASSWORD</td>
                                    <td><?php echo $configStatus['mail_password'] ? '<span class="badge bg-green-lt">' . e(mail_lang('defined')) . '</span>' : '<span class="badge bg-orange-lt">' . e(mail_lang('empty')) . '</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td>MAIL_FROM_ADDRESS</td>
                                    <td><?php echo $configStatus['mail_from_address'] ? '<span class="badge bg-green-lt">' . e(mail_lang('defined')) . '</span>' : '<span class="badge bg-red-lt">' . e(mail_lang('empty')) . '</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td>MAIL_ENCRYPTION</td>
                                    <td><?php echo $configStatus['mail_encryption'] ? '<span class="badge bg-green-lt">' . e(mail_lang('valid')) . '</span>' : '<span class="badge bg-red-lt">' . e(mail_lang('invalid')) . '</span>'; ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo e(mail_lang('ready')); ?></td>
                                    <td><?php echo $configStatus['ready'] ? '<span class="badge bg-green">' . e(mail_lang('ready')) . '</span>' : '<span class="badge bg-red">' . e(mail_lang('missing')) . '</span>'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-7">
                <div class="card">
                    <form action="<?php echo base_url('mail/actions/send-test'); ?>" method="post" data-ajax="true">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo e(mail_lang('send_test_email')); ?></h3>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">

                            <div class="mb-3">
                                <label class="form-label"><?php echo e(mail_lang('recipient_email')); ?></label>
                                <input type="email" name="recipient_email" class="form-control" required value="<?php echo e($defaultRecipient); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><?php echo e(mail_lang('subject')); ?></label>
                                <input type="text" name="subject" class="form-control" required value="<?php echo e(mail_lang('default_subject')); ?>">
                            </div>

                            <div>
                                <label class="form-label"><?php echo e(mail_lang('message')); ?></label>
                                <textarea name="message" rows="6" class="form-control" required><?php echo e(mail_lang('default_message')); ?></textarea>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary" <?php echo !$configStatus['ready'] ? 'disabled' : ''; ?>><?php echo e(mail_lang('send_test_button')); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title"><?php echo e(mail_lang('recent_mail_logs')); ?></h3>
            </div>
            <div class="table-responsive">
                <table data-kirpi-table="report" data-table-title="Mail Logları" class="table table-vcenter card-table table-striped">
                    <thead>
                    <tr>
                        <th><?php echo e(mail_lang('date')); ?></th>
                        <th><?php echo e(mail_lang('recipient')); ?></th>
                        <th><?php echo e(mail_lang('subject')); ?></th>
                        <th><?php echo e(mail_lang('transport')); ?></th>
                        <th><?php echo e(mail_lang('status')); ?></th>
                        <th><?php echo e(mail_lang('error')); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($mailLogs)): ?>
                            <tr>
                                <td colspan="6" class="text-secondary"><?php echo e(mail_lang('no_mail_logs')); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mailLogs as $log): ?>
                                <tr>
                                    <td><?php echo e((string) ($log['created_at'] ?? '')); ?></td>
                                    <td><?php echo e((string) ($log['recipient_email'] ?? '')); ?></td>
                                    <td><?php echo e((string) ($log['subject'] ?? '')); ?></td>
                                    <td><code><?php echo e((string) ($log['transport'] ?? '')); ?></code></td>
                                    <td>
                                        <?php if (($log['status'] ?? '') === 'sent'): ?>
                                            <span class="badge bg-green-lt"><?php echo e(mail_lang('sent')); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-red-lt"><?php echo e(mail_lang('failed')); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-secondary"><?php echo e((string) ($log['error_message'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
