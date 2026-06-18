<?php

require_once BASE_PATH . '/modules/organization/helpers.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';

function qms_relationships_uuid(): string
{
    return qms_entities_uuid();
}

function qms_relationships_allowed_statuses(): array
{
    return ['active', 'inactive', 'archived'];
}

function qms_relationships_allowed_kinds(): array
{
    return ['direct', 'reference', 'evidence', 'dependency'];
}

function qms_relationships_valid_date(string $date): bool
{
    if ($date === '') return true;
    $value = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $value !== false && $value->format('Y-m-d') === $date;
}

function qms_relationships_type(string $relationshipType): ?array
{
    $stmt = db()->prepare("SELECT * FROM qms_relationship_types WHERE relationship_type = :type AND status = 'active' LIMIT 1");
    $stmt->execute([':type' => $relationshipType]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function qms_relationships_types(): array
{
    if (!db_table_exists('qms_relationship_types')) return [];
    return db()->query("SELECT relationship_type, relationship_kind, display_name FROM qms_relationship_types WHERE status = 'active' ORDER BY relationship_kind, display_name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function qms_relationships_row(int $id): ?array
{
    $stmt = db()->prepare("
        SELECT r.*, c.company_name, rt.display_name AS relationship_type_name,
               se.entity_code AS source_code, se.title AS source_title, se.entity_type AS source_entity_type,
               te.entity_code AS target_code, te.title AS target_title, te.entity_type AS target_entity_type
        FROM qms_entity_relationships r
        JOIN organization_companies c ON c.id = r.company_id
        JOIN qms_relationship_types rt ON rt.relationship_type = r.relationship_type
        JOIN qms_entities se ON se.id = r.source_entity_id
        JOIN qms_entities te ON te.id = r.target_entity_id
        WHERE r.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['row_key'] = 'relationships-' . $id;
    return $row;
}

function qms_relationships_entity_options(): array
{
    $params = [];
    $where = ["x.status <> 'archived'"];
    $scope = organization_scope_where('x.company_id', $params);
    if ($scope !== null) $where[] = $scope;
    $stmt = db()->prepare("
        SELECT x.id, x.company_id, x.entity_code, x.title, x.entity_type, c.company_code, c.company_name
        FROM qms_entities x
        JOIN organization_companies c ON c.id = x.company_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.company_name, x.entity_code
    ");
    kirpi_table_bind($stmt, $params);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function qms_relationships_select_options(): array
{
    $organization = organization_select_options();
    return [
        'companies' => $organization['companies'],
        'entities' => qms_relationships_entity_options(),
        'types' => qms_relationships_types(),
        'statuses' => qms_relationships_allowed_statuses(),
    ];
}

function qms_relationships_entity_company(int $entityId): ?int
{
    if ($entityId <= 0) return null;
    $stmt = db()->prepare("SELECT company_id FROM qms_entities WHERE id = :id AND status <> 'archived' LIMIT 1");
    $stmt->execute([':id' => $entityId]);
    $companyId = (int) $stmt->fetchColumn();
    return $companyId > 0 ? $companyId : null;
}

function qms_relationships_save(array $data): array
{
    $id = (int) ($data['id'] ?? 0);
    $companyId = (int) ($data['company_id'] ?? 0);
    $sourceId = (int) ($data['source_entity_id'] ?? 0);
    $targetId = (int) ($data['target_entity_id'] ?? 0);
    $relationshipType = trim((string) ($data['relationship_type'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));
    $evidenceStrength = trim((string) ($data['evidence_strength'] ?? ''));
    $status = trim((string) ($data['status'] ?? 'active'));
    $validFrom = trim((string) ($data['valid_from'] ?? ''));
    $validUntil = trim((string) ($data['valid_until'] ?? ''));
    if ($companyId <= 0 || $sourceId <= 0 || $targetId <= 0 || $relationshipType === '' || !in_array($status, qms_relationships_allowed_statuses(), true)) throw new InvalidArgumentException('required_fields');
    if ($sourceId === $targetId) throw new InvalidArgumentException('same_entity_error');
    if (!qms_relationships_valid_date($validFrom) || !qms_relationships_valid_date($validUntil) || ($validFrom !== '' && $validUntil !== '' && $validUntil < $validFrom)) throw new InvalidArgumentException('invalid_date_range');
    if (!organization_company_in_scope($companyId)) throw new RuntimeException('permission_denied', 403);
    $type = qms_relationships_type($relationshipType);
    if (!$type || !in_array((string) $type['relationship_kind'], qms_relationships_allowed_kinds(), true)) throw new InvalidArgumentException('required_fields');
    if (qms_relationships_entity_company($sourceId) !== $companyId || qms_relationships_entity_company($targetId) !== $companyId) throw new InvalidArgumentException('invalid_entity_scope');
    $userId = (int) (current_user()['id'] ?? 0) ?: null;
    if ($id > 0) {
        $current = qms_relationships_row($id);
        if (!$current) throw new InvalidArgumentException('invalid_record');
        if (!organization_company_in_scope((int) $current['company_id'])) throw new RuntimeException('permission_denied', 403);
        $stmt = db()->prepare("UPDATE qms_entity_relationships SET company_id=:company,source_entity_id=:source,target_entity_id=:target,relationship_type=:type,relationship_kind=:kind,description=:description,evidence_strength=:strength,status=:status,valid_from=:valid_from,valid_until=:valid_until,updated_by_user_id=:user WHERE id=:id");
        $stmt->execute([':company'=>$companyId,':source'=>$sourceId,':target'=>$targetId,':type'=>$relationshipType,':kind'=>(string)$type['relationship_kind'],':description'=>$description!==''?$description:null,':strength'=>$evidenceStrength!==''?$evidenceStrength:null,':status'=>$status,':valid_from'=>$validFrom!==''?$validFrom:null,':valid_until'=>$validUntil!==''?$validUntil:null,':user'=>$userId,':id'=>$id]);
        return qms_relationships_row($id) ?? [];
    }
    $stmt = db()->prepare("INSERT INTO qms_entity_relationships(relationship_uid,company_id,source_entity_id,target_entity_id,relationship_type,relationship_kind,description,evidence_strength,status,valid_from,valid_until,created_by_user_id,updated_by_user_id) VALUES(:uid,:company,:source,:target,:type,:kind,:description,:strength,:status,:valid_from,:valid_until,:created_user,:updated_user)");
    $stmt->execute([':uid'=>qms_relationships_uuid(),':company'=>$companyId,':source'=>$sourceId,':target'=>$targetId,':type'=>$relationshipType,':kind'=>(string)$type['relationship_kind'],':description'=>$description!==''?$description:null,':strength'=>$evidenceStrength!==''?$evidenceStrength:null,':status'=>$status,':valid_from'=>$validFrom!==''?$validFrom:null,':valid_until'=>$validUntil!==''?$validUntil:null,':created_user'=>$userId,':updated_user'=>$userId]);
    return qms_relationships_row((int) db()->lastInsertId()) ?? [];
}
