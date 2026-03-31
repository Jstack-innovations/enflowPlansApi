<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require __DIR__ . '/vendor/autoload.php';
use Twilio\Rest\Client;

// ===== Twilio credentials =====
$sid = "ACc9ad391bff43d4ce8463574671ab1be5";
$token = "9eed46d720ded0519127b7e28dcf38ea";
$fromWhatsapp = "whatsapp:+14155238886"; // your Twilio number
$toWhatsapp = "whatsapp:+2347089913116"; // recipient

$client = new Client($sid, $token);

// ===== Include your existing DB connection =====
$file = __DIR__ . '/../db.php';
if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}
require_once $file; // this should give you $conn or $pdo depending on your setup

try {
    // ===== Fetch today's analytics =====
    $stmt = $conn->prepare("SELECT SUM(revenue) as totalRevenue, SUM(cost) as totalCost FROM analytics WHERE DATE(date) = CURDATE()");
    $stmt->execute();
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $grossRevenue = $data['totalRevenue'] ?? 0;
    $estimatedCost = $data['totalCost'] ?? 0;
    $estimatedProfit = $grossRevenue - $estimatedCost;
    $profitMargin = $grossRevenue ? round(($estimatedProfit / $grossRevenue) * 100) : 0;

    // ===== Build WhatsApp message =====
    $message = "📊 Artisanè Grilluxxè Daily Business Report\n\n";
    $message .= "💰 Gross Revenue: ₦" . number_format($grossRevenue) . "\n";
    $message .= "📉 Estimated Cost: ₦" . number_format($estimatedCost) . "\n";
    $message .= "📈 Estimated Profit: ₦" . number_format($estimatedProfit) . "\n";
    $message .= "📊 Profit Margin: {$profitMargin}%\n\n";
    $message .= "Generated automatically.";

    // ===== Send WhatsApp message =====
    $client->messages->create(
        $toWhatsapp,
        [
            "from" => $fromWhatsapp,
            "body" => $message
        ]
    );

    echo "Report sent successfully!";

} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "Twilio Error: " . $e->getMessage();
}
