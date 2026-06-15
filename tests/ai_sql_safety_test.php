<?php

require_once dirname(__DIR__) . '/core/ai.php';

$failures = [];

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$allowedTables = ['users', 'roles'];
$allowedFields = [
    'users' => ['id', 'is_active', 'created_at', 'updated_at', 'role_id'],
    'roles' => ['is_active'],
];

$clean = kirpi_ai_extract_sql_from_model_text('<think>private reasoning</think>{"sql":"SELECT id, created_at, updated_at, role_id, is_active FROM users WHERE is_active = true","confidence":0.95,"warnings":[]}');
$assert(
    ($clean['sql'] ?? '') === 'SELECT id, created_at, updated_at, role_id, is_active FROM users WHERE is_active = true',
    'Reasoning block must be removed while preserving JSON SQL.'
);
$assert(in_array('model_reasoning_stripped', (array) ($clean['warnings'] ?? []), true), 'Reasoning removal warning is missing.');

$fencedJson = kirpi_ai_extract_sql_from_model_text("```json\n{\"sql\":\"SELECT id FROM users\",\"confidence\":0.9,\"warnings\":[]}\n```");
$assert(($fencedJson['sql'] ?? '') === 'SELECT id FROM users', 'Fenced JSON response must be parsed.');

$proseJson = kirpi_ai_extract_sql_from_model_text('Result: {"sql":"SELECT id FROM users","confidence":0.8,"warnings":[]}');
$assert(($proseJson['sql'] ?? '') === 'SELECT id FROM users', 'JSON embedded after prose must be parsed.');
$assert(in_array('json_object_extracted', (array) ($proseJson['warnings'] ?? []), true), 'Embedded JSON extraction warning is missing.');

$nestedJson = kirpi_ai_extract_json_object_from_model_text('prefix {"status":"ok","purpose":"provider_test","meta":{"message":"brace } inside string"}} suffix {invalid}');
$assert(($nestedJson['purpose'] ?? '') === 'provider_test', 'Balanced JSON extraction must support nested objects and braces inside strings.');

$prose = kirpi_ai_extract_sql_from_model_text('The safest answer is to list active users.');
$assert(($prose['sql'] ?? '') === '', 'Prose-only model output must not become SQL.');
$assert(in_array('sql_statement_missing', (array) ($prose['warnings'] ?? []), true), 'Missing SQL warning is required.');

$guard = kirpi_ai_sql_guard_readonly((string) ($clean['sql'] ?? ''), [
    'allowed_tables' => $allowedTables,
    'allowed_fields' => $allowedFields,
]);
$assert(!empty($guard['allowed']), 'Known-safe provider SQL must pass the guard.');

$unknownField = kirpi_ai_sql_guard_readonly('SELECT id, password_hash FROM users WHERE is_active = true', [
    'allowed_tables' => $allowedTables,
    'allowed_fields' => $allowedFields,
]);
$assert(empty($unknownField['allowed']), 'A field outside planner context must be blocked.');
$assert(in_array('field_not_allowed', (array) ($unknownField['reasons'] ?? []), true), 'Field rejection reason is missing.');
$assert(in_array('password_hash', (array) ($unknownField['blocked_fields'] ?? []), true), 'Blocked field must be reported.');

$qualifiedUnknownField = kirpi_ai_sql_guard_readonly('SELECT u.id, u.password_hash FROM users u WHERE u.is_active = true', [
    'allowed_tables' => $allowedTables,
    'allowed_fields' => $allowedFields,
]);
$assert(empty($qualifiedUnknownField['allowed']), 'A qualified field outside planner context must be blocked.');
$assert(in_array('users.password_hash', (array) ($qualifiedUnknownField['blocked_fields'] ?? []), true), 'Qualified blocked field must be reported.');

$wildcard = kirpi_ai_build_sql_candidate([
    'question' => 'aktif kullanıcıları listele',
    'candidate_sql' => 'SELECT * FROM users',
    'model_adapter' => 'test-adapter',
    'allowed_tables' => $allowedTables,
    'allowed_fields' => $allowedFields,
]);
$assert(($wildcard['status'] ?? '') === 'blocked', 'Wildcard candidate must be blocked before preview.');

$unsafeTable = kirpi_ai_build_sql_candidate([
    'question' => 'aktif kullanıcıları listele',
    'candidate_sql' => 'SELECT id FROM audit_logs',
    'model_adapter' => 'test-adapter',
    'allowed_tables' => $allowedTables,
    'allowed_fields' => $allowedFields,
]);
$assert(($unsafeTable['status'] ?? '') === 'blocked', 'Candidate using a table outside planner context must be blocked.');

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_map(static fn (string $failure): string => 'FAIL: ' . $failure, $failures)) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "AI SQL safety tests passed.\n");
