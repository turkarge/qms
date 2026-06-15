<?php
if (!defined('KIRPI_CORE_ENTRY')) {
    exit;
}

function documents_tables_ready(): bool
{
    return db_table_exists('documents') && db_table_exists('document_links');
}

function documents_allowed_mime_types(): array
{
    return [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
}

function documents_max_upload_size(): int
{
    return max(1, (int) env('DOCUMENTS_MAX_UPLOAD_MB', 10)) * 1024 * 1024;
}

function documents_sanitize_key(string $value, string $fallback = 'attachment'): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9._-]+/', '_', $value) ?? '';
    $value = trim($value, '._-');

    return $value !== '' ? mb_substr($value, 0, 80) : $fallback;
}

function documents_format_size(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}

function documents_normalize_uploads(array $files): array
{
    if (!isset($files['name'])) {
        return [];
    }

    if (!is_array($files['name'])) {
        return [$files];
    }

    $normalized = [];
    foreach ($files['name'] as $index => $name) {
        $normalized[] = [
            'name' => $name,
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $normalized;
}

function documents_file_presentation(string $mimeType, string $fileName = ''): array
{
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeType = strtolower(trim($mimeType));

    if (str_starts_with($mimeType, 'image/')) {
        return ['icon' => 'ti-photo', 'tone' => 'blue', 'label' => strtoupper($extension ?: 'IMG')];
    }
    if ($mimeType === 'application/pdf') {
        return ['icon' => 'ti-file-type-pdf', 'tone' => 'red', 'label' => 'PDF'];
    }
    if (in_array($extension, ['xls', 'xlsx', 'csv'], true)) {
        return ['icon' => 'ti-file-spreadsheet', 'tone' => 'green', 'label' => strtoupper($extension)];
    }
    if (in_array($extension, ['doc', 'docx'], true)) {
        return ['icon' => 'ti-file-type-docx', 'tone' => 'azure', 'label' => strtoupper($extension)];
    }
    if ($extension === 'txt' || $mimeType === 'text/plain') {
        return ['icon' => 'ti-file-text', 'tone' => 'secondary', 'label' => 'TXT'];
    }

    return ['icon' => 'ti-file', 'tone' => 'secondary', 'label' => strtoupper($extension ?: 'FILE')];
}

function document_store_upload(array $file, string $documentType = 'attachment'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'skipped' => true];
    }

    if (!documents_tables_ready()) {
        return ['success' => false, 'message' => 'Belge tabloları henüz kurulu değil.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Dosya yüklenemedi.'];
    }

    if ((int) ($file['size'] ?? 0) > documents_max_upload_size()) {
        return ['success' => false, 'message' => 'Dosya boyutu izin verilen sınırı aşıyor.'];
    }

    $allowed = documents_allowed_mime_types();
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) ($file['tmp_name'] ?? ''));
    if (!isset($allowed[$mime])) {
        return ['success' => false, 'message' => 'Desteklenmeyen dosya formatı.'];
    }

    $documentType = documents_sanitize_key($documentType);
    $relativeDir = 'documents/' . date('Y/m');
    $uploadDir = BASE_PATH . '/uploads/' . $relativeDir;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return ['success' => false, 'message' => 'Belge yükleme dizini oluşturulamadı.'];
    }

    if (!is_writable($uploadDir)) {
        return ['success' => false, 'message' => 'Belge yükleme dizini yazılabilir değil.'];
    }

    $storedName = $documentType . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $relativePath = $relativeDir . '/' . $storedName;
    $targetPath = $uploadDir . '/' . $storedName;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetPath)) {
        return ['success' => false, 'message' => 'Dosya sunucuya kaydedilemedi.'];
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO documents (
                document_type, original_name, stored_name, storage_path, mime_type, file_size, uploaded_by_user_id
            ) VALUES (
                :document_type, :original_name, :stored_name, :storage_path, :mime_type, :file_size, :uploaded_by_user_id
            )
        ");
        $stmt->execute([
            ':document_type' => $documentType,
            ':original_name' => (string) ($file['name'] ?? $storedName),
            ':stored_name' => $storedName,
            ':storage_path' => $relativePath,
            ':mime_type' => $mime,
            ':file_size' => (int) ($file['size'] ?? 0),
            ':uploaded_by_user_id' => (int) (current_user()['id'] ?? 0) ?: null,
        ]);

        return [
            'success' => true,
            'document_id' => (int) db()->lastInsertId(),
            'storage_path' => $relativePath,
            'file_name' => $storedName,
        ];
    } catch (Throwable $e) {
        if (is_file($targetPath)) {
            @unlink($targetPath);
        }

        error_log('document store upload error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Belge kaydı oluşturulamadı.'];
    }
}

function document_link_existing(int $documentId, string $entityType, int $entityId, string $relationType = 'attachment'): bool
{
    if (!documents_tables_ready() || $documentId <= 0 || $entityId <= 0) {
        return false;
    }

    $entityType = documents_sanitize_key($entityType, 'entity');
    $relationType = documents_sanitize_key($relationType, 'attachment');

    try {
        $stmt = db()->prepare("
            INSERT IGNORE INTO document_links (document_id, entity_type, entity_id, relation_type)
            VALUES (:document_id, :entity_type, :entity_id, :relation_type)
        ");
        $stmt->execute([
            ':document_id' => $documentId,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':relation_type' => $relationType,
        ]);

        return true;
    } catch (Throwable $e) {
        error_log('document link error: ' . $e->getMessage());
        return false;
    }
}

function document_upload_for_entity(array $file, string $documentType, string $entityType, int $entityId): array
{
    $result = document_store_upload($file, $documentType);
    if (!($result['success'] ?? false) || !empty($result['skipped'])) {
        return $result;
    }

    document_link_existing((int) ($result['document_id'] ?? 0), $entityType, $entityId, $documentType);

    return $result;
}

function documents_for_entity(string $entityType, int $entityId): array
{
    if (!documents_tables_ready() || $entityId <= 0) {
        return [];
    }

    try {
        $stmt = db()->prepare("
            SELECT d.*, dl.relation_type
            FROM document_links dl
            INNER JOIN documents d ON d.id = dl.document_id
            WHERE dl.entity_type = :entity_type AND dl.entity_id = :entity_id
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([
            ':entity_type' => documents_sanitize_key($entityType, 'entity'),
            ':entity_id' => $entityId,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('documents for entity error: ' . $e->getMessage());
        return [];
    }
}

function document_find(int $documentId): ?array
{
    if (!documents_tables_ready() || $documentId <= 0) {
        return null;
    }

    $stmt = db()->prepare("
        SELECT d.*, u.name AS uploaded_by_name
        FROM documents d
        LEFT JOIN users u ON u.id = d.uploaded_by_user_id
        WHERE d.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $documentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function document_storage_full_path(array $document): ?string
{
    $storagePath = str_replace('\\', '/', trim((string) ($document['storage_path'] ?? '')));
    if ($storagePath === '' || str_contains($storagePath, '..')) {
        return null;
    }

    $uploadsRoot = realpath(BASE_PATH . '/uploads');
    $fullPath = realpath(BASE_PATH . '/uploads/' . ltrim($storagePath, '/'));

    if ($uploadsRoot === false || $fullPath === false || !str_starts_with($fullPath, $uploadsRoot)) {
        return null;
    }

    return is_file($fullPath) ? $fullPath : null;
}

function document_delete(int $documentId): bool
{
    $document = document_find($documentId);
    if (!$document) {
        return false;
    }

    $fullPath = document_storage_full_path($document);

    db()->prepare('DELETE FROM documents WHERE id = :id LIMIT 1')->execute([':id' => $documentId]);

    if ($fullPath !== null && is_file($fullPath)) {
        @unlink($fullPath);
    }

    return true;
}
