<?php

$dsn = sprintf(
    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
    DB_HOST,
    DB_PORT,
    DB_NAME,
    DB_CHARSET
);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('Veritabanı bağlantı hatası: ' . $e->getMessage());

    if (APP_DEBUG) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }

    http_response_code(500);
    exit('Veritabanı bağlantısı kurulamadı.');
}