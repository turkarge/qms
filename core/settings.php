<?php

$GLOBALS['kirpi_settings_cache'] = null;

function kirpi_settings_table_ready(): bool
{
    return db_table_exists('app_settings');
}

function kirpi_settings_all(): array
{
    if (is_array($GLOBALS['kirpi_settings_cache'] ?? null)) {
        return $GLOBALS['kirpi_settings_cache'];
    }

    if (!kirpi_settings_table_ready()) {
        $GLOBALS['kirpi_settings_cache'] = [];
        return $GLOBALS['kirpi_settings_cache'];
    }

    try {
        $stmt = db()->query("
            SELECT setting_key, setting_value
            FROM app_settings
        ");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $settings = [];

        foreach ($rows as $row) {
            $key = trim((string) ($row['setting_key'] ?? ''));
            if ($key === '') {
                continue;
            }

            $settings[$key] = (string) ($row['setting_value'] ?? '');
        }

        $GLOBALS['kirpi_settings_cache'] = $settings;
    } catch (Throwable $e) {
        error_log('settings load error: ' . $e->getMessage());
        $GLOBALS['kirpi_settings_cache'] = [];
    }

    return $GLOBALS['kirpi_settings_cache'];
}

function kirpi_setting_get(string $key, mixed $default = null): mixed
{
    $key = trim($key);
    if ($key === '') {
        return $default;
    }

    $settings = kirpi_settings_all();
    if (!array_key_exists($key, $settings)) {
        return $default;
    }

    return $settings[$key];
}

function kirpi_setting_bool(string $key, bool $default = false): bool
{
    $value = kirpi_setting_get($key, null);

    if ($value === null) {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

function kirpi_setting_set(string $key, ?string $value, ?int $updatedBy = null, bool $isSecret = false): bool
{
    if (!kirpi_settings_table_ready()) {
        return false;
    }

    $key = trim($key);
    if ($key === '') {
        return false;
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO app_settings (
                setting_key,
                setting_value,
                is_secret,
                updated_by
            ) VALUES (
                :setting_key,
                :setting_value,
                :is_secret,
                :updated_by
            )
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                is_secret = VALUES(is_secret),
                updated_by = VALUES(updated_by)
        ");

        $stmt->execute([
            ':setting_key' => $key,
            ':setting_value' => $value ?? '',
            ':is_secret' => $isSecret ? 1 : 0,
            ':updated_by' => $updatedBy,
        ]);

        kirpi_settings_cache_reset();
        return true;
    } catch (Throwable $e) {
        error_log('settings save error: ' . $e->getMessage());
        return false;
    }
}

function kirpi_settings_cache_reset(): void
{
    $GLOBALS['kirpi_settings_cache'] = null;
}
