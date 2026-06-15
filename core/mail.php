<?php

function kirpi_mail_setting(string $key, string $fallback = ''): string
{
    if (function_exists('kirpi_setting_get')) {
        $value = trim((string) kirpi_setting_get($key, ''));
        if ($value !== '') {
            return $value;
        }
    }

    return trim($fallback);
}

function kirpi_mail_from_address(): string
{
    $from = kirpi_mail_setting('mail.from_address', (string) MAIL_FROM_ADDRESS);

    if ($from !== '') {
        return $from;
    }

    $username = kirpi_mail_setting('mail.username', (string) MAIL_USERNAME);
    if ($username !== '' && filter_var($username, FILTER_VALIDATE_EMAIL)) {
        return $username;
    }

    return 'no-reply@localhost';
}

function kirpi_mail_from_name(): string
{
    $name = kirpi_mail_setting('mail.from_name', (string) MAIL_FROM_NAME);
    return $name !== '' ? $name : APP_NAME;
}

function kirpi_mail_uses_smtp(): bool
{
    return kirpi_mail_setting('mail.host', (string) MAIL_HOST) !== '';
}

function kirpi_mail_config_status(): array
{
    $status = [
        'transport' => kirpi_mail_uses_smtp() ? 'smtp' : 'php_mail',
        'mail_host' => kirpi_mail_setting('mail.host', (string) MAIL_HOST) !== '',
        'mail_port' => (int) kirpi_mail_setting('mail.port', (string) MAIL_PORT) > 0,
        'mail_username' => kirpi_mail_setting('mail.username', (string) MAIL_USERNAME) !== '',
        'mail_password' => kirpi_mail_setting('mail.password', (string) MAIL_PASSWORD) !== '',
        'mail_from_address' => kirpi_mail_setting('mail.from_address', (string) MAIL_FROM_ADDRESS) !== '',
        'mail_from_name' => kirpi_mail_setting('mail.from_name', (string) MAIL_FROM_NAME) !== '',
        'mail_encryption' => in_array(strtolower(kirpi_mail_setting('mail.encryption', (string) MAIL_ENCRYPTION)), ['tls', 'ssl', 'none', ''], true),
    ];

    $status['ready'] = $status['transport'] === 'php_mail'
        ? $status['mail_from_address'] || trim((string) MAIL_USERNAME) !== ''
        : $status['mail_host'] && $status['mail_port'] && $status['mail_from_address'];

    return $status;
}

function kirpi_mail_log(array $payload): void
{
    if (!db_table_exists('mail_logs')) {
        return;
    }

    try {
        $stmt = db()->prepare("\n            INSERT INTO mail_logs (\n                user_id, recipient_email, subject, body_preview, transport, status, error_message\n            ) VALUES (\n                :user_id, :recipient_email, :subject, :body_preview, :transport, :status, :error_message\n            )\n        ");

        $stmt->execute([
            ':user_id' => $payload['user_id'] ?? null,
            ':recipient_email' => (string) ($payload['recipient_email'] ?? ''),
            ':subject' => (string) ($payload['subject'] ?? ''),
            ':body_preview' => (string) ($payload['body_preview'] ?? ''),
            ':transport' => (string) ($payload['transport'] ?? 'unknown'),
            ':status' => (string) ($payload['status'] ?? 'failed'),
            ':error_message' => $payload['error_message'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('mail log insert error: ' . $e->getMessage());
    }
}

function kirpi_smtp_read($socket): string
{
    $response = '';

    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }

        $response .= $line;

        if (strlen($line) < 4) {
            break;
        }

        if (preg_match('/^[0-9]{3} /', $line) === 1) {
            break;
        }
    }

    return $response;
}

function kirpi_smtp_command($socket, string $command, array $expectedCodes): array
{
    fwrite($socket, $command . "\r\n");
    $response = kirpi_smtp_read($socket);

    $code = 0;
    if (preg_match('/^([0-9]{3})/m', $response, $matches) === 1) {
        $code = (int) $matches[1];
    }

    return [
        'ok' => in_array($code, $expectedCodes, true),
        'code' => $code,
        'response' => trim($response),
    ];
}

function kirpi_smtp_send_mail(string $to, string $subject, string $htmlBody): array
{
    $host = kirpi_mail_setting('mail.host', (string) MAIL_HOST);
    $port = (int) kirpi_mail_setting('mail.port', (string) MAIL_PORT);
    $username = kirpi_mail_setting('mail.username', (string) MAIL_USERNAME);
    $password = kirpi_mail_setting('mail.password', (string) MAIL_PASSWORD);
    $encryption = strtolower(kirpi_mail_setting('mail.encryption', (string) MAIL_ENCRYPTION));
    $fromAddress = kirpi_mail_from_address();
    $fromName = kirpi_mail_from_name();

    if ($host === '' || $port <= 0) {
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'SMTP ayarları eksik: MAIL_HOST veya MAIL_PORT.',
        ];
    }

    if ($encryption === '' || $encryption === 'none') {
        $remote = "tcp://{$host}:{$port}";
    } elseif ($encryption === 'ssl') {
        $remote = "ssl://{$host}:{$port}";
    } else {
        $remote = "tcp://{$host}:{$port}";
    }

    $errno = 0;
    $errstr = '';
    $socket = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);

    if (!$socket) {
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'SMTP baglantisi kurulamadi: ' . ($errstr !== '' ? $errstr : 'unknown error'),
        ];
    }

    stream_set_timeout($socket, 15);

    $greeting = kirpi_smtp_read($socket);
    if (strpos($greeting, '220') !== 0) {
        fclose($socket);
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'SMTP acilis yaniti gecersiz: ' . trim($greeting),
        ];
    }

    $localHost = parse_url(BASE_URL, PHP_URL_HOST) ?: 'localhost';
    $ehlo = kirpi_smtp_command($socket, 'EHLO ' . $localHost, [250]);
    if (!$ehlo['ok']) {
        fclose($socket);
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'EHLO başarısız: ' . $ehlo['response'],
        ];
    }

    if ($encryption === 'tls') {
        $startTls = kirpi_smtp_command($socket, 'STARTTLS', [220]);
        if (!$startTls['ok']) {
            fclose($socket);
            return [
                'success' => false,
                'transport' => 'smtp',
                'error' => 'STARTTLS başarısız: ' . $startTls['response'],
            ];
        }

        $cryptoOk = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoOk !== true) {
            fclose($socket);
            return [
                'success' => false,
                'transport' => 'smtp',
                'error' => 'TLS sifreli kanal baslatilamadi.',
            ];
        }

        $ehloAfterTls = kirpi_smtp_command($socket, 'EHLO ' . $localHost, [250]);
        if (!$ehloAfterTls['ok']) {
            fclose($socket);
            return [
                'success' => false,
                'transport' => 'smtp',
                'error' => 'TLS sonrası EHLO başarısız: ' . $ehloAfterTls['response'],
            ];
        }
    }

    if ($username !== '') {
        $auth = kirpi_smtp_command($socket, 'AUTH LOGIN', [334]);
        if (!$auth['ok']) {
            fclose($socket);
            return [
                'success' => false,
                'transport' => 'smtp',
                'error' => 'SMTP AUTH LOGIN başarısız: ' . $auth['response'],
            ];
        }

        $userResp = kirpi_smtp_command($socket, base64_encode($username), [334]);
        if (!$userResp['ok']) {
            fclose($socket);
            return [
                'success' => false,
                'transport' => 'smtp',
                'error' => 'SMTP kullanıcı doğrulaması başarısız: ' . $userResp['response'],
            ];
        }

        $passResp = kirpi_smtp_command($socket, base64_encode($password), [235]);
        if (!$passResp['ok']) {
            fclose($socket);
            return [
                'success' => false,
                'transport' => 'smtp',
                'error' => 'SMTP parola doğrulaması başarısız: ' . $passResp['response'],
            ];
        }
    }

    $mailFrom = kirpi_smtp_command($socket, 'MAIL FROM:<' . $fromAddress . '>', [250]);
    if (!$mailFrom['ok']) {
        fclose($socket);
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'MAIL FROM başarısız: ' . $mailFrom['response'],
        ];
    }

    $rcptTo = kirpi_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    if (!$rcptTo['ok']) {
        fclose($socket);
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'RCPT TO başarısız: ' . $rcptTo['response'],
        ];
    }

    $dataCommand = kirpi_smtp_command($socket, 'DATA', [354]);
    if (!$dataCommand['ok']) {
        fclose($socket);
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'DATA komutu başarısız: ' . $dataCommand['response'],
        ];
    }

    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [];
    $headers[] = 'Date: ' . date('r');
    $headers[] = 'From: ' . kirpi_mail_header_name($fromName) . ' <' . $fromAddress . '>';
    $headers[] = 'To: <' . $to . '>';
    $headers[] = 'Subject: ' . $encodedSubject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';

    $body = str_replace(["\r\n", "\r"], "\n", $htmlBody);
    $body = preg_replace('/^\./m', '..', $body ?? '');

    $messageData = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.";
    fwrite($socket, $messageData . "\r\n");

    $dataResponse = kirpi_smtp_read($socket);
    if (strpos($dataResponse, '250') !== 0) {
        fclose($socket);
        return [
            'success' => false,
            'transport' => 'smtp',
            'error' => 'Mesaj teslimi başarısız: ' . trim($dataResponse),
        ];
    }

    kirpi_smtp_command($socket, 'QUIT', [221, 250]);
    fclose($socket);

    return [
        'success' => true,
        'transport' => 'smtp',
    ];
}

function kirpi_mail_header_name(string $name): string
{
    $trimmed = trim($name);
    if ($trimmed === '') {
        return APP_NAME;
    }

    if (preg_match('/[^\x20-\x7E]/', $trimmed) === 1) {
        return '=?UTF-8?B?' . base64_encode($trimmed) . '?=';
    }

    return str_replace(['\r', '\n'], '', $trimmed);
}

function kirpi_php_mail_send(string $to, string $subject, string $htmlBody): array
{
    $fromAddress = kirpi_mail_from_address();
    $fromName = kirpi_mail_header_name(kirpi_mail_from_name());

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $fromName . ' <' . $fromAddress . '>';

    $ok = @mail($to, $subject, $htmlBody, implode("\r\n", $headers));

    if (!$ok) {
        return [
            'success' => false,
            'transport' => 'php_mail',
            'error' => 'PHP mail() gönderimi başarısız oldu.',
        ];
    }

    return [
        'success' => true,
        'transport' => 'php_mail',
    ];
}

function kirpi_send_mail(string $to, string $subject, string $htmlBody, ?int $userId = null): array
{
    $to = trim($to);
    $subject = trim($subject);

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [
            'success' => false,
            'message' => 'Gecerli bir alici e-posta adresi girin.',
            'transport' => 'none',
        ];
    }

    if ($subject === '') {
        return [
            'success' => false,
            'message' => 'Konu bos olamaz.',
            'transport' => 'none',
        ];
    }

    $result = kirpi_mail_uses_smtp()
        ? kirpi_smtp_send_mail($to, $subject, $htmlBody)
        : kirpi_php_mail_send($to, $subject, $htmlBody);

    kirpi_mail_log([
        'user_id' => $userId,
        'recipient_email' => $to,
        'subject' => $subject,
        'body_preview' => mb_substr(strip_tags($htmlBody), 0, 500),
        'transport' => $result['transport'] ?? 'unknown',
        'status' => ($result['success'] ?? false) ? 'sent' : 'failed',
        'error_message' => $result['error'] ?? null,
    ]);

    if (!($result['success'] ?? false)) {
        return [
            'success' => false,
            'message' => (string) ($result['error'] ?? 'Mail gonderilemedi.'),
            'transport' => (string) ($result['transport'] ?? 'unknown'),
        ];
    }

    return [
        'success' => true,
        'message' => 'Test e-postası başarıyla gönderildi.',
        'transport' => (string) ($result['transport'] ?? 'unknown'),
    ];
}

function kirpi_mail_templates_table_ready(): bool
{
    return db_table_exists('mail_templates');
}

function kirpi_mail_default_templates(): array
{
    return [
        'auth.password_reset' => [
            'name' => 'Auth - Password Reset',
            'subject' => '{{app_name}} - Şifre Sıfırlama',
            'html_body' => '<p>Merhaba {{user_name}},</p><p>Şifrenizi sıfırlamak için aşağıdaki bağlantıyı kullanın:</p><p><a href="{{reset_link}}">{{reset_link}}</a></p><p>Bu bağlantı {{expires_minutes}} dakika geçerlidir.</p>',
            'is_active' => 1,
            'is_system' => 1,
        ],
        'queue.test_mail' => [
            'name' => 'Queue - Test Mail',
            'subject' => '{{app_name}} Queue Test',
            'html_body' => '<p>Merhaba {{user_name}},</p><p>Bu e-posta kuyruk (queue) sistemi üzerinden gönderildi.</p><p>Tarih: {{sent_at}}</p>',
            'is_active' => 1,
            'is_system' => 1,
        ],
        'mail.test_manual' => [
            'name' => 'Mail - Manual Test',
            'subject' => '{{app_name}} Test Maili',
            'html_body' => '<p>{{{message_html}}}</p>',
            'is_active' => 1,
            'is_system' => 1,
        ],
        'users.session_dropped' => [
            'name' => 'Users - Session Dropped',
            'subject' => '{{app_name}} - Oturum Sonlandırıldı',
            'html_body' => '<p>Merhaba {{user_name}},</p><p>Oturumlarınız bir yönetici tarafından sonlandırıldı.</p><p>Lütfen yeniden giriş yapın.</p>',
            'is_active' => 1,
            'is_system' => 1,
        ],
        'users.lock_key_reset' => [
            'name' => 'Users - Lock Key Reset',
            'subject' => '{{app_name}} - Kilit Key Sıfırlandı',
            'html_body' => '<p>Merhaba {{user_name}},</p><p>Oturum kilitleme key bilginiz yönetici tarafından sıfırlandı.</p><p>Profil ekranından yeni key oluşturabilirsiniz.</p>',
            'is_active' => 1,
            'is_system' => 1,
        ],
    ];
}

function kirpi_mail_sync_system_templates(): void
{
    if (function_exists('kirpi_template_sync_mail_defaults')) {
        kirpi_template_sync_mail_defaults(kirpi_mail_default_templates());
    }

    if (!kirpi_mail_templates_table_ready()) {
        return;
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO mail_templates (template_key, name, subject, html_body, is_active, is_system)
            VALUES (:template_key, :name, :subject, :html_body, :is_active, :is_system)
            ON DUPLICATE KEY UPDATE
                is_system = VALUES(is_system),
                updated_at = updated_at
        ");

        foreach (kirpi_mail_default_templates() as $templateKey => $template) {
            $stmt->execute([
                ':template_key' => (string) $templateKey,
                ':name' => (string) ($template['name'] ?? $templateKey),
                ':subject' => (string) ($template['subject'] ?? ''),
                ':html_body' => (string) ($template['html_body'] ?? ''),
                ':is_active' => (int) ($template['is_active'] ?? 1),
                ':is_system' => (int) ($template['is_system'] ?? 1),
            ]);
        }
    } catch (Throwable $e) {
        error_log('mail system templates sync error: ' . $e->getMessage());
    }
}

function kirpi_mail_get_template(string $templateKey, bool $mustBeActive = true): ?array
{
    $templateKey = trim($templateKey);
    if ($templateKey === '') {
        return null;
    }

    if (function_exists('kirpi_template_find_mail_template')) {
        $template = kirpi_template_find_mail_template($templateKey, $mustBeActive);
        if ($template) {
            return $template;
        }
    }

    if (kirpi_mail_templates_table_ready()) {
        kirpi_mail_sync_system_templates();

        try {
            $sql = "
                SELECT id, template_key, name, subject, html_body, is_active, is_system, updated_at
                FROM mail_templates
                WHERE template_key = :template_key
            ";
            if ($mustBeActive) {
                $sql .= " AND is_active = 1";
            }
            $sql .= " LIMIT 1";

            $stmt = db()->prepare($sql);
            $stmt->execute([
                ':template_key' => $templateKey,
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (Throwable $e) {
            error_log('mail template read error: ' . $e->getMessage());
        }
    }

    $defaults = kirpi_mail_default_templates();
    if (!isset($defaults[$templateKey])) {
        return null;
    }

    $default = $defaults[$templateKey];
    if ($mustBeActive && (int) ($default['is_active'] ?? 1) !== 1) {
        return null;
    }

    return [
        'id' => null,
        'template_key' => $templateKey,
        'name' => (string) ($default['name'] ?? $templateKey),
        'subject' => (string) ($default['subject'] ?? ''),
        'html_body' => (string) ($default['html_body'] ?? ''),
        'is_active' => (int) ($default['is_active'] ?? 1),
        'is_system' => (int) ($default['is_system'] ?? 1),
        'updated_at' => null,
    ];
}

function kirpi_mail_render_placeholders(string $content, array $variables): string
{
    if ($content === '') {
        return '';
    }

    $replaceEscapedMap = [];
    $replaceRawMap = [];
    foreach ($variables as $key => $value) {
        $varKey = trim((string) $key);
        if ($varKey === '') {
            continue;
        }

        if (is_scalar($value) || $value === null) {
            $stringValue = (string) ($value ?? '');
            $replaceEscapedMap['{{' . $varKey . '}}'] = htmlspecialchars($stringValue, ENT_QUOTES, 'UTF-8');
            $replaceRawMap['{{{' . $varKey . '}}}'] = $stringValue;
        }
    }

    if (!empty($replaceRawMap)) {
        $content = strtr($content, $replaceRawMap);
    }

    return strtr($content, $replaceEscapedMap);
}

function kirpi_mail_extract_placeholders(string $content): array
{
    if ($content === '') {
        return [];
    }

    if (preg_match_all('/\{\{\{?\s*([a-zA-Z0-9_.-]+)\s*\}?\}\}/', $content, $matches) !== 1) {
        return [];
    }

    $keys = array_map(static fn($item): string => (string) $item, $matches[1] ?? []);
    $keys = array_values(array_unique($keys));
    sort($keys);

    return $keys;
}

function kirpi_send_templated_mail(
    string $to,
    string $templateKey,
    array $variables = [],
    ?int $userId = null,
    ?string $subjectOverride = null
): array {
    $template = kirpi_mail_get_template($templateKey, true);
    if (!$template) {
        return [
            'success' => false,
            'message' => 'Mail şablonu bulunamadı veya pasif.',
            'transport' => 'none',
        ];
    }

    $defaultVars = [
        'app_name' => app_name(),
        'app_url' => BASE_URL,
        'year' => date('Y'),
    ];
    $allVars = array_merge($defaultVars, $variables);

    $subjectTemplate = trim((string) ($template['subject'] ?? ''));
    $subject = trim((string) ($subjectOverride ?? ''));
    if ($subject === '') {
        $subject = kirpi_mail_render_placeholders($subjectTemplate, $allVars);
    }

    $htmlTemplate = (string) ($template['html_body'] ?? '');
    $htmlBody = kirpi_mail_render_placeholders($htmlTemplate, $allVars);

    return kirpi_send_mail($to, $subject, $htmlBody, $userId);
}
