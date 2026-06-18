<?php
/**
 * Session Configuration
 * This file MUST be included BEFORE any session_start() calls
 * It configures PHP session settings for extended lifetime and security
 */

// Only set these if session is not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure PHP session to last longer (24 hours = 86400 seconds)
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.cache_expire', 1440); // 1440 minutes = 24 hours
    
    // Additional session security and reliability settings
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    
    // Optional: Set custom session name for better security
    ini_set('session.name', 'FACULTY_EVAL_SID');
}
?>
