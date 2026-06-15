<?php

$root = dirname(__DIR__);
$nav = file_get_contents($root . '/layouts/nav.php');
$app = file_get_contents($root . '/assets/js/app.js');
$list = file_get_contents($root . '/modules/notifications/scripts/list.js');
$markRead = file_get_contents($root . '/modules/notifications/actions/mark_read.php');
$markAllRead = file_get_contents($root . '/modules/notifications/actions/mark_all_read.php');
$css = file_get_contents($root . '/assets/css/app.css');

$assertions = [
    'tabler numeric unread badge' => str_contains($nav, 'badge badge-sm bg-red text-red-fg')
        && str_contains($nav, 'js-notification-count'),
    'single read control' => str_contains($nav, 'js-notification-mark-read'),
    'mark all control' => str_contains($nav, 'js-notification-mark-all'),
    'compact tabler list' => str_contains($nav, 'list-group list-group-flush list-group-hoverable')
        && str_contains($nav, 'status-dot-animated bg-red'),
    'no custom detail metadata' => !str_contains($nav, 'kirpi-notification-source')
        && !str_contains($nav, 'kirpi-notification-meta'),
    'responsive shared dropdown' => str_contains($nav, 'nav-item dropdown d-flex me-3')
        && !str_contains($nav, 'nav-item d-md-none me-3'),
    'server unread count' => str_contains($markRead, "'unread_count' => \$unreadCount"),
    'server mark all count' => str_contains($markAllRead, "'unread_count' => 0"),
    'navbar read lifecycle' => str_contains($app, 'markNotificationItemAsRead')
        && str_contains($app, 'js-notification-mark-all'),
    'list direct action' => str_contains($list, 'js-notification-read')
        && !str_contains($list, 'js-kirpi-row-menu'),
    'native notification surface' => !str_contains($nav, 'kirpi-notification-menu')
        && !str_contains($nav, 'btn-ghost-secondary js-notification-mark-read')
        && !str_contains($css, '.js-notification-dot {')
        && !str_contains($css, '.kirpi-notification-list'),
    'plain tabler footer link' => str_contains($nav, '<div class="card-footer text-center">')
        && !str_contains($nav, 'btn btn-sm btn-ghost-secondary w-100'),
];

$failed = array_keys(array_filter($assertions, static fn (bool $passed): bool => !$passed));
if ($failed !== []) {
    fwrite(STDERR, 'Notification center contract failed: ' . implode(', ', $failed) . PHP_EOL);
    exit(1);
}

echo 'Notification center contract passed (' . count($assertions) . ' assertions).' . PHP_EOL;
