<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }

require_once __DIR__ . '/../../SECURE/config.php';
require_once __DIR__ . '/../../SECURE/auth.php';

$user = authenticate($pdo);

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET auth_token        = NULL,
        auth_token_expiry = NULL
    WHERE id = :id
");
$stmt->execute([":id" => $user["id"]]);

echo json_encode(["status" => "ok", "message" => "Logged out successfully."]);
