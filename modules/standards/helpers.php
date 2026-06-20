<?php

require_once BASE_PATH . '/modules/organization/helpers.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';
require_once BASE_PATH . '/modules/qms_relationships/helpers.php';
require_once BASE_PATH . '/modules/qms_events/helpers.php';
require_once BASE_PATH . '/modules/standards/language.php';

function standards_uuid(): string
{
    return qms_entities_uuid();
}

function standards_allowed_statuses(): array
{
    return ['draft', 'active', 'published', 'archived'];
}

function standards_valid_date(string $date): bool
{
    if ($date === '') return true;
    $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $value !== false && $value->format('Y-m-d') === $date;
}

function standards_select_options(): array
{
    $params = [];
    $scope = organization_scope_where('s.company_id', $params);
    $where = $scope !== null ? ' WHERE ' . $scope : '';
    $standardsStmt = db()->prepare("SELECT s.id, s.company_id, s.standard_code, s.standard_name, c.company_code, c.company_name FROM standards_catalog s JOIN organization_companies c ON c.id=s.company_id{$where} ORDER BY c.company_name, s.standard_code");
    kirpi_table_bind($standardsStmt, $params);
    $standardsStmt->execute();
    $standards = $standardsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $params = [];
    $scope = organization_scope_where('s.company_id', $params);
    $where = $scope !== null ? ' WHERE ' . $scope : '';
    $versionsStmt = db()->prepare("SELECT v.id, v.standard_id, s.company_id, s.standard_code, s.standard_name, v.version_label FROM standards_versions v JOIN standards_catalog s ON s.id=v.standard_id{$where} ORDER BY s.standard_code, v.version_label DESC");
    kirpi_table_bind($versionsStmt, $params);
    $versionsStmt->execute();
    $versions = $versionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $params = [];
    $scope = organization_scope_where('s.company_id', $params);
    $where = $scope !== null ? ' WHERE ' . $scope : '';
    $clausesStmt = db()->prepare("SELECT cl.id, cl.version_id, s.company_id, s.standard_code, v.version_label, cl.clause_code, cl.title FROM standards_clauses cl JOIN standards_versions v ON v.id=cl.version_id JOIN standards_catalog s ON s.id=v.standard_id{$where} ORDER BY s.standard_code, v.version_label DESC, cl.clause_code");
    kirpi_table_bind($clausesStmt, $params);
    $clausesStmt->execute();
    $clauses = $clausesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $params = [];
    $scope = organization_scope_where('s.company_id', $params);
    $where = $scope !== null ? ' WHERE ' . $scope : '';
    $requirementsStmt = db()->prepare("SELECT r.id, r.version_id, s.company_id, s.standard_code, v.version_label, r.requirement_code, r.title FROM standards_requirements r JOIN standards_versions v ON v.id=r.version_id JOIN standards_catalog s ON s.id=v.standard_id{$where} ORDER BY s.standard_code, v.version_label DESC, r.requirement_code");
    kirpi_table_bind($requirementsStmt, $params);
    $requirementsStmt->execute();
    $requirements = $requirementsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    return [
        'companies' => organization_select_options()['companies'],
        'standards' => $standards,
        'versions' => $versions,
        'clauses' => $clauses,
        'requirements' => $requirements,
        'statuses' => standards_allowed_statuses(),
    ];
}

function standards_row(string $resource, int $id): ?array
{
    $sql = [
        'standards' => "SELECT s.*, c.company_name FROM standards_catalog s JOIN organization_companies c ON c.id=s.company_id WHERE s.id=:id",
        'versions' => "SELECT v.*, s.company_id, s.standard_code, s.standard_name, c.company_name FROM standards_versions v JOIN standards_catalog s ON s.id=v.standard_id JOIN organization_companies c ON c.id=s.company_id WHERE v.id=:id",
        'requirements' => "SELECT r.*, s.company_id, s.standard_code, s.standard_name, v.version_label, cl.clause_code, c.company_name FROM standards_requirements r JOIN standards_versions v ON v.id=r.version_id JOIN standards_catalog s ON s.id=v.standard_id JOIN standards_clauses cl ON cl.id=r.clause_id JOIN organization_companies c ON c.id=s.company_id WHERE r.id=:id",
        'controls' => "SELECT x.*, r.requirement_code, r.title AS requirement_title, s.company_id, s.standard_code, s.standard_name, v.version_label, c.company_name FROM standards_controls x JOIN standards_requirements r ON r.id=x.requirement_id JOIN standards_versions v ON v.id=r.version_id JOIN standards_catalog s ON s.id=v.standard_id JOIN organization_companies c ON c.id=s.company_id WHERE x.id=:id",
    ][$resource] ?? null;
    if ($sql === null || $id <= 0) {
        return null;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $row['row_key'] = $resource . '-' . $id;
    return $row;
}

function standards_datatable_row(string $resource, int $id): array
{
    $row = standards_row($resource, $id);
    if (!$row) {
        return [];
    }
    if ($resource === 'requirements') {
        $row['row_key'] = 'requirements-' . $id;
    } elseif ($resource === 'controls') {
        $row['row_key'] = 'controls-' . $id;
    } elseif ($resource === 'versions') {
        $row['row_key'] = 'versions-' . $id;
    } elseif ($resource === 'standards') {
        $row['row_key'] = 'standards-' . $id;
    }
    return $row;
}

function standards_find_or_create_catalog(array $data): array
{
    $companyId = (int) ($data['company_id'] ?? 0);
    $code = strtoupper(trim((string) ($data['standard_code'] ?? '')));
    $name = trim((string) ($data['standard_name'] ?? ''));
    if ($companyId <= 0 || $code === '' || $name === '') {
        throw new InvalidArgumentException('required_fields');
    }
    if (!organization_company_in_scope($companyId)) {
        throw new RuntimeException('permission_denied', 403);
    }
    $stmt = db()->prepare('SELECT id FROM standards_catalog WHERE company_id=:company AND standard_code=:code LIMIT 1');
    $stmt->execute([':company' => $companyId, ':code' => $code]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return standards_row('standards', $id) ?? [];
    }
    $userId = (int) (current_user()['id'] ?? 0) ?: null;
    try {
        $insert = db()->prepare("INSERT INTO standards_catalog(company_id,standard_uid,standard_code,standard_name,owner_organization,category,status,created_by_user_id,updated_by_user_id) VALUES(:company,:uid,:code,:name,:owner,:category,:status,:created_user,:updated_user)");
        $insert->execute([
            ':company' => $companyId,
            ':uid' => standards_uuid(),
            ':code' => $code,
            ':name' => $name,
            ':owner' => trim((string) ($data['owner_organization'] ?? '')) ?: null,
            ':category' => trim((string) ($data['category'] ?? '')) ?: null,
            ':status' => (string) ($data['status'] ?? 'active'),
            ':created_user' => $userId,
            ':updated_user' => $userId,
        ]);
    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) !== 1062) {
            throw $e;
        }
        $stmt->execute([':company' => $companyId, ':code' => $code]);
        $id = (int) $stmt->fetchColumn();
        if ($id > 0) {
            return standards_row('standards', $id) ?? [];
        }
        throw $e;
    }
    return standards_row('standards', (int) db()->lastInsertId()) ?? [];
}

function standards_find_or_create_version(int $standardId, array $data): array
{
    $label = trim((string) ($data['version_label'] ?? ''));
    if ($standardId <= 0 || $label === '') {
        throw new InvalidArgumentException('required_fields');
    }
    $standard = standards_row('standards', $standardId);
    if (!$standard || !organization_company_in_scope((int) $standard['company_id'])) {
        throw new RuntimeException('permission_denied', 403);
    }
    $stmt = db()->prepare('SELECT id FROM standards_versions WHERE standard_id=:standard AND version_label=:label LIMIT 1');
    $stmt->execute([':standard' => $standardId, ':label' => $label]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return standards_row('versions', $id) ?? [];
    }
    $userId = (int) (current_user()['id'] ?? 0) ?: null;
    $insert = db()->prepare("INSERT INTO standards_versions(standard_id,version_uid,version_label,published_on,effective_from,transition_until,status,created_by_user_id,updated_by_user_id) VALUES(:standard,:uid,:label,:published,:effective,:transition,:status,:created_user,:updated_user)");
    $insert->execute([
        ':standard' => $standardId,
        ':uid' => standards_uuid(),
        ':label' => $label,
        ':published' => $data['published_on'] ?? null,
        ':effective' => $data['effective_from'] ?? null,
        ':transition' => $data['transition_until'] ?? null,
        ':status' => (string) ($data['status'] ?? 'published'),
        ':created_user' => $userId,
        ':updated_user' => $userId,
    ]);
    return standards_row('versions', (int) db()->lastInsertId()) ?? [];
}

function standards_find_or_create_clause(int $versionId, array $data): array
{
    $code = trim((string) ($data['clause_code'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    if ($versionId <= 0 || $code === '' || $title === '') {
        throw new InvalidArgumentException('required_fields');
    }
    $version = standards_row('versions', $versionId);
    if (!$version || !organization_company_in_scope((int) $version['company_id'])) {
        throw new RuntimeException('permission_denied', 403);
    }
    $stmt = db()->prepare('SELECT id FROM standards_clauses WHERE version_id=:version AND clause_code=:code LIMIT 1');
    $stmt->execute([':version' => $versionId, ':code' => $code]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        $row = db()->prepare('SELECT * FROM standards_clauses WHERE id=:id');
        $row->execute([':id' => $id]);
        return $row->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    $insert = db()->prepare("INSERT INTO standards_clauses(version_id,parent_clause_id,clause_uid,clause_code,title,body,sort_order,status) VALUES(:version,:parent,:uid,:code,:title,:body,:sort,:status)");
    $insert->execute([
        ':version' => $versionId,
        ':parent' => !empty($data['parent_clause_id']) ? (int) $data['parent_clause_id'] : null,
        ':uid' => standards_uuid(),
        ':code' => $code,
        ':title' => $title,
        ':body' => trim((string) ($data['body'] ?? '')) ?: null,
        ':sort' => (int) ($data['sort_order'] ?? 0),
        ':status' => (string) ($data['status'] ?? 'active'),
    ]);
    $row = db()->prepare('SELECT * FROM standards_clauses WHERE id=:id');
    $row->execute([':id' => (int) db()->lastInsertId()]);
    return $row->fetch(PDO::FETCH_ASSOC) ?: [];
}

function standards_find_or_create_requirement(int $versionId, int $clauseId, array $data): array
{
    $code = trim((string) ($data['requirement_code'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $text = trim((string) ($data['requirement_text'] ?? ''));
    if ($versionId <= 0 || $clauseId <= 0 || $code === '' || $title === '' || $text === '') {
        throw new InvalidArgumentException('required_fields');
    }
    $version = standards_row('versions', $versionId);
    if (!$version || !organization_company_in_scope((int) $version['company_id'])) {
        throw new RuntimeException('permission_denied', 403);
    }
    $stmt = db()->prepare('SELECT id FROM standards_requirements WHERE version_id=:version AND requirement_code=:code LIMIT 1');
    $stmt->execute([':version' => $versionId, ':code' => $code]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return standards_row('requirements', $id) ?? [];
    }
    $insert = db()->prepare("INSERT INTO standards_requirements(version_id,clause_id,requirement_uid,requirement_code,title,requirement_text,verification_method,criticality,status) VALUES(:version,:clause,:uid,:code,:title,:text,:verification,:criticality,:status)");
    $insert->execute([
        ':version' => $versionId,
        ':clause' => $clauseId,
        ':uid' => standards_uuid(),
        ':code' => $code,
        ':title' => $title,
        ':text' => $text,
        ':verification' => trim((string) ($data['verification_method'] ?? '')) ?: null,
        ':criticality' => (string) ($data['criticality'] ?? 'normal'),
        ':status' => (string) ($data['status'] ?? 'active'),
    ]);
    $requirement = standards_row('requirements', (int) db()->lastInsertId()) ?? [];
    if ($requirement) {
        $entityCode = strtoupper((string) preg_replace('/[^A-Z0-9]+/i', '-', (string) $version['standard_code'] . '-' . (string) $version['version_label'] . '-' . $code));
        $entityCode = trim(substr($entityCode, 0, 100), '-');
        qms_entities_register([
            'company_id' => (int) $version['company_id'],
            'entity_type' => 'requirement',
            'domain_table' => 'standards_requirements',
            'domain_record_id' => (int) $requirement['id'],
            'entity_code' => $entityCode,
            'title' => $title,
            'description' => $text,
            'status' => 'active',
        ]);
    }
    return $requirement;
}

function standards_find_or_create_control(int $requirementId, array $data): array
{
    $code = trim((string) ($data['control_code'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $text = trim((string) ($data['control_text'] ?? ''));
    if ($requirementId <= 0 || $code === '' || $title === '' || $text === '') {
        throw new InvalidArgumentException('required_fields');
    }
    $requirement = standards_row('requirements', $requirementId);
    if (!$requirement || !organization_company_in_scope((int) $requirement['company_id'])) {
        throw new RuntimeException('permission_denied', 403);
    }
    $stmt = db()->prepare('SELECT id FROM standards_controls WHERE requirement_id=:requirement AND control_code=:code LIMIT 1');
    $stmt->execute([':requirement' => $requirementId, ':code' => $code]);
    $id = (int) $stmt->fetchColumn();
    if ($id > 0) {
        return standards_row('controls', $id) ?? [];
    }
    $insert = db()->prepare("INSERT INTO standards_controls(requirement_id,control_uid,control_code,title,control_text,control_type,status) VALUES(:requirement,:uid,:code,:title,:text,:type,:status)");
    $insert->execute([
        ':requirement' => $requirementId,
        ':uid' => standards_uuid(),
        ':code' => $code,
        ':title' => $title,
        ':text' => $text,
        ':type' => trim((string) ($data['control_type'] ?? '')) ?: null,
        ':status' => (string) ($data['status'] ?? 'active'),
    ]);
    return standards_row('controls', (int) db()->lastInsertId()) ?? [];
}

function standards_save_standard(array $data): array
{
    $id = (int) ($data['id'] ?? 0);
    $companyId = (int) ($data['company_id'] ?? 0);
    $code = strtoupper(trim((string) ($data['standard_code'] ?? '')));
    $name = trim((string) ($data['standard_name'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'active'));
    if ($companyId <= 0 || $code === '' || $name === '' || !in_array($status, standards_allowed_statuses(), true)) throw new InvalidArgumentException('required_fields');
    if (!organization_company_in_scope($companyId)) throw new RuntimeException('permission_denied', 403);
    $userId = (int) (current_user()['id'] ?? 0) ?: null;
    if ($id > 0) {
        $current = standards_row('standards', $id);
        if (!$current) throw new InvalidArgumentException('invalid_record');
        if (!organization_company_in_scope((int) $current['company_id'])) throw new RuntimeException('permission_denied', 403);
        $stmt = db()->prepare('UPDATE standards_catalog SET company_id=:company,standard_code=:code,standard_name=:name,owner_organization=:owner,category=:category,status=:status,updated_by_user_id=:user WHERE id=:id');
        $stmt->execute([':company'=>$companyId,':code'=>$code,':name'=>$name,':owner'=>trim((string)($data['owner_organization']??''))?:null,':category'=>trim((string)($data['category']??''))?:null,':status'=>$status,':user'=>$userId,':id'=>$id]);
        return standards_row('standards', $id) ?? [];
    }
    return standards_find_or_create_catalog($data + ['standard_code' => $code, 'standard_name' => $name, 'status' => $status]);
}

function standards_save_version(array $data): array
{
    $id = (int) ($data['id'] ?? 0);
    $standardId = (int) ($data['standard_id'] ?? 0);
    $label = trim((string) ($data['version_label'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'published'));
    foreach (['published_on', 'effective_from', 'transition_until'] as $dateField) {
        if (!standards_valid_date(trim((string) ($data[$dateField] ?? '')))) throw new InvalidArgumentException('invalid_date');
    }
    if ($standardId <= 0 || $label === '' || !in_array($status, standards_allowed_statuses(), true)) throw new InvalidArgumentException('required_fields');
    $standard = standards_row('standards', $standardId);
    if (!$standard || !organization_company_in_scope((int) $standard['company_id'])) throw new RuntimeException('permission_denied', 403);
    $userId = (int) (current_user()['id'] ?? 0) ?: null;
    if ($id > 0) {
        $current = standards_row('versions', $id);
        if (!$current) throw new InvalidArgumentException('invalid_record');
        if (!organization_company_in_scope((int) $current['company_id'])) throw new RuntimeException('permission_denied', 403);
        $stmt = db()->prepare('UPDATE standards_versions SET standard_id=:standard,version_label=:label,published_on=:published,effective_from=:effective,transition_until=:transition,status=:status,updated_by_user_id=:user WHERE id=:id');
        $stmt->execute([':standard'=>$standardId,':label'=>$label,':published'=>trim((string)($data['published_on']??''))?:null,':effective'=>trim((string)($data['effective_from']??''))?:null,':transition'=>trim((string)($data['transition_until']??''))?:null,':status'=>$status,':user'=>$userId,':id'=>$id]);
        return standards_row('versions', $id) ?? [];
    }
    return standards_find_or_create_version($standardId, $data + ['version_label' => $label, 'status' => $status]);
}

function standards_save_clause(array $data): array
{
    $id = (int) ($data['id'] ?? 0);
    $versionId = (int) ($data['version_id'] ?? 0);
    $parentId = (int) ($data['parent_clause_id'] ?? 0);
    $code = trim((string) ($data['clause_code'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'active'));
    if ($versionId <= 0 || $code === '' || $title === '' || !in_array($status, standards_allowed_statuses(), true)) throw new InvalidArgumentException('required_fields');
    $version = standards_row('versions', $versionId);
    if (!$version || !organization_company_in_scope((int) $version['company_id'])) throw new RuntimeException('permission_denied', 403);
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE standards_clauses SET version_id=:version,parent_clause_id=:parent,clause_code=:code,title=:title,body=:body,sort_order=:sort,status=:status WHERE id=:id');
        $stmt->execute([':version'=>$versionId,':parent'=>$parentId>0?$parentId:null,':code'=>$code,':title'=>$title,':body'=>trim((string)($data['body']??''))?:null,':sort'=>(int)($data['sort_order']??0),':status'=>$status,':id'=>$id]);
        $row = db()->prepare('SELECT * FROM standards_clauses WHERE id=:id');
        $row->execute([':id'=>$id]);
        return $row->fetch(PDO::FETCH_ASSOC) ?: [];
    }
    return standards_find_or_create_clause($versionId, $data + ['clause_code' => $code, 'title' => $title, 'status' => $status]);
}

function standards_save_requirement(array $data): array
{
    $id = (int) ($data['id'] ?? 0);
    $versionId = (int) ($data['version_id'] ?? 0);
    $clauseId = (int) ($data['clause_id'] ?? 0);
    $code = trim((string) ($data['requirement_code'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $text = trim((string) ($data['requirement_text'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'active'));
    if ($versionId <= 0 || $clauseId <= 0 || $code === '' || $title === '' || $text === '' || !in_array($status, standards_allowed_statuses(), true)) throw new InvalidArgumentException('required_fields');
    $version = standards_row('versions', $versionId);
    if (!$version || !organization_company_in_scope((int) $version['company_id'])) throw new RuntimeException('permission_denied', 403);
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE standards_requirements SET version_id=:version,clause_id=:clause,requirement_code=:code,title=:title,requirement_text=:text,verification_method=:verification,criticality=:criticality,status=:status WHERE id=:id');
        $stmt->execute([':version'=>$versionId,':clause'=>$clauseId,':code'=>$code,':title'=>$title,':text'=>$text,':verification'=>trim((string)($data['verification_method']??''))?:null,':criticality'=>trim((string)($data['criticality']??'normal'))?:'normal',':status'=>$status,':id'=>$id]);
        $entityStmt = db()->prepare("SELECT id FROM qms_entities WHERE domain_table='standards_requirements' AND domain_record_id=:id LIMIT 1");
        $entityStmt->execute([':id'=>$id]);
        $entityId = (int) $entityStmt->fetchColumn();
        if ($entityId > 0) {
            qms_entities_update($entityId, ['company_id'=>(int)$version['company_id'], 'title'=>$title, 'description'=>$text, 'status'=>'active', 'enforce_scope'=>false]);
        }
        return standards_row('requirements', $id) ?? [];
    }
    return standards_find_or_create_requirement($versionId, $clauseId, $data + ['requirement_code' => $code, 'title' => $title, 'requirement_text' => $text, 'status' => $status]);
}

function standards_save_control(array $data): array
{
    $id = (int) ($data['id'] ?? 0);
    $requirementId = (int) ($data['requirement_id'] ?? 0);
    $code = trim((string) ($data['control_code'] ?? ''));
    $title = trim((string) ($data['title'] ?? ''));
    $text = trim((string) ($data['control_text'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'active'));
    if ($requirementId <= 0 || $code === '' || $title === '' || $text === '' || !in_array($status, standards_allowed_statuses(), true)) throw new InvalidArgumentException('required_fields');
    $requirement = standards_row('requirements', $requirementId);
    if (!$requirement || !organization_company_in_scope((int) $requirement['company_id'])) throw new RuntimeException('permission_denied', 403);
    if ($id > 0) {
        $stmt = db()->prepare('UPDATE standards_controls SET requirement_id=:requirement,control_code=:code,title=:title,control_text=:text,control_type=:type,status=:status WHERE id=:id');
        $stmt->execute([':requirement'=>$requirementId,':code'=>$code,':title'=>$title,':text'=>$text,':type'=>trim((string)($data['control_type']??''))?:null,':status'=>$status,':id'=>$id]);
        return standards_row('controls', $id) ?? [];
    }
    return standards_find_or_create_control($requirementId, $data + ['control_code' => $code, 'title' => $title, 'control_text' => $text, 'status' => $status]);
}

function standards_save_resource(string $resource, array $data): array
{
    return match ($resource) {
        'standards' => standards_save_standard($data),
        'versions' => standards_save_version($data),
        'clauses' => standards_save_clause($data),
        'requirements' => standards_save_requirement($data),
        'controls' => standards_save_control($data),
        default => throw new InvalidArgumentException('invalid_resource'),
    };
}

function standards_requirement_entity(int $requirementId): ?array
{
    if ($requirementId <= 0) return null;
    $stmt = db()->prepare("SELECT * FROM qms_entities WHERE domain_table='standards_requirements' AND domain_record_id=:id AND entity_type='requirement' AND status <> 'archived' LIMIT 1");
    $stmt->execute([':id' => $requirementId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function standards_mapping_entity_options(int $companyId): array
{
    if ($companyId <= 0 || !organization_company_in_scope($companyId)) return [];
    $stmt = db()->prepare("SELECT id, entity_code, title, entity_type FROM qms_entities WHERE company_id=:company AND status <> 'archived' AND entity_type <> 'requirement' ORDER BY entity_type, entity_code");
    $stmt->execute([':company' => $companyId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function standards_requirement_mappings(int $requirementId): array
{
    $requirement = standards_row('requirements', $requirementId);
    $requirementEntity = standards_requirement_entity($requirementId);
    if (!$requirement || !$requirementEntity || !organization_company_in_scope((int) $requirement['company_id'])) return [];
    $stmt = db()->prepare("\n        SELECT r.id, r.relationship_type, r.description, r.status, r.created_at,
               se.entity_code AS source_code, se.title AS source_title, se.entity_type AS source_entity_type
        FROM qms_entity_relationships r
        JOIN qms_entities se ON se.id=r.source_entity_id
        WHERE r.target_entity_id=:target AND r.company_id=:company AND r.status <> 'archived'
        ORDER BY r.id DESC
    ");
    $stmt->execute([':target' => (int) $requirementEntity['id'], ':company' => (int) $requirement['company_id']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$row) $row['relationship_type_name'] = qms_relationships_type_label((string) $row['relationship_type']);
    unset($row);
    return $rows;
}

function standards_map_requirement(array $data): array
{
    $requirementId = (int) ($data['requirement_id'] ?? 0);
    $sourceEntityId = (int) ($data['source_entity_id'] ?? 0);
    $relationshipType = trim((string) ($data['relationship_type'] ?? 'satisfies_requirement'));
    $description = trim((string) ($data['description'] ?? ''));
    $requirement = standards_row('requirements', $requirementId);
    $requirementEntity = standards_requirement_entity($requirementId);
    if (!$requirement || !$requirementEntity || $sourceEntityId <= 0) throw new InvalidArgumentException('required_fields');
    $companyId = (int) $requirement['company_id'];
    if (!organization_company_in_scope($companyId)) throw new RuntimeException('permission_denied', 403);
    if (qms_relationships_entity_company($sourceEntityId) !== $companyId) throw new InvalidArgumentException('invalid_entity_scope');
    if ($sourceEntityId === (int) $requirementEntity['id']) throw new InvalidArgumentException('same_entity_error');
    $existing = db()->prepare("SELECT id FROM qms_entity_relationships WHERE company_id=:company AND source_entity_id=:source AND target_entity_id=:target AND relationship_type=:type AND status <> 'archived' LIMIT 1");
    $existing->execute([':company'=>$companyId, ':source'=>$sourceEntityId, ':target'=>(int)$requirementEntity['id'], ':type'=>$relationshipType]);
    $existingId = (int) $existing->fetchColumn();
    if ($existingId > 0) return qms_relationships_row($existingId) ?? [];
    $relationship = qms_relationships_save([
        'company_id' => $companyId,
        'source_entity_id' => $sourceEntityId,
        'target_entity_id' => (int) $requirementEntity['id'],
        'relationship_type' => $relationshipType,
        'description' => $description,
        'status' => 'active',
    ]);
    qms_events_publish([
        'event_type' => 'requirement.mapped.v1',
        'entity_type' => 'requirement',
        'entity_id' => (int) $requirementEntity['id'],
        'company_id' => $companyId,
        'actor_type' => 'user',
        'payload_version' => 1,
        'payload' => [
            'requirement_id' => $requirementId,
            'relationship_id' => (int) ($relationship['id'] ?? 0),
            'source_entity_id' => $sourceEntityId,
            'relationship_type' => $relationshipType,
        ],
        'source_module' => 'standards',
    ]);
    return $relationship;
}

function standards_unmap_requirement(int $relationshipId): void
{
    $row = qms_relationships_row($relationshipId);
    if (!$row || (string) $row['target_entity_type'] !== 'requirement') throw new InvalidArgumentException('invalid_record');
    if (!organization_company_in_scope((int) $row['company_id'])) throw new RuntimeException('permission_denied', 403);
    $userId = (int) (current_user()['id'] ?? 0) ?: null;
    $stmt = db()->prepare("UPDATE qms_entity_relationships SET status='archived',archived_at=NOW(),archived_by_user_id=:archived_user,updated_by_user_id=:updated_user WHERE id=:id AND status <> 'archived'");
    $stmt->execute([':archived_user' => $userId, ':updated_user' => $userId, ':id' => $relationshipId]);
}
