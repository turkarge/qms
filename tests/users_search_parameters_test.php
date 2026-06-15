<?php

$files = [
    __DIR__ . '/../modules/users/actions/export.php',
    __DIR__ . '/../modules/api/v1/users/index.php',
];

$requiredParameters = [':search_name', ':search_email', ':search_role'];

foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "Could not read {$file}.\n");
        exit(1);
    }

    foreach ($requiredParameters as $parameter) {
        if (substr_count($source, $parameter) < 2) {
            fwrite(STDERR, "Missing unique search parameter {$parameter} in {$file}.\n");
            exit(1);
        }
    }

    if (preg_match('/LIKE\s+:search(?!_)/', $source) === 1) {
        fwrite(STDERR, "Repeated generic search parameter remains in {$file}.\n");
        exit(1);
    }
}

fwrite(STDOUT, "User search parameter tests passed.\n");
