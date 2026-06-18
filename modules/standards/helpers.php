<?php

require_once BASE_PATH . '/modules/organization/helpers.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';
require_once BASE_PATH . '/modules/standards/language.php';

function standards_uuid(): string
{
    return qms_entities_uuid();
}

function standards_allowed_statuses(): array
{
    return ['draft', 'active', 'published', 'archived'];
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
