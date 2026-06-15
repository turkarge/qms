<?php

$root = dirname(__DIR__);
$page = file_get_contents($root . '/modules/backup/pages/view.php');
$language = file_get_contents($root . '/modules/backup/language.php');
$script = file_get_contents($root . '/modules/backup/scripts/view.js');
$app = file_get_contents($root . '/assets/js/app.js');

$assertions = [
    'status region' => str_contains($page, 'id="backup-operation-status"'),
    'create operation marker' => str_contains($page, 'data-backup-operation="create"'),
    'verify operation marker' => str_contains($page, 'data-backup-operation="verify"'),
    'restore operation marker' => str_contains($page, 'data-backup-operation="restore"'),
    'delete operation marker' => str_contains($page, 'data-backup-operation="delete"'),
    'localized working messages' => str_contains($language, "'working_create'") && str_contains($language, "'operation_failed'"),
    'control locking' => str_contains($script, 'setControlsLocked(true)') && str_contains($script, 'setControlsLocked(false)'),
    'visible status lifecycle' => str_contains($script, 'kirpi:form.start')
        && str_contains($script, 'kirpi:form.success')
        && str_contains($script, 'kirpi:form.error')
        && str_contains($script, 'kirpi:form.complete'),
    'global ajax lifecycle' => str_contains($app, 'new CustomEvent("kirpi:form.start"')
        && str_contains($app, 'new CustomEvent("kirpi:form.error"')
        && str_contains($app, 'new CustomEvent("kirpi:form.complete"'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Backup operation feedback contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Backup operation feedback contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
