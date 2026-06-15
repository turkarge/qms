<?php

function kirpi_queue_table_ready(): bool
{
    return db_table_exists('jobs_queue');
}

function kirpi_queue_push(string $jobType, array $payload, string $queueName = 'default', ?string $availableAt = null, int $maxAttempts = 3): int
{
    if (!kirpi_queue_table_ready()) {
        throw new RuntimeException('Queue table is not ready.');
    }

    $jobType = trim($jobType);
    if ($jobType === '') {
        throw new InvalidArgumentException('Job type is required.');
    }

    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encodedPayload === false) {
        throw new RuntimeException('Job payload encode failed.');
    }

    $availableAt = $availableAt ?: date('Y-m-d H:i:s');
    $maxAttempts = max(1, min(20, $maxAttempts));

    $stmt = db()->prepare("\n        INSERT INTO jobs_queue (\n            queue_name,\n            job_type,\n            payload_json,\n            max_attempts,\n            status,\n            available_at\n        ) VALUES (\n            :queue_name,\n            :job_type,\n            :payload_json,\n            :max_attempts,\n            'queued',\n            :available_at\n        )\n    ");

    $stmt->execute([
        ':queue_name' => $queueName,
        ':job_type' => $jobType,
        ':payload_json' => $encodedPayload,
        ':max_attempts' => $maxAttempts,
        ':available_at' => $availableAt,
    ]);

    return (int) db()->lastInsertId();
}

function kirpi_queue_claim_next(string $queueName = 'default'): ?array
{
    if (!kirpi_queue_table_ready()) {
        return null;
    }

    try {
        db()->beginTransaction();

        $stmt = db()->prepare("\n            SELECT *\n            FROM jobs_queue\n            WHERE queue_name = :queue_name\n              AND status = 'queued'\n              AND available_at <= NOW()\n            ORDER BY id ASC\n            LIMIT 1\n            FOR UPDATE\n        ");
        $stmt->execute([
            ':queue_name' => $queueName,
        ]);

        $job = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$job) {
            db()->commit();
            return null;
        }

        $update = db()->prepare("\n            UPDATE jobs_queue\n            SET status = 'processing',\n                attempts = attempts + 1,\n                reserved_at = NOW(),\n                updated_at = NOW()\n            WHERE id = :id\n        ");
        $update->execute([
            ':id' => (int) $job['id'],
        ]);

        db()->commit();

        $job['attempts'] = (int) ($job['attempts'] ?? 0) + 1;
        return $job;
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        error_log('queue claim error: ' . $e->getMessage());
        return null;
    }
}

function kirpi_queue_mark_completed(int $jobId): void
{
    $stmt = db()->prepare("\n        UPDATE jobs_queue\n        SET status = 'completed',\n            finished_at = NOW(),\n            updated_at = NOW()\n        WHERE id = :id\n    ");
    $stmt->execute([
        ':id' => $jobId,
    ]);
}

function kirpi_queue_mark_failed(int $jobId, int $attempts, int $maxAttempts, string $errorMessage): void
{
    $status = $attempts >= $maxAttempts ? 'failed' : 'queued';

    $stmt = db()->prepare("\n        UPDATE jobs_queue\n        SET status = :status,\n            last_error = :last_error,\n            reserved_at = NULL,\n            available_at = CASE\n                WHEN :status = 'queued' THEN DATE_ADD(NOW(), INTERVAL 30 SECOND)\n                ELSE available_at\n            END,\n            updated_at = NOW()\n        WHERE id = :id\n    ");
    $stmt->execute([
        ':status' => $status,
        ':last_error' => mb_substr($errorMessage, 0, 1000),
        ':id' => $jobId,
    ]);
}

function kirpi_queue_decode_payload(array $job): array
{
    $payloadRaw = (string) ($job['payload_json'] ?? '');
    if ($payloadRaw === '') {
        return [];
    }

    $decoded = json_decode($payloadRaw, true);
    return is_array($decoded) ? $decoded : [];
}

function kirpi_queue_handle_job(array $job): void
{
    $jobType = (string) ($job['job_type'] ?? '');
    $payload = kirpi_queue_decode_payload($job);

    switch ($jobType) {
        case 'mail.send':
            $recipient = trim((string) ($payload['recipient_email'] ?? ''));
            $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
            $templateKey = trim((string) ($payload['template_key'] ?? ''));
            $templateVars = (array) ($payload['template_vars'] ?? []);

            if ($templateKey !== '') {
                $result = kirpi_send_templated_mail($recipient, $templateKey, $templateVars, $userId);
            } else {
                $subject = trim((string) ($payload['subject'] ?? ''));
                $body = (string) ($payload['body_html'] ?? '');
                $result = kirpi_send_mail($recipient, $subject, $body, $userId);
            }

            if (!($result['success'] ?? false)) {
                throw new RuntimeException((string) ($result['message'] ?? 'mail.send failed'));
            }
            return;

        default:
            throw new RuntimeException('Unsupported job type: ' . $jobType);
    }
}

function kirpi_queue_work_once(string $queueName = 'default'): array
{
    if (!kirpi_queue_table_ready()) {
        return [
            'status' => 'error',
            'message' => 'Queue table is not ready.',
        ];
    }

    $job = kirpi_queue_claim_next($queueName);
    if (!$job) {
        return [
            'status' => 'idle',
            'message' => 'No queued job found.',
        ];
    }

    $jobId = (int) ($job['id'] ?? 0);
    $attempts = (int) ($job['attempts'] ?? 1);
    $maxAttempts = (int) ($job['max_attempts'] ?? 3);

    try {
        kirpi_queue_handle_job($job);
        kirpi_queue_mark_completed($jobId);

        return [
            'status' => 'processed',
            'job_id' => $jobId,
            'job_type' => (string) ($job['job_type'] ?? ''),
            'message' => 'Job processed successfully.',
        ];
    } catch (Throwable $e) {
        kirpi_queue_mark_failed($jobId, $attempts, $maxAttempts, $e->getMessage());

        return [
            'status' => 'failed',
            'job_id' => $jobId,
            'job_type' => (string) ($job['job_type'] ?? ''),
            'message' => $e->getMessage(),
        ];
    }
}
