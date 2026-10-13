<?php
/**
 * Session Configuration
 * This file MUST be included BEFORE any session_start() calls
 * It configures PHP session settings and optional HTTPS enforcement.
 */

function requestIsHttps(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return getenv('FACULTY_TRUST_PROXY') === '1'
        && strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0])) === 'https';
}

if (getenv('FACULTY_FORCE_HTTPS') === '1' && !requestIsHttps()) {
    $serverName = (string)($_SERVER['SERVER_NAME'] ?? '');
    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
    if (!preg_match('/\A[a-zA-Z0-9.-]+\z/', $serverName) || str_contains($requestUri, "\r") || str_contains($requestUri, "\n")) {
        http_response_code(400);
        exit('Invalid request host.');
    }

    header('Location: https://' . $serverName . $requestUri, true, 308);
    exit;
}

// Only set these if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', '28800');
    ini_set('session.cookie_lifetime', '0');
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', requestIsHttps() ? 1 : 0);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.name', 'FACULTY_EVAL_SID');
}
