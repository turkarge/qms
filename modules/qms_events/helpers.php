<?php

require_once BASE_PATH . '/modules/organization/helpers.php';
require_once BASE_PATH . '/modules/qms_entities/helpers.php';
require_once BASE_PATH . '/modules/qms_events/language.php';

function qms_events_uuid(): string
{
    return qms_entities_uuid();
}

function qms_events_allowed_actor_types(): array
{
    return ['user', 'system', 'rule', 'integration'];
}

function qms_events_type_label(string $eventType): string
{
    return qms_events_lang('type_' . str_replace('.', '_', $eventType), $eventType);
}

function qms_events_actor_label(string $actorType, ?string $userName = null): string
{
    if ($actorType === 'user' && $userName !== null && $userName !== '') {
        return $userName;
    }
    return qms_events_lang('actor_' . $actorType, $actorType);
}

function qms_events_json_encode(array $value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function qms_events_publish(array $event): array
{
    if (!db_table_exists('qms_domain_events')) {
        throw new RuntimeException('qms_events_schema_missing');
    }

    $eventType = trim((string) ($event['event_type'] ?? ''));
    $entityType = trim((string) ($event['entity_type'] ?? ''));
    $entityId = (int) ($event['entity_id'] ?? 0);
    $companyId = (int) ($event['company_id'] ?? 0);
    $actorType = trim((string) ($event['actor_type'] ?? 'user'));
    $sourceModule = trim((string) ($event['source_module'] ?? 'qms'));
    $payloadVersion = (int) ($event['payload_version'] ?? 1);
    $payload = $event['payload'] ?? [];
    $metadata = $event['metadata'] ?? [];

    if ($eventType === '' || $entityType === '' || $entityId <= 0 || $companyId <= 0 || $sourceModule === '' || $payloadVersion <= 0) {
        throw new InvalidArgumentException('required_fields');
    }
    if (!in_array($actorType, qms_events_allowed_actor_types(), true)) {
        throw new InvalidArgumentException('invalid_actor_type');
    }
    if (!organization_company_in_scope($companyId)) {
        throw new RuntimeException('permission_denied', 403);
    }
    if (!is_array($payload) || !is_array($metadata)) {
        throw new InvalidArgumentException('invalid_payload');
    }

    $actorUserId = array_key_exists('actor_user_id', $event) ? (int) $event['actor_user_id'] : (int) (current_user()['id'] ?? 0);
    if ($actorType !== 'user') {
        $actorUserId = 0;
    }

    $occurredAt = trim((string) ($event['occurred_at'] ?? ''));
    if ($occurredAt === '') {
        $occurredAt = date('Y-m-d H:i:s');
    }

    $eventId = trim((string) ($event['event_id'] ?? '')) ?: qms_events_uuid();
    $correlationId = trim((string) ($event['correlation_id'] ?? '')) ?: $eventId;
    $causationId = trim((string) ($event['causation_id'] ?? ''));
    $requestId = trim((string) ($event['request_id'] ?? ''));

    $stmt = db()->prepare("
        INSERT INTO qms_domain_events(
            event_id,event_type,entity_type,entity_id,company_id,facility_id,department_id,
            actor_type,actor_user_id,correlation_id,causation_id,payload_version,payload,
            metadata,source_module,request_id,occurred_at
        ) VALUES(
            :event_id,:event_type,:entity_type,:entity_id,:company_id,:facility_id,:department_id,
            :actor_type,:actor_user_id,:correlation_id,:causation_id,:payload_version,:payload,
            :metadata,:source_module,:request_id,:occurred_at
        )
    ");
    $stmt->execute([
        ':event_id' => $eventId,
        ':event_type' => $eventType,
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':company_id' => $companyId,
        ':facility_id' => !empty($event['facility_id']) ? (int) $event['facility_id'] : null,
        ':department_id' => !empty($event['department_id']) ? (int) $event['department_id'] : null,
        ':actor_type' => $actorType,
        ':actor_user_id' => $actorUserId > 0 ? $actorUserId : null,
        ':correlation_id' => $correlationId,
        ':causation_id' => $causationId !== '' ? $causationId : null,
        ':payload_version' => $payloadVersion,
        ':payload' => qms_events_json_encode($payload),
        ':metadata' => $metadata ? qms_events_json_encode($metadata) : null,
        ':source_module' => $sourceModule,
        ':request_id' => $requestId !== '' ? $requestId : null,
        ':occurred_at' => $occurredAt,
    ]);

    return qms_events_row((int) db()->lastInsertId()) ?? [];
}

function qms_events_row(int $id): ?array
{
    $stmt = db()->prepare("
        SELECT e.*, c.company_name, u.name AS actor_user_name, qe.entity_code, qe.title AS entity_title
        FROM qms_domain_events e
        JOIN organization_companies c ON c.id = e.company_id
        LEFT JOIN users u ON u.id = e.actor_user_id
        LEFT JOIN qms_entities qe ON qe.id = e.entity_id
        WHERE e.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    $row['event_type_name'] = qms_events_type_label((string) $row['event_type']);
    $row['actor_name'] = qms_events_actor_label((string) $row['actor_type'], $row['actor_user_name'] ?? null);
    $row['entity_name'] = trim((string) ($row['entity_code'] ?? '') . ' - ' . (string) ($row['entity_title'] ?? ''), ' -');
    $row['row_key'] = 'events-' . $id;
    return $row;
}
