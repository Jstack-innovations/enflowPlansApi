<?php
ob_start();

header("Content-Type: text/html; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    http_response_code(500);
    echo "<h2>Server error: DB not found</h2>";
    exit();
}

require_once $file;

$token = $_GET['token'] ?? '';

if (!$token) {
    echo "<h2>Invalid or missing token</h2>";
    exit();
}

$stmt = $conn->prepare("SELECT id, phone FROM users WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows === 1) {

    $user = $result->fetch_assoc();
    $userId = $user['id'];
    $phone = $user['phone'];

    $update = $conn->prepare("UPDATE users SET status='active', verification_token=NULL WHERE id=?");
    $update->bind_param("i", $userId);
    $update->execute();

    $claim = $conn->prepare("UPDATE paid_orders SET user_id=? WHERE phone=? AND user_id IS NULL");
    $claim->bind_param("is", $userId, $phone);
    $claim->execute();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Verification Success | Artisan Grills</title>
      <link href="https://fonts.googleapis.com/css2?family=Sacramento&display=swap" rel="stylesheet">

      <style>
        * { margin:0; padding:0; box-sizing:border-box; }

        body {
          font-family: Arial, sans-serif;
          background: #fff8f0;
          display:flex;
          justify-content:center;
          align-items:center;
          height:100vh;
          color:#333;
        }

        .container {
          background:#fff;
          padding:40px;
          border-radius:20px;
          text-align:center;
          box-shadow:0 8px 25px rgba(0,0,0,0.2);
          width:90%;
          max-width:500px;
        }

        .header {
          font-family:'Sacramento', cursive;
          font-size:48px;
          color:#A0522D;
          margin-bottom:20px;
        }

        .message {
          font-size:18px;
          margin-bottom:20px;
        }

        .btn {
          display:inline-block;
          padding:12px 25px;
          background:#FF7043;
          color:#fff;
          border-radius:50px;
          text-decoration:none;
        }
      </style>
    </head>

    <body>
      <div class="container">
        <div class="header">Artisan Grills</div>

        <div class="message">
          Your email has been verified successfully!<br>
          Please login to continue.
        </div>

        <a class="btn" href="https://yourappurl.com/login">Open App</a>
      </div>
    </body>
    </html>

    <?php
    exit();

} else {
    echo "<h2>Invalid or expired token.</h2>";
    exit();
}            text-decoration: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-weight: bold;
            transition: background 0.3s ease;
        }
        .login-btn:hover { background-color: #E64A19; }

        /* Success checkmark animation */
        .checkmark {
          width: 100px;
          height: 100px;
          border-radius: 50%;
          display: flex;
          justify-content: center;
          align-items: center;
          margin: 0 auto 20px auto;
          background: #4CAF50;
          animation: pop 0.5s ease-out forwards;
        }
        .checkmark::after {
          content: "";
          width: 25px;
          height: 50px;
          border-left: 5px solid #fff;
          border-bottom: 5px solid #fff;
          transform: rotate(-45deg);
          animation: draw 0.5s ease-out forwards 0.5s;
        }
        @keyframes pop {
          0% { transform: scale(0); }
          100% { transform: scale(1); }
        }
        @keyframes draw {
          0% { height: 0; width: 0; opacity: 0; }
          100% { height: 50px; width: 25px; opacity: 1; }
        }

        /* Responsive */
        @media(max-width:400px){
          .header { font-size: 36px; }
          .message { font-size: 16px; }
          .login-btn { padding: 12px 25px; }
        }
      </style>
    </head>
    <body>
      <!-- Steam floating animations -->
      <div class="steam"></div>
      <div class="steam"></div>
      <div class="steam"></div>

      <div class="container">
        <div class="checkmark"></div>
        <div class="header">Artisan Grills</div>
        <div class="message">
          Your email has been verified successfully!<br>
          Please login on the app to view all your orders.
        </div>
        <a href="https://yourappurl.com/login" class="login-btn">Open App</a>
      </div>
    </body>
    </html>
    <?php

} else {
    echo "<h2>Invalid or expired token.</h2>";
}
?>
