<?php

if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function kirpi_export_filename(string $filename, string $extension): string
{
    $base = strtolower(trim($filename));
    $base = preg_replace('/[^a-z0-9._-]+/', '-', $base) ?? '';
    $base = trim($base, '.-');

    if ($base === '') {
        $base = 'export';
    }

    return $base . '.' . ltrim(strtolower($extension), '.');
}

function kirpi_export_scalar(mixed $value): string
{
    if ($value === null) {
        return '';
    }

    if ($value instanceof DateTimeInterface) {
        return kirpi_format_datetime($value);
    }

    if (is_bool($value)) {
        return $value ? '1' : '0';
    }

    if (is_array($value) || is_object($value)) {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded !== false ? $encoded : '';
    }

    return (string) $value;
}

function kirpi_export_csv(string $filename, array $headers, iterable $rows): void
{
    $downloadName = kirpi_export_filename($filename, 'csv');

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'wb');
    fputcsv($output, array_map('kirpi_export_scalar', $headers), ';');

    foreach ($rows as $row) {
        fputcsv($output, array_map('kirpi_export_scalar', (array) $row), ';');
    }

    fclose($output);
    exit;
}

function kirpi_export_xls(string $filename, array $headers, iterable $rows): void
{
    $downloadName = kirpi_export_filename($filename, 'xls');

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    echo "\xEF\xBB\xBF";
    echo '<table><thead><tr>';
    foreach ($headers as $header) {
        echo '<th>' . e(kirpi_export_scalar($header)) . '</th>';
    }
    echo '</tr></thead><tbody>';

    foreach ($rows as $row) {
        echo '<tr>';
        foreach ((array) $row as $value) {
            echo '<td>' . e(kirpi_export_scalar($value)) . '</td>';
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
    exit;
}

function kirpi_export_response(string $format, string $filename, array $headers, iterable $rows): void
{
    $format = strtolower(trim($format));

    if ($format === 'xls' || $format === 'excel') {
        kirpi_export_xls($filename, $headers, $rows);
    }

    kirpi_export_csv($filename, $headers, $rows);
}
