<?php

require_once BASE_PATH . '/modules/organization/helpers.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';
if (is_file(BASE_PATH . '/modules/qms_relationships/helpers.php')) {
    require_once BASE_PATH . '/modules/qms_relationships/helpers.php';
}
if (is_file(BASE_PATH . '/modules/qms_events/helpers.php')) {
    require_once BASE_PATH . '/modules/qms_events/helpers.php';
}

function qms_demo_seed_find_or_create_user(string $name, string $email): int
{
    $stmt = db()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return $id;
    }

    $insert = db()->prepare("INSERT INTO users(name, email, password, is_active) VALUES(:name, :email, :password, 1)");
    $insert->execute([
        ':name' => $name,
        ':email' => $email,
        ':password' => password_hash('Demo1234!', PASSWORD_DEFAULT),
    ]);

    return (int) db()->lastInsertId();
}

function qms_demo_seed_find_or_create_company(): int
{
    $stmt = db()->prepare("SELECT id FROM organization_companies WHERE company_code = 'KQMS-DEMO' LIMIT 1");
    $stmt->execute();
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return $id;
    }

    $insert = db()->prepare("INSERT INTO organization_companies(company_code, company_name, legal_name, status) VALUES('KQMS-DEMO', 'Kirpi QMS Demo Sirketi', 'Kirpi QMS Demo Sirketi A.S.', 'active')");
    $insert->execute();

    return (int) db()->lastInsertId();
}

function qms_demo_seed_find_or_create_unit(int $companyId, string $type, string $code, string $name, ?int $parentId = null): int
{
    $stmt = db()->prepare('SELECT id FROM organization_units WHERE company_id = :company AND unit_code = :code LIMIT 1');
    $stmt->execute([':company' => $companyId, ':code' => $code]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return $id;
    }

    $insert = db()->prepare("INSERT INTO organization_units(company_id, parent_unit_id, unit_type, unit_code, unit_name, status) VALUES(:company, :parent, :type, :code, :name, 'active')");
    $insert->execute([
        ':company' => $companyId,
        ':parent' => $parentId,
        ':type' => $type,
        ':code' => $code,
        ':name' => $name,
    ]);

    return (int) db()->lastInsertId();
}

function qms_demo_seed_assign_user(int $userId, int $companyId, ?int $unitId = null): void
{
    $stmt = db()->prepare('SELECT id FROM organization_user_assignments WHERE user_id = :user AND company_id = :company AND status = "active" LIMIT 1');
    $stmt->execute([':user' => $userId, ':company' => $companyId]);
    if ((int) $stmt->fetchColumn() > 0) {
        return;
    }

    $insert = db()->prepare("INSERT INTO organization_user_assignments(user_id, company_id, unit_id, scope_mode, is_primary, status, starts_at) VALUES(:user, :company, :unit, 'company', 1, 'active', NOW())");
    $insert->execute([
        ':user' => $userId,
        ':company' => $companyId,
        ':unit' => $unitId,
    ]);
}

function qms_demo_seed_find_or_register_entity(array $data): array
{
    $stmt = db()->prepare('SELECT id FROM qms_entities WHERE domain_table = :domain_table AND domain_record_id = :domain_id LIMIT 1');
    $stmt->execute([
        ':domain_table' => (string) $data['domain_table'],
        ':domain_id' => (int) $data['domain_record_id'],
    ]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return qms_entities_row($id) ?? [];
    }

    return qms_entities_register($data);
}

function qms_demo_seed_find_or_create_relationship(int $companyId, int $sourceId, int $targetId, string $type, string $description = ''): ?array
{
    if (!function_exists('qms_relationships_save')) {
        return null;
    }

    $stmt = db()->prepare('SELECT id FROM qms_entity_relationships WHERE company_id = :company AND source_entity_id = :source AND target_entity_id = :target AND relationship_type = :type LIMIT 1');
    $stmt->execute([
        ':company' => $companyId,
        ':source' => $sourceId,
        ':target' => $targetId,
        ':type' => $type,
    ]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return qms_relationships_row($id) ?? null;
    }

    return qms_relationships_save([
        'company_id' => $companyId,
        'source_entity_id' => $sourceId,
        'target_entity_id' => $targetId,
        'relationship_type' => $type,
        'description' => $description,
        'status' => 'active',
    ]);
}

function qms_demo_seed_find_or_create_event(array $data): ?array
{
    if (!function_exists('qms_events_publish')) {
        return null;
    }

    $stmt = db()->prepare('
        SELECT id
        FROM qms_domain_events
        WHERE company_id = :company
          AND entity_type = :entity_type
          AND entity_id = :entity_id
          AND event_type = :event_type
          AND source_module = :source_module
        LIMIT 1
    ');
    $stmt->execute([
        ':company' => (int) $data['company_id'],
        ':entity_type' => (string) $data['entity_type'],
        ':entity_id' => (int) $data['entity_id'],
        ':event_type' => (string) $data['event_type'],
        ':source_module' => (string) $data['source_module'],
    ]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return qms_events_row($id) ?? null;
    }

    return qms_events_publish($data);
}

function qms_demo_seed_data(): array
{
    if (!db_table_exists('organization_companies') || !db_table_exists('qms_entities')) {
        throw new RuntimeException('QMS demo seed requires organization and qms_entities schemas.');
    }

    $companyId = qms_demo_seed_find_or_create_company();
    $facilityId = qms_demo_seed_find_or_create_unit($companyId, 'facility', 'KQMS-FAB', 'Demo Fabrika');
    $departmentId = qms_demo_seed_find_or_create_unit($companyId, 'department', 'KQMS-KLT', 'Kalite Departmani', $facilityId);
    $ownerId = qms_demo_seed_find_or_create_user('QMS Demo Sorumlusu', 'qms.demo.owner@example.test');
    $auditorId = qms_demo_seed_find_or_create_user('QMS Demo Denetcisi', 'qms.demo.auditor@example.test');
    qms_demo_seed_assign_user($ownerId, $companyId, $departmentId);
    qms_demo_seed_assign_user($auditorId, $companyId, $departmentId);

    $_SESSION['user'] = [
        'id' => $ownerId,
        'role_name' => 'Super Admin',
        'permissions' => ['*'],
        'default_company_id' => $companyId,
    ];
    $_SESSION['active_company_id'] = $companyId;

    $common = [
        'company_id' => $companyId,
        'facility_id' => $facilityId,
        'department_id' => $departmentId,
        'owner_user_id' => $ownerId,
        'actor_user_id' => $ownerId,
        'status' => 'active',
    ];

    $requirement = qms_demo_seed_find_or_register_entity($common + [
        'entity_type' => 'requirement',
        'domain_table' => 'demo_requirements',
        'domain_record_id' => 1001,
        'title' => 'ISO 9001 Demo Gerekliligi',
        'description' => 'Demo standart gerekliligi.',
    ]);
    $risk = qms_demo_seed_find_or_register_entity($common + [
        'entity_type' => 'risk',
        'domain_table' => 'demo_risks',
        'domain_record_id' => 1002,
        'title' => 'Demo Kritik Tedarikci Riski',
        'description' => 'Tedarikci gecikmelerinin kalite surecine etkisi.',
    ]);
    $document = qms_demo_seed_find_or_register_entity($common + [
        'entity_type' => 'controlled_document',
        'domain_table' => 'demo_controlled_documents',
        'domain_record_id' => 1003,
        'title' => 'Demo Kalite El Kitabi',
        'description' => 'Kontrollu dokuman demo kaydi.',
    ]);
    $evidence = qms_demo_seed_find_or_register_entity($common + [
        'entity_type' => 'evidence',
        'domain_table' => 'demo_evidence',
        'domain_record_id' => 1004,
        'title' => 'Demo Denetim Kaniti',
        'description' => 'Gereklilik icin demo kanit kaydi.',
    ]);
    $capa = qms_demo_seed_find_or_register_entity($common + [
        'entity_type' => 'capa',
        'domain_table' => 'demo_capa',
        'domain_record_id' => 1005,
        'title' => 'Demo CAPA Aksiyonu',
        'description' => 'Risk azaltma icin demo CAPA.',
    ]);

    $relationships = [];
    if (function_exists('qms_relationships_save')) {
        $relationships[] = qms_demo_seed_find_or_create_relationship($companyId, (int) $document['id'], (int) $requirement['id'], 'satisfies_requirement', 'Dokuman gerekliligi karsilar.');
        $relationships[] = qms_demo_seed_find_or_create_relationship($companyId, (int) $evidence['id'], (int) $requirement['id'], 'provides_evidence_for', 'Kanit gerekliligi destekler.');
        $relationships[] = qms_demo_seed_find_or_create_relationship($companyId, (int) $capa['id'], (int) $risk['id'], 'mitigates', 'CAPA riski azaltir.');
        $relationships[] = qms_demo_seed_find_or_create_relationship($companyId, (int) $capa['id'], (int) $document['id'], 'references', 'CAPA dokumana referans verir.');
    }

    $events = [];
    if (function_exists('qms_events_publish')) {
        $eventCommon = [
            'company_id' => $companyId,
            'facility_id' => $facilityId,
            'department_id' => $departmentId,
            'actor_type' => 'user',
            'actor_user_id' => $ownerId,
            'payload_version' => 1,
            'source_module' => 'qms_demo_seed',
        ];
        $events[] = qms_demo_seed_find_or_create_event($eventCommon + [
            'event_type' => 'requirement.mapped.v1',
            'entity_type' => 'requirement',
            'entity_id' => (int) $requirement['id'],
            'payload' => ['title' => $requirement['title'] ?? 'ISO 9001 Demo Gerekliligi'],
        ]);
        $events[] = qms_demo_seed_find_or_create_event($eventCommon + [
            'event_type' => 'risk.created.v1',
            'entity_type' => 'risk',
            'entity_id' => (int) $risk['id'],
            'payload' => ['title' => $risk['title'] ?? 'Demo Kritik Tedarikci Riski'],
        ]);
        $events[] = qms_demo_seed_find_or_create_event($eventCommon + [
            'event_type' => 'controlled_document.published.v1',
            'entity_type' => 'controlled_document',
            'entity_id' => (int) $document['id'],
            'payload' => ['title' => $document['title'] ?? 'Demo Kalite El Kitabi'],
        ]);
        $events[] = qms_demo_seed_find_or_create_event($eventCommon + [
            'event_type' => 'evidence.attached.v1',
            'entity_type' => 'evidence',
            'entity_id' => (int) $evidence['id'],
            'payload' => ['title' => $evidence['title'] ?? 'Demo Denetim Kaniti'],
        ]);
        $events[] = qms_demo_seed_find_or_create_event($eventCommon + [
            'event_type' => 'capa.opened.v1',
            'entity_type' => 'capa',
            'entity_id' => (int) $capa['id'],
            'payload' => ['title' => $capa['title'] ?? 'Demo CAPA Aksiyonu'],
        ]);
    }

    return [
        'company_id' => $companyId,
        'users' => [$ownerId, $auditorId],
        'units' => [$facilityId, $departmentId],
        'entities' => array_values(array_filter([$requirement, $risk, $document, $evidence, $capa])),
        'relationships' => array_values(array_filter($relationships)),
        'events' => array_values(array_filter($events)),
    ];
}
