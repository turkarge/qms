<?php

require_once BASE_PATH . '/modules/organization/helpers.php';

function governance_resource_config(string $resource): ?array
{
    return [
        'ownerships' => ['table' => 'governance_ownership_assignments', 'permission' => 'governance.ownership.manage', 'entity' => 'ownership_assignment'],
        'delegations' => ['table' => 'governance_delegations', 'permission' => 'governance.delegation.manage', 'entity' => 'delegation'],
    ][$resource] ?? null;
}

function governance_subject_types(): array
{
    return ['process', 'standard', 'requirement', 'controlled_document', 'risk', 'capa'];
}

function governance_ownership_types(): array
{
    return ['process_owner', 'standard_owner', 'requirement_owner', 'document_owner', 'risk_owner', 'capa_owner'];
}

function governance_valid_date(string $date): bool
{
    return organization_valid_date($date);
}

function governance_effective_status(array $row): string
{
    if (($row['status'] ?? '') === 'revoked') return 'revoked';
    $today = date('Y-m-d');
    if (!empty($row['ends_on']) && (string) $row['ends_on'] < $today) return 'expired';
    if (!empty($row['starts_on']) && (string) $row['starts_on'] > $today) return 'inactive';
    return (string) ($row['status'] ?? 'active');
}

function governance_select_options(): array
{
    $organization = organization_select_options();
    $users = db()->query("SELECT u.id,u.name,u.email,GROUP_CONCAT(DISTINCT a.company_id ORDER BY a.company_id) AS company_ids FROM users u JOIN organization_user_assignments a ON a.user_id=u.id AND a.status='active' AND (a.starts_at IS NULL OR a.starts_at<=NOW()) AND (a.ends_at IS NULL OR a.ends_at>=NOW()) WHERE u.is_active=1 GROUP BY u.id,u.name,u.email ORDER BY u.name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $allowedCompanies = array_flip(array_map(static fn(array $company): int => (int) $company['id'], $organization['companies']));
    foreach ($users as &$user) {
        $companyIds = array_values(array_filter(array_map('intval', explode(',', (string) $user['company_ids'])), static fn(int $companyId): bool => isset($allowedCompanies[$companyId])));
        $user['company_ids'] = $companyIds;
    }
    unset($user);
    $users = array_values(array_filter($users, static fn(array $user): bool => $user['company_ids'] !== []));
    return ['companies' => $organization['companies'], 'users' => $users];
}

function governance_user_in_company(int $userId, int $companyId): bool
{
    if ($userId <= 0 || $companyId <= 0) return false;
    $stmt = db()->prepare("SELECT COUNT(*) FROM organization_user_assignments WHERE user_id=:user_id AND company_id=:company_id AND status='active' AND (starts_at IS NULL OR starts_at<=NOW()) AND (ends_at IS NULL OR ends_at>=NOW())");
    $stmt->execute([':user_id' => $userId, ':company_id' => $companyId]);
    return (int) $stmt->fetchColumn() > 0;
}

function governance_resource_row(string $resource, int $id): ?array
{
    $sql = $resource === 'ownerships'
        ? 'SELECT x.*,c.company_name,u.name AS owner_name FROM governance_ownership_assignments x JOIN organization_companies c ON c.id=x.company_id JOIN users u ON u.id=x.owner_user_id WHERE x.id=:id'
        : 'SELECT x.*,c.company_name,fu.name AS from_user_name,tu.name AS to_user_name FROM governance_delegations x JOIN organization_companies c ON c.id=x.company_id JOIN users fu ON fu.id=x.from_user_id JOIN users tu ON tu.id=x.to_user_id WHERE x.id=:id';
    $stmt = db()->prepare($sql); $stmt->execute([':id' => $id]); $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $row['status'] = governance_effective_status($row);
    $row['row_key'] = $resource . '-' . $id;
    return $row;
}
