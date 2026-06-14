<?php

header('Content-Type: application/json');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/');

$basePath = __DIR__ . "/api";

/* API ROOT */
if ($uri === '' || $uri === '/') {
    echo json_encode(["message" => "API running"]);
    exit;
}

/* SUBSCRIPTION PLAN ROUTES */
/* SECURE ROUTES */
if ($uri === "/flutterwave") {
    require $basePath . "/SECURE/flutterwave-key.php";
    exit;
}

if ($uri === "/sendTelegramReport") {
    require $basePath . "/SECURE/sendTelegramReport.php";
    exit;
}

/*GET*/
if ($uri === "/subscriptionContent") {
    require $basePath . "/plans/GET/CORS/jsonplans.php";
    exit;
}

if ($uri === "/settings") {
    require $basePath . "/plans/GET/CORS/settings.php";
    exit;
}

if ($uri === "/accountStatus") {
    require $basePath . "/plans/GET/CORS/accountStatus.php";
    exit;
}


/*POST*/
if ($uri === "/verifyAccess") {
    require $basePath . "/plans/POST/verifyAccess.php";
    exit;
}

if ($uri === "/deductCredits") {
    require $basePath . "/plans/POST/deductCredits.php";
    exit;
}

if ($uri === "/subPlans") {
    require $basePath . "/plans/POST/subPlans.php";
    exit;
}
if ($uri === "/trialSignup") {
    require $basePath . "/plans/POST/trial-signup.php";
    exit;
}
if ($uri === "/onboarding") {
    require $basePath . "/plans/POST/onboarding-validate.php";
    exit;
}
if ($uri === "/onboardingSetPassword") {
    require $basePath . "/plans/POST/onboarding-set-password.php";
    exit;
}
if ($uri === "/onboardingVerifyOtp") {
    require $basePath . "/plans/POST/onboarding-verify-otp.php";
    exit;
}
if ($uri === "/zaraTopup") {
    require $basePath . "/plans/POST/zara-topup.php";
    exit;
}

/*PUT*/
if ($uri === "/countdown") {
    require $basePath . "/plans/PUT/countdown.php";
    exit;
}




/* 404 */
http_response_code(404);
echo json_encode(["error" => "Route not found"]);
