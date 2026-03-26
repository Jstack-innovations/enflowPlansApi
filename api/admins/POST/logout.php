<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

// destroy session
session_unset();
session_destroy();

// remove session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

echo json_encode([
  "success" => true,
  "message" => "Logged out"
]);
