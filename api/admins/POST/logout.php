<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";
// destroy session
session_unset();
session_destroy();

echo json_encode([
  "success" => true,
  "message" => "Logged out"
]);
