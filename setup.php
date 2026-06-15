<?php

define('BASE_PATH', __DIR__);
define('KIRPI_CORE_ENTRY', true);

require_once BASE_PATH . '/core/config.php';
require_once BASE_PATH . '/core/database.php';
require_once BASE_PATH . '/core/functions.php';
require_once BASE_PATH . '/core/setup.php';

$acceptHeader = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
$wantsJson = (($_GET['format'] ?? '') === 'json') || str_contains($acceptHeader, 'application/json');
$setupKey = trim((string) env('SETUP_KEY', ''));

function setup_response(array $payload, int $statusCode = 200): never
{
    global $wantsJson;

    if ($wantsJson) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    $status = $payload['status'] ?? 'info';
    $title = ($status === 'success') ? 'Kurulum Tamamlandi' : 'Kurulum';
    $message = (string) ($payload['message'] ?? '');
    $result = $payload['result'] ?? [];

    ?>
    <!doctype html>
    <html lang="tr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>KirpiCore Setup</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f5f7fb; margin:0; padding:2rem; color:#1f2937; }
            .card { max-width:900px; margin:0 auto; background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:1.25rem; }
            h1 { margin:0 0 1rem; font-size:1.4rem; }
            .msg { padding:.75rem; border-radius:8px; margin-bottom:1rem; }
            .ok { background:#ecfdf3; border:1px solid #bbf7d0; color:#166534; }
            .err { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
            .grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; }
            .row { margin-bottom:.75rem; }
            label { font-weight:600; display:block; margin-bottom:.3rem; }
            input { width:100%; box-sizing:border-box; padding:.6rem .7rem; border:1px solid #d1d5db; border-radius:8px; }
            button { background:#2563eb; color:#fff; border:0; border-radius:8px; padding:.65rem 1rem; cursor:pointer; }
            table { width:100%; border-collapse:collapse; margin-top:.75rem; }
            th, td { text-align:left; border:1px solid #e5e7eb; padding:.45rem; font-size:.9rem; }
            code { background:#f3f4f6; padding:.1rem .3rem; border-radius:4px; }
        </style>
    </head>
    <body>
    <div class="card">
        <h1><?php echo e($title); ?></h1>

        <?php if ($message !== ''): ?>
            <div class="msg <?php echo ($status === 'success') ? 'ok' : 'err'; ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($status !== 'success'): ?>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                <div class="grid">
                    <div class="row">
                        <label>SETUP_KEY</label>
                        <input type="password" name="setup_key" required>
                    </div>
                    <div class="row">
                        <label>Admin Adi</label>
                        <input type="text" name="admin_name" value="Admin" required>
                    </div>
                    <div class="row">
                        <label>Admin E-posta</label>
                        <input type="email" name="admin_email" value="admin@kirpi.local" required>
                    </div>
                    <div class="row">
                        <label>Admin Sifre</label>
                        <input type="password" name="admin_password" required>
                    </div>
                    <div class="row">
                        <label>Admin Sifre Tekrar</label>
                        <input type="password" name="admin_password_confirm" required>
                    </div>
                </div>
                <button type="submit">Kurulumu Baslat</button>
            </form>
        <?php else: ?>
            <p><strong>Kurulum Ozeti</strong></p>
            <ul>
                <li>Core statement: <?php echo (int) ($result['core_statements'] ?? 0); ?></li>
                <li>Module statement: <?php echo (int) ($result['module_statements'] ?? 0); ?></li>
            </ul>

            <?php if (!empty($result['installed_files']) && is_array($result['installed_files'])): ?>
                <p><strong>Kurulan Modul Dosyalari</strong></p>
                <table>
                    <thead><tr><th>Dosya</th><th>Statement</th></tr></thead>
                    <tbody>
                    <?php foreach ($result['installed_files'] as $item): ?>
                        <tr>
                            <td><?php echo e((string) ($item['file'] ?? '')); ?></td>
                            <td><?php echo (int) ($item['statements'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($result['tables']) && is_array($result['tables'])): ?>
                <p><strong>Olusan Tablolar</strong></p>
                <p><?php echo e(implode(', ', $result['tables'])); ?></p>
            <?php endif; ?>

            <?php if (!empty($result['roles']) && is_array($result['roles'])): ?>
                <p><strong>Roller</strong></p>
                <table>
                    <thead><tr><th>ID</th><th>Ad</th><th>Aktif</th></tr></thead>
                    <tbody>
                    <?php foreach ($result['roles'] as $role): ?>
                        <tr>
                            <td><?php echo (int) ($role['id'] ?? 0); ?></td>
                            <td><?php echo e((string) ($role['name'] ?? '')); ?></td>
                            <td><?php echo (int) ($role['is_active'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php if (!empty($result['users']) && is_array($result['users'])): ?>
                <p><strong>Kullanicilar</strong></p>
                <table>
                    <thead><tr><th>ID</th><th>Ad</th><th>E-posta</th><th>Rol</th><th>Aktif</th></tr></thead>
                    <tbody>
                    <?php foreach ($result['users'] as $u): ?>
                        <tr>
                            <td><?php echo (int) ($u['id'] ?? 0); ?></td>
                            <td><?php echo e((string) ($u['name'] ?? '')); ?></td>
                            <td><?php echo e((string) ($u['email'] ?? '')); ?></td>
                            <td><?php echo (int) ($u['role_id'] ?? 0); ?></td>
                            <td><?php echo (int) ($u['is_active'] ?? 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <p>Setup sonrasinda onerilen: <code>AUTO_WEB_SETUP=false</code> ve <code>SETUP_KEY</code> degerini degistir/kapat.</p>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
    exit;
}

if (APP_ENV === 'production' && $setupKey === '') {
    setup_response([
        'status' => 'error',
        'message' => 'SETUP_KEY tanimlanmamis. Production ortaminda setup.php icin SETUP_KEY zorunlu.',
    ], 403);
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setup_response([
        'status' => 'info',
        'message' => 'Kurulumu baslatmak icin formu doldurun.',
    ]);
}

$providedKey = trim((string) ($_POST['setup_key'] ?? ''));
if ($setupKey !== '' && !hash_equals($setupKey, $providedKey)) {
    setup_response([
        'status' => 'error',
        'message' => 'Gecersiz setup key.',
    ], 403);
}

$adminName = trim((string) ($_POST['admin_name'] ?? 'Admin'));
$adminEmail = trim((string) ($_POST['admin_email'] ?? 'admin@kirpi.local'));
$adminPassword = (string) ($_POST['admin_password'] ?? '');
$adminPasswordConfirm = (string) ($_POST['admin_password_confirm'] ?? '');

if ($adminName === '' || $adminEmail === '' || $adminPassword === '') {
    setup_response([
        'status' => 'error',
        'message' => 'Admin alanlari zorunludur.',
    ], 422);
}

if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
    setup_response([
        'status' => 'error',
        'message' => 'Gecerli bir admin e-posta girin.',
    ], 422);
}

if (mb_strlen($adminPassword) < 6) {
    setup_response([
        'status' => 'error',
        'message' => 'Admin sifresi en az 6 karakter olmali.',
    ], 422);
}

if ($adminPassword !== $adminPasswordConfirm) {
    setup_response([
        'status' => 'error',
        'message' => 'Admin sifreleri uyusmuyor.',
    ], 422);
}

try {
    $result = kirpi_install_database_schema();

    db()->exec("
        INSERT INTO roles (name, is_active)
        VALUES ('Super Admin', 1)
        ON DUPLICATE KEY UPDATE is_active = VALUES(is_active)
    ");
    db()->exec("
        INSERT INTO roles (name, is_active)
        VALUES ('Default User', 1)
        ON DUPLICATE KEY UPDATE is_active = VALUES(is_active)
    ");

    $adminPasswordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    $adminStmt = db()->prepare("
        INSERT INTO users (role_id, name, email, password, is_active)
        VALUES (
            (SELECT id FROM roles WHERE name = 'Super Admin' LIMIT 1),
            :name,
            :email,
            :password,
            1
        )
        ON DUPLICATE KEY UPDATE
            role_id = VALUES(role_id),
            name = VALUES(name),
            password = VALUES(password),
            is_active = VALUES(is_active)
    ");
    $adminStmt->execute([
        ':name' => $adminName,
        ':email' => $adminEmail,
        ':password' => $adminPasswordHash,
    ]);

    $tablesStmt = db()->query("
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY table_name ASC
    ");
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

    $rolesStmt = db()->query("SELECT id, name, is_active FROM roles ORDER BY id ASC");
    $roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $usersStmt = db()->query("SELECT id, name, email, role_id, is_active FROM users ORDER BY id ASC");
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $result['tables'] = $tables;
    $result['roles'] = $roles;
    $result['users'] = $users;

    setup_response([
        'status' => 'success',
        'message' => 'Kurulum tamamlandi.',
        'result' => $result,
    ], 200);
} catch (Throwable $e) {
    setup_response([
        'status' => 'error',
        'message' => 'Kurulum basarisiz oldu.',
        'error' => APP_DEBUG ? $e->getMessage() : 'internal_error',
    ], 500);
}
