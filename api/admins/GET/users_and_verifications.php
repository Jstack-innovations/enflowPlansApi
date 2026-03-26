<?php

require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

$data = [
"users" => [],
"verifications" => []
];

/* USERS */
$q = $conn->query("SELECT id,full_name,email,phone,status,created_at FROM users ORDER BY id DESC");

while($row = $q->fetch_assoc()){
$data["users"][] = $row;
}

/* VERIFICATIONS */
$q2 = $conn->query("
SELECT lv.*, u.full_name, u.email
FROM login_verifications lv
LEFT JOIN users u ON u.id = lv.user_id
ORDER BY lv.id DESC
");

while($row = $q2->fetch_assoc()){
$data["verifications"][] = $row;
}

echo json_encode($data);
