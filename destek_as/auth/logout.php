<?php
// C:\laragon\www\destek_as\auth\logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    logActivity($pdo, "Sistemden çıkış yapıldı", "users", $_SESSION['user_id']);
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: /destek_as/auth/login.php");
exit;
?>
