<?php

function kirpi_table_request(int $defaultLength = 10, int $maxLength = 100): array
{
    $length = (int) ($_GET['length'] ?? $defaultLength);

    return [
        'draw' => max(0, (int) ($_GET['draw'] ?? 0)),
        'start' => max(0, (int) ($_GET['start'] ?? 0)),
        'length' => min($maxLength, max(10, $length)),
        'columns' => is_array($_GET['columns'] ?? null) ? $_GET['columns'] : [],
        'orders' => is_array($_GET['order'] ?? null) ? $_GET['order'] : [],
        'search' => trim((string) ($_GET['search']['value'] ?? '')),
    ];
}

function kirpi_table_column_searches(array $request): array
{
    $searches = [];
    foreach (($request['columns'] ?? []) as $column) {
        if (!is_array($column)) {
            continue;
        }
        $name = (string) ($column['name'] ?? $column['data'] ?? '');
        $value = trim((string) ($column['search']['value'] ?? ''));
        if ($name !== '' && $value !== '') {
            $searches[$name] = $value;
        }
    }

    return $searches;
}

function kirpi_table_order_sql(array $request, array $columnMap, string $fallback): string
{
    $parts = [];
    foreach (array_slice($request['orders'] ?? [], 0, 3) as $order) {
        if (!is_array($order)) {
            continue;
        }
        $index = (int) ($order['column'] ?? -1);
        $column = $request['columns'][$index] ?? null;
        $name = is_array($column) ? (string) ($column['name'] ?? $column['data'] ?? '') : '';
        if (!isset($columnMap[$name])) {
            continue;
        }
        $direction = strtolower((string) ($order['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
        $parts[] = $columnMap[$name] . ' ' . $direction;
    }

    return $parts ? implode(', ', $parts) : $fallback;
}

function kirpi_table_bind(PDOStatement $statement, array $params): void
{
    foreach ($params as $key => $value) {
        $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}

function kirpi_table_response(array $request, int $total, int $filtered, array $data): never
{
    json_response([
        'draw' => (int) ($request['draw'] ?? 0),
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ]);
}
