<?php
function authenticate($pdo) {
    $headers = getallheaders();
    $authHeader = trim($headers["Authorization"] ?? $headers["authorization"] ?? "");

    if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. No token provided."]);
        exit();
    }

    $token = trim(substr($authHeader, 7));

    $stmt = $pdo->prepare("
        SELECT id, fullname, email, phone, plan, status,
       trial_ends_at, renewal_date, business_name, business_type,
       logo_url, country, currency, subscription_code,
       zara_credits, zara_credits_used, auth_token_expiry,
       team_members, connected_tools, zara_brand_voice,
       zara_primary_lang, zara_also_speaks, zara_top_goals, zara_hours
FROM subscriptions
WHERE auth_token = :token
LIMIT 1
    ");
    $stmt->execute([":token" => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized. Invalid token."]);
        exit();
    }

    // Token expired
    if (strtotime($user["auth_token_expiry"]) < time()) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Session expired. Please log in again."]);
        exit();
    }

    // Suspended only
    if ($user["status"] === "suspended") {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Your account has been suspended. Please contact support."]);
        exit();
    }

    return $user;
}
