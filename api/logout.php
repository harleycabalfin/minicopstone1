<?php
require_once 'includes/db.php';

// Start session (required to destroy it)
session_start();

// Destroy all session data
$_SESSION = [];
session_unset();
session_destroy();

// Optional: Clear session cookie for extra safety
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page with logout success message
header("Location: login.php?logout=success");
exit();
?>
