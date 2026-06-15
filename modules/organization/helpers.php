<?php

function organization_resource_config(string $resource): ?array
{
    return [
        'companies' => ['table' => 'organization_companies', 'entity' => 'company', 'create' => 'organization.create', 'edit' => 'organization.edit'],
        'units' => ['table' => 'organization_units', 'entity' => 'organization_unit', 'create' => 'organization.create', 'edit' => 'organization.edit'],
        'positions' => ['table' => 'organization_positions', 'entity' => 'position', 'create' => 'organization.create', 'edit' => 'organization.edit'],
        'assignments' => ['table' => 'organization_user_assignments', 'entity' => 'organization_user_assignment', 'create' => 'organization.assign', 'edit' => 'organization.assign'],
    ][$resource] ?? null;
}

function organization_allowed_unit_types(): array
{
    return ['facility', 'location', 'department', 'team'];
}

function organization_allowed_scope_modes(): array
{
    return ['self', 'team', 'department', 'department_descendants', 'facility', 'company', 'global'];
}

function organization_normalize_code(string $code): string
{
    return strtoupper(trim($code));
}

function organization_valid_code(string $code): bool
{
    return preg_match('/^[A-Z0-9][A-Z0-9_-]{1,39}$/', organization_normalize_code($code)) === 1;
}

function organization_active_assignments(int $userId): array
{
    if ($userId <= 0 || !db_table_exists('organization_user_assignments')) {
        return [];
    }
    $stmt = db()->prepare("SELECT * FROM organization_user_assignments WHERE user_id = :user_id AND status = 'active' AND (starts_at IS NULL OR starts_at <= NOW()) AND (ends_at IS NULL OR ends_at >= NOW()) ORDER BY is_primary DESC, id ASC");
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function organization_user_scope(int $userId): array
{
    $assignments = organization_active_assignments($userId);
    $scope = ['global' => false, 'company_ids' => [], 'unit_ids' => [], 'modes' => []];
    foreach ($assignments as $assignment) {
        $mode = (string) ($assignment['scope_mode'] ?? 'self');
        $scope['modes'][] = $mode;
        if ($mode === 'global') {
            $scope['global'] = true;
        }
        $companyId = (int) ($assignment['company_id'] ?? 0);
        $unitId = (int) ($assignment['unit_id'] ?? 0);
        if ($companyId > 0) $scope['company_ids'][] = $companyId;
        if ($unitId > 0) $scope['unit_ids'][] = $unitId;
    }
    $scope['company_ids'] = array_values(array_unique($scope['company_ids']));
    $scope['unit_ids'] = array_values(array_unique($scope['unit_ids']));
    $scope['modes'] = array_values(array_unique($scope['modes']));
    return $scope;
}

function organization_entity_in_scope(array $entity, ?array $user = null): bool
{
    $user = $user ?? current_user();
    if (($user['role_name'] ?? '') === 'Super Admin') return true;
    $scope = organization_user_scope((int) ($user['id'] ?? 0));
    if ($scope['global']) return true;
    $companyId = (int) ($entity['company_id'] ?? 0);
    $unitId = (int) ($entity['unit_id'] ?? $entity['department_id'] ?? 0);
    return in_array($companyId, $scope['company_ids'], true) && ($unitId === 0 || in_array($unitId, $scope['unit_ids'], true) || in_array('company', $scope['modes'], true));
}

function organization_company_in_scope(int $companyId, ?array $user = null): bool
{
    if ($companyId <= 0) return false;
    $user = $user ?? current_user();
    if (($user['role_name'] ?? '') === 'Super Admin') return true;
    $scope = organization_user_scope((int) ($user['id'] ?? 0));
    return $scope['global'] || in_array($companyId, $scope['company_ids'], true);
}

function organization_scope_where(string $companyColumn, array &$params, ?array $user = null): ?string
{
    $user = $user ?? current_user();
    if (($user['role_name'] ?? '') === 'Super Admin') return null;
    $scope = organization_user_scope((int) ($user['id'] ?? 0));
    if ($scope['global']) return null;
    if (!$scope['company_ids']) return '1 = 0';
    $placeholders = [];
    foreach ($scope['company_ids'] as $index => $companyId) {
        $key = ':scope_company_' . $index;
        $placeholders[] = $key;
        $params[$key] = $companyId;
    }
    return $companyColumn . ' IN (' . implode(', ', $placeholders) . ')';
}

function organization_select_options(): array
{
    $companies = db()->query("SELECT id, company_code, company_name FROM organization_companies WHERE status = 'active' ORDER BY company_name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $units = db()->query("SELECT id, company_id, parent_unit_id, unit_type, unit_code, unit_name FROM organization_units WHERE status = 'active' ORDER BY company_id, sort_order, unit_name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $positions = db()->query("SELECT id, company_id, department_unit_id, position_code, position_name FROM organization_positions WHERE status = 'active' ORDER BY position_name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $users = db()->query("SELECT id, name, email FROM users WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $user = current_user();
    if (($user['role_name'] ?? '') !== 'Super Admin') {
        $scope = organization_user_scope((int) ($user['id'] ?? 0));
        if (!$scope['global']) {
            $allowed = array_flip($scope['company_ids']);
            $companies = array_values(array_filter($companies, static fn(array $row): bool => isset($allowed[(int) $row['id']])));
            $units = array_values(array_filter($units, static fn(array $row): bool => isset($allowed[(int) $row['company_id']])));
            $positions = array_values(array_filter($positions, static fn(array $row): bool => isset($allowed[(int) $row['company_id']])));
        }
    }
    return compact('companies', 'units', 'positions', 'users');
}
