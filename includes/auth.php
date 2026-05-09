<?php
/**
 * SAKMS - Authentication & Session Management
 * Demo: Hardcoded Supervisor Login
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==========================================
// HARDCODED SUPERVISOR CREDENTIALS
// ==========================================
define('SUPERVISOR_EMAIL', 'supervisor@sakms.com');
define('SUPERVISOR_PASSWORD', 'supervisor123');

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['supervisor_logged_in']) && $_SESSION['supervisor_logged_in'] === true;
}

/**
 * Get current logged-in supervisor
 */
function getCurrentSupervisor() {
    return isset($_SESSION['supervisor_name']) ? $_SESSION['supervisor_name'] : null;
}

/**
 * Login supervisor (hardcoded validation)
 */
function loginSupervisor($email, $password) {
    // Validate credentials
    if ($email === SUPERVISOR_EMAIL && $password === SUPERVISOR_PASSWORD) {
        $_SESSION['supervisor_logged_in'] = true;
        $_SESSION['supervisor_email'] = $email;
        $_SESSION['supervisor_name'] = 'Emily Tan';
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

/**
 * Logout supervisor
 */
function logoutSupervisor() {
    session_unset();
    session_destroy();
}

/**
 * Require login (redirect if not authenticated)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Require logout (redirect if already authenticated)
 */
function requireLogout() {
    if (isLoggedIn()) {
        header('Location: index.php');
        exit();
    }
}

/**
 * Get session timeout (30 minutes of inactivity)
 */
function checkSessionTimeout($timeout = 1800) {
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $timeout)) {
        logoutSupervisor();
        return false;
    }
    // Refresh login time
    $_SESSION['login_time'] = time();
    return true;
}
