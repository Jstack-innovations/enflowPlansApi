<?php

// Allow JSON + CORS
$allowedOrigins = [
    "http://localhost:5173",
    "https://artisangrills-production.up.railway.app",
    "https://admin-artisangrilluxe.vercel.app"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$input = json_decode(file_get_contents("php://input"), true);

$id = $input["id"];
$full_name = $input["full_name"];
$email = $input["email"];
$phone = $input["phone"];
$status = $input["status"];

$stmt = $conn->prepare("
UPDATE users
SET full_name=?, email=?, phone=?, status=?
WHERE id=?
");

$stmt->bind_param("ssssi",$full_name,$email,$phone,$status,$id);

if($stmt->execute()){
echo json_encode(["success"=>true]);
}else{
echo json_encode(["success"=>false]);
}
