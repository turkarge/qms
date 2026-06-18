<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

require_once BASE_PATH . '/modules/settings/language.php';
require_once BASE_PATH . '/modules/notifications/language.php';
require_once BASE_PATH . '/modules/profile/language.php';
require_once BASE_PATH . '/modules/auth/language.php';
if (is_file(BASE_PATH . '/modules/organization/language.php')) {
    require_once BASE_PATH . '/modules/organization/language.php';
}
if (is_file(BASE_PATH . '/modules/organization/helpers.php')) {
    require_once BASE_PATH . '/modules/organization/helpers.php';
}

$user = current_user();
$currentRoutePath = $GLOBALS['current_route_path'] ?? '';
$unreadNotificationsCount = get_unread_notifications_count((int) ($user['id'] ?? 0));
$recentNotifications = get_recent_notifications((int) ($user['id'] ?? 0), 5);
$userAvatarUrl = !empty($user['avatar'])
    ? base_url('uploads/avatars/' . ltrim((string) $user['avatar'], '/'))
    : null;
$canUseLockFeature = $user
    && kirpi_auth_lock_schema_ready()
    && !empty($user['lock_enabled']);

$navToggleLabel = settings_lang('nav_toggle', 'Menuyu Ac/Kapat');
$navBellAria = notifications_lang('nav_bell_aria', notifications_lang('notifications', 'Notifications'));
$navNotificationsTitle = notifications_lang('notifications', 'Notifications');
$navNotificationsEmpty = notifications_lang('nav_empty', 'No notifications');
$navViewAllNotifications = notifications_lang('nav_view_all', 'View all notifications');
$navMarkNotificationRead = notifications_lang('nav_mark_read', 'Mark as read');
$navMarkAllNotificationsRead = notifications_lang('nav_mark_all_read', 'Mark all as read');
$navOpenNotification = notifications_lang('nav_open_notification', 'Open notification');
$navProfileLabel = profile_lang('profile', 'Profile');
$navUserMenuAria = profile_lang('nav_user_menu', 'User Menu');
$navUserFallback = profile_lang('user_fallback', 'User');
$navLockAction = auth_lang('nav_lock_session', 'Lock Session');
$navLogout = auth_lang('nav_logout', 'Logout');
$activeCompanyOptions = [];
$activeCompanyId = null;
if (
    $user
    && function_exists('organization_accessible_companies')
    && function_exists('organization_active_company_id')
    && route_exists('organization/actions/set-active-company')
    && check_permission('organization.view')
) {
    $activeCompanyOptions = organization_accessible_companies($user);
    $activeCompanyId = organization_active_company_id($user);
}

$menu = function_exists('kirpi_navigation_menu_tree') ? kirpi_navigation_menu_tree() : [];

$filterVisibleMenuItems = static function (array $items) use (&$filterVisibleMenuItems): array {
    $visibleItems = [];

    foreach ($items as $item) {
        $hasChildren = isset($item['children']) && is_array($item['children']);

        if ($hasChildren) {
            $visibleChildren = $filterVisibleMenuItems($item['children']);
            if (empty($visibleChildren)) {
                continue;
            }

            $item['children'] = $visibleChildren;
            $visibleItems[] = $item;
            continue;
        }

        if (!route_exists($item['url'] ?? '')) {
            continue;
        }

        if (($item['permission'] ?? null) && !check_permission($item['permission'])) {
            continue;
        }

        $visibleItems[] = $item;
    }

    return $visibleItems;
};

$isMenuItemActive = static function (array $item, string $routePath) use (&$isMenuItemActive): bool {
    if (!empty($item['url']) && $item['url'] === $routePath) {
        return true;
    }

    if (isset($item['children']) && is_array($item['children'])) {
        foreach ($item['children'] as $childItem) {
            if ($isMenuItemActive($childItem, $routePath)) {
                return true;
            }
        }
    }

    return false;
};
?>

<header class="navbar navbar-expand-md d-print-none">
    <div class="container-xl">
        <button class="navbar-toggler" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbar-menu"
            aria-controls="navbar-menu" aria-expanded="false" aria-label="<?php echo e($navToggleLabel); ?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <a href="<?php echo base_url(APP_DEFAULT_ROUTE); ?>" class="navbar-brand navbar-brand-autodark me-3">
            <img src="<?php echo asset_url('img/logo.svg'); ?>" alt="<?php echo e(app_name()); ?>" class="navbar-brand-image me-2">
            <?php echo e(app_name()); ?>
        </a>

        <div class="navbar-nav flex-row order-md-last">
            <?php if ($user): ?>
                <?php if (count($activeCompanyOptions) > 0): ?>
                    <div class="nav-item d-none d-md-flex align-items-center me-3">
                        <form action="<?php echo base_url('organization/actions/set-active-company'); ?>" method="post" data-ajax="true" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <label class="visually-hidden" for="nav-active-company"><?php echo e(organization_lang('active_company')); ?></label>
                            <select
                                id="nav-active-company"
                                name="active_company_id"
                                class="form-select form-select-sm"
                                style="max-width: 14rem;"
                                onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit();"
                                aria-label="<?php echo e(organization_lang('active_company')); ?>">
                                <?php foreach ($activeCompanyOptions as $companyOption): ?>
                                    <?php $companyOptionId = (int) ($companyOption['id'] ?? 0); ?>
                                    <option value="<?php echo $companyOptionId; ?>" <?php echo $companyOptionId === (int) $activeCompanyId ? 'selected' : ''; ?>>
                                        <?php echo e((string) ($companyOption['company_name'] ?? '')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                <?php endif; ?>

                <?php if (route_exists('notifications/list') && check_permission('notifications.view')): ?>
                    <div class="nav-item dropdown d-flex me-3">
                        <a href="#"
                            class="nav-link px-0 js-notification-bell <?php echo $currentRoutePath === 'notifications/list' ? 'active' : ''; ?>"
                            data-unread-count="<?php echo (int) $unreadNotificationsCount; ?>"
                            data-bs-toggle="dropdown" data-bs-auto-close="outside" tabindex="-1" aria-label="<?php echo e($navBellAria); ?>" aria-expanded="false">
                            <i class="ti ti-bell fs-2 kirpi-bell-icon"></i>
                            <?php if ($unreadNotificationsCount > 0): ?>
                                <span class="badge badge-sm bg-red text-red-fg ms-1 js-notification-dot js-notification-count"><?php echo $unreadNotificationsCount > 99 ? '99+' : (int) $unreadNotificationsCount; ?></span>
                            <?php endif; ?>
                        </a>

                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card" style="min-width: 24rem;">
                            <div class="card">
                                <div class="card-header d-flex align-items-center justify-content-between">
                                    <h3 class="card-title m-0"><?php echo e($navNotificationsTitle); ?></h3>
                                    <a
                                        href="#"
                                        class="link-secondary js-notification-mark-all <?php echo $unreadNotificationsCount > 0 ? '' : 'd-none'; ?>"
                                        data-mark-read-url="<?php echo base_url('notifications/actions/mark-all-read'); ?>"
                                        title="<?php echo e($navMarkAllNotificationsRead); ?>"
                                        aria-label="<?php echo e($navMarkAllNotificationsRead); ?>">
                                        <i class="ti ti-checks"></i>
                                    </a>
                                </div>

                                <div class="list-group list-group-flush list-group-hoverable">
                                    <?php if (empty($recentNotifications)): ?>
                                        <div class="list-group-item py-4 text-center text-secondary">
                                            <?php echo e($navNotificationsEmpty); ?>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($recentNotifications as $notification): ?>
                                            <?php
                                            $isUnread = empty($notification['read_at']);
                                            ?>
                                            <div
                                                class="list-group-item js-notification-item <?php echo $isUnread ? 'is-unread' : ''; ?>"
                                                data-notification-id="<?php echo (int) ($notification['id'] ?? 0); ?>"
                                                data-is-unread="<?php echo $isUnread ? '1' : '0'; ?>"
                                                data-mark-read-url="<?php echo base_url('notifications/actions/mark-read'); ?>">
                                                <div class="row align-items-center g-2">
                                                    <div class="col-auto">
                                                        <span class="status-dot <?php echo $isUnread ? 'status-dot-animated bg-red' : 'bg-secondary'; ?> d-block js-notification-item-dot"></span>
                                                    </div>
                                                    <div class="col text-truncate">
                                                        <a
                                                            href="<?php echo base_url('notifications/list'); ?>"
                                                            class="text-reset text-decoration-none d-block text-truncate js-notification-open"
                                                            aria-label="<?php echo e($navOpenNotification); ?>">
                                                            <?php echo e($notification['title'] ?? $navNotificationsTitle); ?>
                                                        </a>
                                                        <div class="d-block text-secondary text-truncate mt-n1"><?php echo e($notification['message'] ?? ''); ?></div>
                                                    </div>
                                                    <div class="col-auto">
                                                        <?php if ($isUnread): ?>
                                                            <a
                                                                href="#"
                                                                class="list-group-item-actions js-notification-mark-read"
                                                                title="<?php echo e($navMarkNotificationRead); ?>"
                                                                aria-label="<?php echo e($navMarkNotificationRead); ?>">
                                                                <i class="ti ti-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="card-footer text-center">
                                    <a href="<?php echo base_url('notifications/list'); ?>"><?php echo e($navViewAllNotifications); ?></a>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>

                <?php if ($canUseLockFeature && route_exists('auth/actions/lock')): ?>
                    <div class="nav-item d-none d-md-flex me-3">
                        <form action="<?php echo base_url('auth/actions/lock'); ?>" method="post" data-ajax="true" class="m-0">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <button
                                type="submit"
                                class="nav-link px-0 border-0 bg-transparent text-reset"
                                title="<?php echo e($navLockAction); ?>"
                                aria-label="<?php echo e($navLockAction); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icons-tabler-outline icon-tabler-lock" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                    <path d="M5 13m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z" />
                                    <path d="M8 13v-4a4 4 0 0 1 8 0v4" />
                                </svg>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="nav-item dropdown">
                    <a href="#" id="user-menu-trigger" class="nav-link d-flex lh-1 text-reset p-0 dropdown-toggle"
                        data-bs-toggle="dropdown" aria-label="<?php echo e($navUserMenuAria); ?>" aria-expanded="false">
                        <?php if ($userAvatarUrl): ?>
                            <span class="avatar avatar-sm" style="background-image: url('<?php echo e($userAvatarUrl); ?>')"></span>
                        <?php else: ?>
                            <span class="avatar avatar-sm">
                                <?php echo e(mb_strtoupper(mb_substr($user['name'] ?? $navUserFallback, 0, 1))); ?>
                            </span>
                        <?php endif; ?>

                        <div class="d-none d-xl-block ps-2">
                            <div><?php echo e($user['name'] ?? $navUserFallback); ?></div>
                            <div class="mt-1 small text-secondary">
                                <?php echo e($user['role_name'] ?? ''); ?>
                            </div>
                        </div>
                    </a>

                    <div id="user-menu-dropdown" class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <?php if (route_exists('profile/view')): ?>
                            <a href="<?php echo base_url('profile/view'); ?>" class="dropdown-item"><?php echo e($navProfileLabel); ?></a>
                        <?php endif; ?>

                        <div class="dropdown-divider"></div>
                        <div class="px-3 py-2">
                            <div class="text-secondary small mb-2">Tema</div>
                            <div class="btn-group w-100" role="group" aria-label="Tema seçimi">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-choice="light">Light</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-choice="dark">Dark</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-theme-choice="system">Sistem</button>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>
                        <button type="button" class="dropdown-item d-flex align-items-center gap-2 js-layout-toggle">
                            <i class="ti ti-arrows-maximize js-layout-toggle-icon"></i>
                            <span>Geniş görünüm</span>
                        </button>

                        <?php if ($canUseLockFeature && route_exists('auth/actions/lock')): ?>
                            <form action="<?php echo base_url('auth/actions/lock'); ?>" method="post" class="m-0" data-ajax="true">
                                <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                                <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent">
                                    <?php echo e($navLockAction); ?>
                                </button>
                            </form>
                        <?php endif; ?>

                        <form action="<?php echo base_url('auth/actions/logout'); ?>" method="post" class="m-0"
                            data-ajax="true">
                            <input type="hidden" name="csrf_token" value="<?php echo e(get_csrf_token()); ?>">
                            <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent">
                                <?php echo e($navLogout); ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="collapse navbar-collapse" id="navbar-menu">
            <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                <ul class="navbar-nav">
                    <?php foreach ($menu as $item): ?>
                        <?php
                        $hasChildren = isset($item['children']) && is_array($item['children']);

                        if ($hasChildren) {
                            $visibleChildren = $filterVisibleMenuItems($item['children']);

                            if (empty($visibleChildren)) {
                                continue;
                            }
                        } else {
                            if (!route_exists($item['url'])) {
                                continue;
                            }

                            if ($item['permission'] && !check_permission($item['permission'])) {
                                continue;
                            }
                        }

                        $isActive = !$hasChildren && $currentRoutePath === ($item['url'] ?? '');
                        $isChildActive = $hasChildren && array_filter(
                            $visibleChildren,
                            static fn(array $child): bool => $isMenuItemActive($child, $currentRoutePath)
                        );
                        ?>

                        <?php if ($hasChildren): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo !empty($isChildActive) ? 'active' : ''; ?>"
                                    href="#" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="<?php echo e($item['icon']); ?>"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        <?php echo e($item['title']); ?>
                                    </span>
                                </a>

                                <div class="dropdown-menu">
                                    <?php foreach ($visibleChildren as $child): ?>
                                        <?php if (isset($child['children']) && is_array($child['children'])): ?>
                                            <?php $isNestedActive = $isMenuItemActive($child, $currentRoutePath); ?>
                                            <div class="dropend">
                                                <a class="dropdown-item dropdown-toggle <?php echo $isNestedActive ? 'active' : ''; ?>"
                                                    href="#"
                                                    data-bs-toggle="dropdown"
                                                    data-bs-auto-close="outside"
                                                    aria-expanded="false">
                                                    <?php echo e($child['title']); ?>
                                                </a>
                                                <div class="dropdown-menu">
                                                    <?php foreach ($child['children'] as $subChild): ?>
                                                        <a class="dropdown-item <?php echo $currentRoutePath === ($subChild['url'] ?? '') ? 'active' : ''; ?>"
                                                            href="<?php echo base_url($subChild['url']); ?>">
                                                            <?php echo e($subChild['title']); ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <a class="dropdown-item <?php echo $currentRoutePath === ($child['url'] ?? '') ? 'active' : ''; ?>"
                                                href="<?php echo base_url($child['url']); ?>">
                                                <?php echo e($child['title']); ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>"
                                    href="<?php echo base_url($item['url']); ?>">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="<?php echo e($item['icon']); ?>"></i>
                                    </span>
                                    <span class="nav-link-title">
                                        <?php echo e($item['title']); ?>
                                    </span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</header>
