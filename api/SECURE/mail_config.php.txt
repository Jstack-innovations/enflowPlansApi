<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

return [
    "host" => "smtp.gmail.com",
    "username" => "wsamson630@gmail.com",
    "password" => "xkhwvicgjfhvtyoi",

    // OLD (kept for reference)
    // "port" => 587,
    // "encryption" => "tls",

    // WORKING CONFIG (CURRENT FIX)
    "port" => 465,
    "encryption" => "ssl",

    "from_email" => "wsamson630@gmail.com",
    "from_name" => "Artisan Grills"
];
