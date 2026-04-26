<?php
session_start();
require_once 'db.php';
require_once 'log_activity.php';

$loggedUser = $_SESSION['admin_username'] ?? 'unknown';
$role       = $_SESSION['role'] ?? 'unknown';
log_activity($conn, 'Logged out', 'auth', "$loggedUser ($role)", 'secondary', 'sign-out-alt');

// Wipe session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: login.php');
exit();
