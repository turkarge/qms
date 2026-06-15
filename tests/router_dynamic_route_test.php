<?php

$buildPattern = static function (string $routeKey): string {
    $pattern = '';
    $offset = 0;
    preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $routeKey, $placeholderMatches, PREG_OFFSET_CAPTURE);
    foreach (($placeholderMatches[0] ?? []) as $placeholderMatch) {
        $placeholder = (string) ($placeholderMatch[0] ?? '');
        $position = (int) ($placeholderMatch[1] ?? 0);
        $pattern .= preg_quote(substr($routeKey, $offset, $position - $offset), '#');
        $pattern .= '([^/]+)';
        $offset = $position + strlen($placeholder);
    }
    return $pattern . preg_quote(substr($routeKey, $offset), '#');
};

$cases = [
    ['documents/actions/download/{id}', 'documents/actions/download/42', ['42']],
    ['api/v1/users/{id}/status', 'api/v1/users/17/status', ['17']],
    ['example/{group}/{id}', 'example/core/99', ['core', '99']],
];

foreach ($cases as [$routeKey, $path, $expected]) {
    $pattern = $buildPattern($routeKey);
    if (preg_match('#^' . $pattern . '$#', $path, $matches) !== 1) {
        fwrite(STDERR, "Dynamic route did not match: {$routeKey}\n");
        exit(1);
    }
    array_shift($matches);
    if ($matches !== $expected) {
        fwrite(STDERR, "Dynamic route params mismatch: {$routeKey}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Dynamic route tests passed.\n");
