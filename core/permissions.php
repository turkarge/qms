<?php

function kirpi_core_permission_catalog(): array
{
    return [
        'dashboard' => [
            'title' => 'Dashboard',
            'permissions' => [
                [
                    'name' => 'Dashboard Görüntüleme',
                    'slug' => 'dashboard.view',
                ],
            ],
        ],
        'users' => [
            'title' => 'Kullanıcılar',
            'permissions' => [
                [
                    'name' => 'Kullanıcıları Görüntüleme',
                    'slug' => 'users.view',
                ],
                [
                    'name' => 'Kullanıcı Oluşturma',
                    'slug' => 'users.create',
                ],
                [
                    'name' => 'Kullanıcı Düzenleme',
                    'slug' => 'users.edit',
                ],
                [
                    'name' => 'Kullanıcı Durumu Güncelleme',
                    'slug' => 'users.status',
                ],
                [
                    'name' => 'Kullanıcı Oturumu Düşürme',
                    'slug' => 'users.session.drop',
                ],
                [
                    'name' => 'Kullanıcı Lock Key Sıfırlama',
                    'slug' => 'users.lock.reset',
                ],
            ],
        ],
        'roles' => [
            'title' => 'Roller',
            'permissions' => [
                [
                    'name' => 'Rolleri Görüntüleme',
                    'slug' => 'roles.view',
                ],
                [
                    'name' => 'Rol Oluşturma',
                    'slug' => 'roles.create',
                ],
                [
                    'name' => 'Rol Düzenleme',
                    'slug' => 'roles.edit',
                ],
                [
                    'name' => 'Rol Durumu Güncelleme',
                    'slug' => 'roles.status',
                ],
                [
                    'name' => 'Rol Yetkilerini Yönetme',
                    'slug' => 'roles.permissions',
                ],
            ],
        ],
        'profile' => [
            'title' => 'Profil',
            'permissions' => [
                [
                    'name' => 'Profili Görüntüleme',
                    'slug' => 'profile.view',
                ],
                [
                    'name' => 'Profili Güncelleme',
                    'slug' => 'profile.edit',
                ],
            ],
        ],
        'notifications' => [
            'title' => 'Bildirimler',
            'permissions' => [
                [
                    'name' => 'Bildirimleri Görüntüleme',
                    'slug' => 'notifications.view',
                ],
                [
                    'name' => 'Bildirim Ayarlarını Yönetme',
                    'slug' => 'notifications.settings',
                ],
            ],
        ],
        'mail' => [
            'title' => 'Mail',
            'permissions' => [
                [
                    'name' => 'Mail Modülü Görüntüleme',
                    'slug' => 'mail.view',
                ],
                [
                    'name' => 'Test Maili Gönderme',
                    'slug' => 'mail.test',
                ],
            ],
        ],
        'template' => [
            'title' => 'Template Registry',
            'permissions' => [
                [
                    'name' => 'Şablonları Görüntüleme',
                    'slug' => 'template.view',
                ],
                [
                    'name' => 'Şablon Yönetimi',
                    'slug' => 'template.manage',
                ],
            ],
        ],
        'documents' => [
            'title' => 'Documents',
            'permissions' => [
                [
                    'name' => 'Belgeleri Görüntüleme',
                    'slug' => 'documents.view',
                ],
                [
                    'name' => 'Belge Yükleme',
                    'slug' => 'documents.upload',
                ],
                [
                    'name' => 'Belge Yönetimi',
                    'slug' => 'documents.manage',
                ],
            ],
        ],
        'audit' => [
            'title' => 'Audit',
            'permissions' => [
                [
                    'name' => 'Audit Log Görüntüleme',
                    'slug' => 'audit.view',
                ],
            ],
        ],
        'settings' => [
            'title' => 'Ayarlar',
            'permissions' => [
                [
                    'name' => 'Ayarları Görüntüleme',
                    'slug' => 'settings.view',
                ],
                [
                    'name' => 'Ayarları Güncelleme',
                    'slug' => 'settings.update',
                ],
            ],
        ],
        'queue' => [
            'title' => 'Queue',
            'permissions' => [
                [
                    'name' => 'Queue Görüntüleme',
                    'slug' => 'queue.view',
                ],
                [
                    'name' => 'Queue Yönetimi',
                    'slug' => 'queue.manage',
                ],
            ],
        ],
        'backup' => [
            'title' => 'Backup',
            'permissions' => [
                [
                    'name' => 'Backup Görüntüleme',
                    'slug' => 'backup.view',
                ],
                [
                    'name' => 'Backup Oluşturma',
                    'slug' => 'backup.create',
                ],
                [
                    'name' => 'Backup Restore',
                    'slug' => 'backup.restore',
                ],
                [
                    'name' => 'Backup İndirme',
                    'slug' => 'backup.download',
                ],
                [
                    'name' => 'Backup Silme',
                    'slug' => 'backup.delete',
                ],
            ],
        ],
        'security' => [
            'title' => 'Güvenlik',
            'permissions' => [
                [
                    'name' => 'Güvenlik İzleme Görüntüleme',
                    'slug' => 'security.view',
                ],
            ],
        ],
        'health' => [
            'title' => 'Health',
            'permissions' => [
                [
                    'name' => 'Health Metrics Görüntüleme',
                    'slug' => 'health.view',
                ],
                [
                    'name' => 'Env Reader Görüntüleme',
                    'slug' => 'health.env.view',
                ],
            ],
        ],
        'ai' => [
            'title' => 'Kirpi Intelligence',
            'permissions' => [
                [
                    'name' => 'AI Panel Görüntüleme',
                    'slug' => 'ai.view',
                ],
                [
                    'name' => 'AI Schema Registry Yönetimi',
                    'slug' => 'ai.schema.manage',
                ],
                [
                    'name' => 'AI Adapter Yönetimi',
                    'slug' => 'ai.adapters.manage',
                ],
                [
                    'name' => 'AI Audit Log Görüntüleme',
                    'slug' => 'ai.audit.view',
                ],
            ],
        ],
        'organization' => [
            'title' => 'Organizasyon',
            'permissions' => [
                ['name' => 'Organizasyon Görüntüleme', 'slug' => 'organization.view'],
                ['name' => 'Organizasyon Kaydı Oluşturma', 'slug' => 'organization.create'],
                ['name' => 'Organizasyon Kaydı Düzenleme', 'slug' => 'organization.edit'],
                ['name' => 'Organizasyon Durumu Güncelleme', 'slug' => 'organization.status'],
                ['name' => 'Organizasyon Atamalarını Yönetme', 'slug' => 'organization.assign'],
                ['name' => 'Organizasyon Verisi Dışa Aktarma', 'slug' => 'organization.export'],
            ],
        ],
        'governance' => [
            'title' => 'Yönetişim',
            'permissions' => [
                ['name' => 'Yönetişim Görüntüleme', 'slug' => 'governance.view'],
                ['name' => 'Sahiplik Yönetimi', 'slug' => 'governance.ownership.manage'],
                ['name' => 'RACI Yönetimi', 'slug' => 'governance.raci.manage'],
                ['name' => 'Onay Akışı Yönetimi', 'slug' => 'governance.approval.manage'],
                ['name' => 'Delegasyon Yönetimi', 'slug' => 'governance.delegation.manage'],
            ],
        ],
        'qms_entities' => [
            'title' => 'QMS Varlıkları',
            'permissions' => [
                ['name' => 'QMS Varlıklarını Görüntüleme', 'slug' => 'qms_entities.view'],
                ['name' => 'QMS Varlıklarını Yönetme', 'slug' => 'qms_entities.manage'],
                ['name' => 'QMS Varlıklarını Arşivleme', 'slug' => 'qms_entities.archive'],
            ],
        ],
        'qms_relationships' => [
            'title' => 'QMS Iliskileri',
            'permissions' => [
                ['name' => 'QMS Iliskilerini Goruntuleme', 'slug' => 'qms_relationships.view'],
                ['name' => 'QMS Iliskilerini Yonetme', 'slug' => 'qms_relationships.manage'],
                ['name' => 'QMS Iliskilerini Arsivleme', 'slug' => 'qms_relationships.archive'],
            ],
        ],
    ];
}

function kirpi_flatten_permission_catalog(): array
{
    $permissions = [];

    foreach (kirpi_core_permission_catalog() as $groupKey => $group) {
        foreach ($group['permissions'] ?? [] as $permission) {
            $permissions[] = [
                'group_name' => $groupKey,
                'group_title' => $group['title'] ?? $groupKey,
                'name' => $permission['name'],
                'slug' => $permission['slug'],
            ];
        }
    }

    return $permissions;
}

function get_role_permission_slugs(int $roleId): array
{
    if ($roleId <= 0 || !db_table_exists('permissions') || !db_table_exists('role_permissions')) {
        return [];
    }

    try {
        $stmt = db()->prepare("
            SELECT p.slug
            FROM role_permissions rp
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id = :role_id
            ORDER BY p.slug ASC
        ");
        $stmt->execute([
            ':role_id' => $roleId,
        ]);

        return array_values(array_unique($stmt->fetchAll(PDO::FETCH_COLUMN)));
    } catch (Throwable $e) {
        error_log('Role permission fetch error: ' . $e->getMessage());
        return [];
    }
}

function sync_permission_catalog(): void
{
    if (!db_table_exists('permissions')) {
        return;
    }

    $catalog = kirpi_flatten_permission_catalog();

    foreach ($catalog as $permission) {
        try {
            $stmt = db()->prepare("
                INSERT INTO permissions (name, slug, group_name)
                VALUES (:name, :slug, :group_name)
                ON DUPLICATE KEY UPDATE
                    name = VALUES(name),
                    group_name = VALUES(group_name)
            ");
            $stmt->execute([
                ':name' => $permission['name'],
                ':slug' => $permission['slug'],
                ':group_name' => $permission['group_name'],
            ]);
        } catch (Throwable $e) {
            error_log('Permission catalog sync error: ' . $e->getMessage());
            return;
        }
    }
}

function sync_role_permissions(int $roleId, array $permissionSlugs): void
{
    if ($roleId <= 0 || !db_table_exists('permissions') || !db_table_exists('role_permissions')) {
        return;
    }

    sync_permission_catalog();

    $allowedSlugs = array_column(kirpi_flatten_permission_catalog(), 'slug');
    $filteredSlugs = array_values(array_unique(array_intersect($allowedSlugs, $permissionSlugs)));

    $deleteStmt = db()->prepare('DELETE FROM role_permissions WHERE role_id = :role_id');
    $deleteStmt->execute([
        ':role_id' => $roleId,
    ]);

    if (empty($filteredSlugs)) {
        return;
    }

    $permissionStmt = db()->prepare('SELECT id, slug FROM permissions WHERE slug = :slug LIMIT 1');
    $insertStmt = db()->prepare("
        INSERT INTO role_permissions (role_id, permission_id)
        VALUES (:role_id, :permission_id)
    ");

    foreach ($filteredSlugs as $slug) {
        $permissionStmt->execute([
            ':slug' => $slug,
        ]);

        $permission = $permissionStmt->fetch(PDO::FETCH_ASSOC);
        if (!$permission) {
            continue;
        }

        $insertStmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => (int) $permission['id'],
        ]);
    }
}
