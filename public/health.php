<?php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

$url = trim((string)getenv('DATABASE_URL'));
$diagnostics = [
    'database_url_configured' => $url !== '',
    'database_url_scheme' => null,
];

if ($url !== '') {
    $parts = @parse_url($url);
    $diagnostics['database_url_scheme'] = $parts['scheme'] ?? null;
    $diagnostics['database_url_has_host'] = !empty($parts['host']);
    $diagnostics['database_url_has_database'] = !empty($parts['path']);
}

try {
    $pdo = db();
    $pdo->query('SELECT 1');
    $tables = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'")->fetchColumn();
    echo json_encode([
        'status' => 'ok',
        'database' => 'connected',
        'tables' => $tables,
        'diagnostics' => $diagnostics,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'database' => 'unavailable',
        'message' => $e->getMessage(),
        'diagnostics' => $diagnostics,
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
