<?php
require_once __DIR__ . '/session_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isJsonRequest(): bool
{
    return isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false
        || isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
}

function failUnauthorized(string $message = 'Unauthorized access', int $status = 401): never
{
    http_response_code($status);
    if (isJsonRequest() || !headers_sent()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $message, 'message' => $message]);
    } else {
        echo $message;
    }
    exit;
}

function requireRole(string ...$roles): void
{
    $role = (string)($_SESSION['role'] ?? '');
    if ($role === '' || !in_array($role, $roles, true)) {
        failUnauthorized('Unauthorized access', 403);
    }
}

function requireDashboardRole(string $role): void
{
    if (empty($_SESSION['id']) || (string)($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>403 Access Denied</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f4f6f8;color:#202a35;font:16px/1.5 Arial,sans-serif}.access-denied{text-align:center;padding:40px 24px}h1{margin:0;color:#c62828;font-size:64px;line-height:1}h2{margin:16px 0 8px;font-size:24px}p{margin:0;color:#596573}</style></head><body><main class="access-denied"><h1>403</h1><h2>Access Denied</h2><p>You don\'t have permission to access this page.</p></main></body></html>';
        exit;
    }
}

function requireMethod(string ...$methods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $methods, true)) {
        failUnauthorized('Method not allowed', 405);
    }
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        requireSameOrigin();
    }
}

function requireSameOrigin(): void
{
    $origin = trim((string)($_SERVER['HTTP_ORIGIN'] ?? ''));
    if ($origin !== '') {
        $expected = 'http' . ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
        if (!hash_equals($expected, rtrim($origin, '/'))) {
            failUnauthorized('Cross-origin request blocked', 403);
        }
        return;
    }
    $referer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
    if ($referer !== '' && parse_url($referer, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '')) {
        failUnauthorized('Cross-origin request blocked', 403);
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function requireCsrf(): void
{
    $provided = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
    if ($provided === '' || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), $provided)) {
        failUnauthorized('Invalid security token', 419);
    }
}

