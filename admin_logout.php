<?php
session_name("admin_session");
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy session data on server
session_unset();
session_destroy();

// Delete the session cookie completely
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: admin_login.php");
exit();
