<?php
function start_secure_session() {
    // Check if session is already active
    if (session_status() === PHP_SESSION_ACTIVE) {
        return; // Session already started, no need to set parameters or start again
    }
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',  // Auto-detects; set to '.cvpwfl.com' if you have subdomains
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}
?>