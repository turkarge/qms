<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/profile/language.php';
if (is_file(BASE_PATH . '/modules/organization/language.php')) {
    require_once BASE_PATH . '/modules/organization/language.php';
}
if (is_file(BASE_PATH . '/modules/organization/helpers.php')) {
    require_once BASE_PATH . '/modules/organization/helpers.php';
}

$currentUser = current_user();
$profile = null;
$lockSchemaReady = kirpi_auth_lock_schema_ready();
$defaultCompanySchemaReady = db_table_exists('users') && db_column_exists('users', 'default_company_id');

if (!$currentUser || !isset($currentUser['id'])) {
    display_error_page(
        profile_lang('forbidden_title'),
        profile_lang('forbidden_message'),
        403,
        true
    );
}

try {
    $stmt = db()->prepare("\n        SELECT\n            u.id,\n            u.name,\n            u.email,\n            u.avatar,\n            u.is_active,\n            " . ($defaultCompanySchemaReady ? "u.default_company_id" : "NULL AS default_company_id") . ",\n            " . ($lockSchemaReady ? "u.lock_enabled" : "0 AS lock_enabled") . ",\n            r.name AS role_name\n        FROM users u\n        LEFT JOIN roles r ON r.id = u.role_id\n        WHERE u.id = :id\n        LIMIT 1\n    ");
    $stmt->execute([
        ':id' => (int) $currentUser['id'],
    ]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        throw new RuntimeException('Profile not found.');
    }
} catch (Throwable $e) {
    error_log('profile page error: ' . $e->getMessage());

    display_error_page(
        profile_lang('load_error_title'),
        profile_lang('load_error_message'),
        500,
        true
    );
}

$avatarUrl = !empty($profile['avatar'])
    ? base_url('uploads/avatars/' . ltrim($profile['avatar'], '/'))
    : null;

$initial = mb_strtoupper(mb_substr($profile['name'] ?? 'U', 0, 1));
$isSuperAdmin = ((string) ($profile['role_name'] ?? '')) === 'Super Admin';
$apiTokenOnce = $_SESSION['profile_api_token_once'] ?? null;
if (isset($_SESSION['profile_api_token_once'])) {
    unset($_SESSION['profile_api_token_once']);
}
$apiTokenCopyMap = isset($_SESSION['profile_api_token_copy_map']) && is_array($_SESSION['profile_api_token_copy_map'])
    ? $_SESSION['profile_api_token_copy_map']
    : [];
$apiEnabled = api_is_enabled();
$apiTokenTableReady = api_token_table_ready();
$apiTokenRows = $isSuperAdmin ? api_list_tokens_for_user((int) ($profile['id'] ?? 0), 100) : [];
$lockEnabled = $lockSchemaReady && (int) ($profile['lock_enabled'] ?? 0) === 1;
$profileCompanyOptions = ($defaultCompanySchemaReady && function_exists('organization_accessible_companies'))
    ? organization_accessible_companies($currentUser)
    : [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo e(profile_lang('my_account')); ?></div>
                <h2 class="page-title"><?php echo e(profile_lang('profile')); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if ($avatarUrl): ?>
                            <span
                                class="avatar avatar-xl mb-3"
                                style="width: 96px; height: 96px; background-image: url('<?php echo e($avatarUrl); ?>')"
                            ></span>
                        <?php else: ?>
                            <span class="avatar avatar-xl mb-3" style="width: 96px; height: 96px;">
                                <?php echo e($initial); ?>
                            </span>
                        <?php endif; ?>

                        <h3 class="m-0 mb-1"><?php echo e($profile['name']); ?></h3>
                        <div class="text-secondary"><?php echo e($profile['email']); ?></div>

                        <div class="mt-3">
                            <span class="badge bg-blue-lt"><?php echo e($profile['role_name'] ?: profile_lang('no_role')); ?></span>
                            <span class="badge <?php echo (int) $profile['is_active'] === 1 ? 'bg-green-lt' : 'bg-red-lt'; ?>">
                                <?php echo (int) $profile['is_active'] === 1 ? e(profile_lang('active')) : e(profile_lang('passive')); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-8">
                <?php if ($isSuperAdmin): ?>
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                <li class="nav-item">
                                    <a href="#tab-profile-info" class="nav-link active" data-bs-toggle="tab"><?php echo e(profile_lang('profile_info')); ?></a>
                                </li>
                                <li class="nav-item">
                                    <a href="#tab-profile-api-tokens" class="nav-link" data-bs-toggle="tab"><?php echo e(profile_lang('api_tokens')); ?></a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-0">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tab-profile-info">
                <?php endif; ?>

                <div class="<?php echo $isSuperAdmin ? '' : 'card'; ?>">
                    <?php if (!$isSuperAdmin): ?>
                    <div class="card-header">
                        <h3 class="card-title"><?php echo e(profile_lang('profile_info')); ?></h3>
                    </div>
                    <?php endif; ?>

                    <form id="profile-update-form" action="<?php echo base_url('profile/actions/update'); ?>" method="post" enctype="multipart/form-data" data-ajax="true">
                        <div class="card-body">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">

                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label form-required"><?php echo e(profile_lang('name_surname')); ?></label>
                                    <input type="text" name="name" class="form-control" value="<?php echo e($profile['name']); ?>" required>
                                </div>

                                <div class="col-12">
                                    <label class="form-label form-required"><?php echo e(profile_lang('email')); ?></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo e($profile['email']); ?>" required>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label"><?php echo e(profile_lang('new_password')); ?></label>
                                    <input type="password" name="password" class="form-control" placeholder="<?php echo e(profile_lang('password_placeholder')); ?>">
                                    <small class="form-hint"><?php echo e(profile_lang('password_hint')); ?></small>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label"><?php echo e(profile_lang('new_password_repeat')); ?></label>
                                    <input type="password" name="password_confirm" class="form-control" placeholder="<?php echo e(profile_lang('password_placeholder')); ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label"><?php echo e(profile_lang('avatar')); ?></label>
                                    <input type="file" name="avatar" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                    <small class="form-hint"><?php echo e(profile_lang('avatar_hint')); ?></small>
                                </div>

                                <?php if ($defaultCompanySchemaReady && count($profileCompanyOptions) > 0): ?>
                                    <div class="col-12">
                                        <label class="form-label"><?php echo e(organization_lang('default_company')); ?></label>
                                        <select name="default_company_id" class="form-select">
                                            <option value=""><?php echo e(organization_lang('select_company')); ?></option>
                                            <?php foreach ($profileCompanyOptions as $companyOption): ?>
                                                <?php $companyOptionId = (int) ($companyOption['id'] ?? 0); ?>
                                                <option value="<?php echo $companyOptionId; ?>" <?php echo $companyOptionId === (int) ($profile['default_company_id'] ?? 0) ? 'selected' : ''; ?>>
                                                    <?php echo e((string) ($companyOption['company_name'] ?? '')); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-hint">Sistem acildiginda aktif firma bu secime gore gelir.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary"><?php echo e(profile_lang('update_profile')); ?></button>
                        </div>
                    </form>
                </div>

                <?php if ($isSuperAdmin): ?>
                                </div>
                                <div class="tab-pane" id="tab-profile-api-tokens">
                                    <div>
                                        <div class="card-header">
                                            <h3 class="card-title"><?php echo e(profile_lang('api_token_management')); ?></h3>
                                        </div>
                                        <div class="card-body">
                                            <?php if (is_array($apiTokenOnce) && !empty($apiTokenOnce['token'])): ?>
                                                <div class="alert alert-warning">
                                                    <div class="fw-bold mb-3"><?php echo e(profile_lang('token_once_warning')); ?></div>
                                                    <div class="mb-2 w-100">
                                                        <label class="form-label mb-1"><?php echo e(profile_lang('token')); ?></label>
                                                        <div class="input-group">
                                                            <input type="text" class="form-control w-100" readonly value="<?php echo e((string) ($apiTokenOnce['token'] ?? '')); ?>">
                                                            <button type="button" class="btn btn-outline-secondary js-token-copy-btn" data-token="<?php echo e((string) ($apiTokenOnce['token'] ?? '')); ?>" title="<?php echo e(profile_lang('copy_title')); ?>">
                                                                <?php echo e(profile_lang('copy')); ?>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="text-secondary small">
                                                        <?php echo e(profile_lang('token_name')); ?>: <?php echo e((string) ($apiTokenOnce['token_name'] ?? '-')); ?> |
                                                        <?php echo e(profile_lang('expires_at')); ?>: <?php echo !empty($apiTokenOnce['is_unlimited']) ? e(profile_lang('unlimited')) : e((string) ($apiTokenOnce['expires_at'] ?? '-')); ?> |
                                                        <?php echo e(profile_lang('scopes')); ?>: <?php echo e(implode(', ', (array) ($apiTokenOnce['scopes'] ?? ['*']))); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <form action="<?php echo base_url('profile/actions/create-api-token'); ?>" method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                                <div class="row g-3">
                                                    <div class="col-12 col-md-6">
                                                        <label class="form-label"><?php echo e(profile_lang('token_name')); ?></label>
                                                        <input type="text" name="token_name" class="form-control" placeholder="ornek: postman" value="profile-token" <?php echo (!$apiEnabled || !$apiTokenTableReady) ? 'disabled' : ''; ?>>
                                                    </div>
                                                    <div class="col-12 col-md-3">
                                                        <label class="form-label"><?php echo e(profile_lang('validity')); ?></label>
                                                        <select name="ttl_option" class="form-select" <?php echo (!$apiEnabled || !$apiTokenTableReady) ? 'disabled' : ''; ?>>
                                                            <option value="24h">24 Saat</option>
                                                            <option value="1_month" selected>1 Ay</option>
                                                            <option value="3_months">3 Ay</option>
                                                            <option value="6_months">6 Ay</option>
                                                            <option value="1_year">1 Yil</option>
                                                            <option value="unlimited"><?php echo e(profile_lang('unlimited')); ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 col-md-3">
                                                        <label class="form-label"><?php echo e(profile_lang('scope')); ?></label>
                                                        <select name="scope_option" class="form-select" <?php echo (!$apiEnabled || !$apiTokenTableReady) ? 'disabled' : ''; ?>>
                                                            <option value="full_access" selected><?php echo e(profile_lang('all_permissions')); ?></option>
                                                            <option value="profile_read"><?php echo e(profile_lang('profile_only')); ?></option>
                                                            <option value="users_read"><?php echo e(profile_lang('users_read')); ?></option>
                                                            <option value="users_manage"><?php echo e(profile_lang('users_manage')); ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-12 d-flex align-items-end">
                                                        <button type="submit" class="btn btn-outline-primary w-100" <?php echo (!$apiEnabled || !$apiTokenTableReady) ? 'disabled' : ''; ?>><?php echo e(profile_lang('create_api_token')); ?></button>
                                                    </div>
                                                </div>
                                            </form>

                                            <?php if (!$apiEnabled): ?>
                                                <div class="alert alert-warning mt-3 mb-0"><?php echo e(profile_lang('api_disabled_warning')); ?></div>
                                            <?php endif; ?>

                                            <?php if (!$apiTokenTableReady): ?>
                                                <div class="alert alert-warning mt-3 mb-0"><?php echo e(profile_lang('api_table_warning')); ?></div>
                                            <?php endif; ?>

                                            <hr class="my-4">
                                            <div class="table-responsive">
                                                <table data-kirpi-table="standard" data-table-title="API Tokenları" class="table table-vcenter card-table table-striped mb-0">
                                                    <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th><?php echo e(profile_lang('created')); ?></th>
                                                        <th><?php echo e(profile_lang('last_used')); ?></th>
                                                        <th><?php echo e(profile_lang('expires')); ?></th>
                                                        <th><?php echo e(profile_lang('scopes')); ?></th>
                                                        <th><?php echo e(profile_lang('status')); ?></th>
                                                        <th class="w-1"></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php if (empty($apiTokenRows)): ?>
                                                        <tr>
                                                            <td colspan="8" class="text-center text-secondary py-4"><?php echo e(profile_lang('no_tokens')); ?></td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($apiTokenRows as $tokenRow): ?>
                                                            <?php
                                                            $isRevoked = !empty($tokenRow['revoked_at']);
                                                            $expiresAtRaw = (string) ($tokenRow['expires_at'] ?? '');
                                                            $isUnlimitedToken = $expiresAtRaw !== '' && strtotime($expiresAtRaw) !== false && strtotime($expiresAtRaw) >= strtotime('2099-01-01 00:00:00');
                                                            $isExpired = !$isRevoked && !$isUnlimitedToken && $expiresAtRaw !== '' && strtotime($expiresAtRaw) !== false && strtotime($expiresAtRaw) < time();
                                                            $statusLabel = $isRevoked ? profile_lang('revoked') : ($isExpired ? profile_lang('expired') : profile_lang('active_en'));
                                                            $statusClass = $isRevoked ? 'bg-red-lt' : ($isExpired ? 'bg-yellow-lt' : 'bg-green-lt');
                                                            $tokenId = (int) ($tokenRow['id'] ?? 0);
                                                            $copyTokenValue = (string) ($apiTokenCopyMap[(string) $tokenId] ?? '');
                                                            $tokenScopes = (array) ($tokenRow['scopes'] ?? ['*']);
                                                            ?>
                                                            <tr>
                                                                <td><?php echo $tokenId; ?></td>
                                                                <td><?php echo e((string) ($tokenRow['token_name'] ?? 'default')); ?></td>
                                                                <td><?php echo e(kirpi_format_datetime((string) ($tokenRow['created_at'] ?? ''))); ?></td>
                                                                <td><?php echo e(kirpi_format_datetime((string) ($tokenRow['last_used_at'] ?? ''))); ?></td>
                                                                <td><?php echo e($isUnlimitedToken ? profile_lang('unlimited') : kirpi_format_datetime($expiresAtRaw)); ?></td>
                                                                <td><code><?php echo e(implode(', ', $tokenScopes)); ?></code></td>
                                                                <td><span class="badge <?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span></td>
                                                                <td>
                                                                    <?php if (!$isRevoked && !$isExpired): ?>
                                                                        <div class="d-flex gap-2">
                                                                            <button
                                                                                type="button"
                                                                                class="btn btn-sm btn-outline-secondary js-token-copy-btn"
                                                                                data-token="<?php echo e($copyTokenValue); ?>"
                                                                                title="<?php echo $copyTokenValue !== '' ? e(profile_lang('copy_title')) : e(profile_lang('copy_disabled_title')); ?>"
                                                                                <?php echo $copyTokenValue === '' ? 'disabled' : ''; ?>
                                                                            >
                                                                                <i class="ti ti-copy"></i>
                                                                            </button>

                                                                            <form id="profile-revoke-token-form-<?php echo $tokenId; ?>" action="<?php echo base_url('profile/actions/revoke-api-token'); ?>" method="post" class="d-none">
                                                                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                                                                <input type="hidden" name="token_id" value="<?php echo $tokenId; ?>">
                                                                            </form>
                                                                            <a href="#" class="btn btn-sm btn-outline-danger" data-confirm="<?php echo e(profile_lang('revoke_confirm')); ?>" data-form="profile-revoke-token-form-<?php echo $tokenId; ?>"><?php echo e(profile_lang('revoke')); ?></a>
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <span class="text-secondary small">-</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($lockSchemaReady): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h3 class="card-title"><?php echo e(profile_lang('lock_key_title')); ?></h3>
                        </div>
                        <form action="<?php echo base_url('profile/actions/lock-settings'); ?>" method="post" data-ajax="true">
                            <div class="card-body">
                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">

                                <div class="mb-3">
                                    <label class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" name="lock_enabled" value="1" <?php echo $lockEnabled ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo e(profile_lang('lock_enabled')); ?></span>
                                    </label>
                                    <div class="form-hint"><?php echo e(profile_lang('lock_hint')); ?></div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label"><?php echo e(profile_lang('new_key')); ?></label>
                                        <input type="password" name="lock_pin" class="form-control" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" placeholder="<?php echo e(profile_lang('key_placeholder')); ?>">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label"><?php echo e(profile_lang('key_repeat')); ?></label>
                                        <input type="password" name="lock_pin_confirm" class="form-control" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" placeholder="<?php echo e(profile_lang('key_placeholder')); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer text-end">
                                <button type="submit" class="btn btn-outline-primary"><?php echo e(profile_lang('save_key')); ?></button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".js-token-copy-btn").forEach(function (button) {
        button.addEventListener("click", async function () {
            const value = String(button.dataset.token || "");
            if (!value) {
                if (window.KirpiCore && typeof window.KirpiCore.toast === "function") {
                    window.KirpiCore.toast("<?php echo e(profile_lang('copy_not_allowed')); ?>", "warning");
                }
                return;
            }

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const tempInput = document.createElement("input");
                    tempInput.value = value;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    document.execCommand("copy");
                    document.body.removeChild(tempInput);
                }

                if (window.KirpiCore && typeof window.KirpiCore.toast === "function") {
                    window.KirpiCore.toast("<?php echo e(profile_lang('copy_success')); ?>", "success");
                }
            } catch (error) {
                if (window.KirpiCore && typeof window.KirpiCore.toast === "function") {
                    window.KirpiCore.toast("<?php echo e(profile_lang('copy_error')); ?>", "error");
                }
            }
        });
    });
});
</script>
