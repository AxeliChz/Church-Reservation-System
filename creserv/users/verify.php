<?php
require_once "../config/db.php";
require_once "../includes/email_handler.php";

$message = "";
$messageType = "";
$verified = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $db = new Database();
    $conn = $db->connect();
    
    
    $stmt = $conn->prepare("SELECT id, username, email, is_verified, created_at FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        if ($user['is_verified'] == 1) {
            $message = "This email has already been verified. You can login now.";
            $messageType = "info";
            $verified = true;
        } else {
            
            $createdAt = strtotime($user['created_at']);
            $now = time();
            $hoursDiff = ($now - $createdAt) / 3600;
            
            if ($hoursDiff > 24) {
                $message = "Verification link has expired. Please request a new verification email.";
                $messageType = "error";
            } else {
                // Verify the user
                $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
                
                if ($updateStmt->execute([$user['id']])) {
                    // Send welcome email
                    sendWelcomeEmail($user['email'], $user['username']);
                    
                    $message = "Email verified successfully! Your account is now active.";
                    $messageType = "success";
                    $verified = true;
                } else {
                    $message = "Verification failed. Please try again or contact support.";
                    $messageType = "error";
                }
            }
        }
    } else {
        $message = "Invalid verification link. Please check your email or request a new verification link.";
        $messageType = "error";
    }
} else {
    $message = "No verification token provided.";
    $messageType = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification - Church Reservation System</title>
  <link rel="stylesheet" href="../style/style.css">
  <style>
    .verification-container {
      max-width: 600px;
      margin: 80px auto;
      text-align: center;
    }
    
    .verification-icon {
      font-size: 80px;
      margin: 30px 0;
    }
    
    .verification-icon.success { color: #4caf50; }
    .verification-icon.error { color: #f44336; }
    .verification-icon.info { color: #2196f3; }
    
    .verification-message {
      background: white;
      padding: 40px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(33, 150, 243, 0.15);
    }
    
    .verification-message h1 {
      margin-bottom: 20px;
      font-size: 28px;
    }
    
    .verification-message p {
      font-size: 18px;
      margin: 20px 0;
      line-height: 1.6;
    }
    
    .action-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-top: 30px;
      flex-wrap: wrap;
    }
    
    .info-box {
      background: #f5f9fc;
      padding: 20px;
      border-radius: 8px;
      margin-top: 30px;
      text-align: left;
    }
    
    .info-box ul {
      margin: 15px 0 0 20px;
    }
    
    .info-box li {
      margin: 8px 0;
    }
  </style>
</head>
<body>
<div class="container verification-container">
  <div class="verification-message">
    
    <?php if ($messageType === 'success'): ?>
      <div class="verification-icon success">✓</div>
      <h1>Email Verified!</h1>
      <p class="success"><?= htmlspecialchars($message) ?></p>
      
      <div class="info-box">
        <h3>🎉 Welcome to Church Reservation System!</h3>
        <p><strong>Your account is ready. You can now:</strong></p>
        <ul>
          <li>✓ Book church events and ceremonies</li>
          <li>✓ Check availability in real-time</li>
          <li>✓ Manage your reservations</li>
          <li>✓ Request changes to bookings</li>
          <li>✓ Receive notifications and updates</li>
        </ul>
      </div>
      
      <div class="action-buttons">
        <button onclick="window.location.href='login.php'" class="btn-primary btn-large">
          Login to Your Account
        </button>
      </div>
      
    <?php elseif ($messageType === 'info'): ?>
      <div class="verification-icon info">ℹ️</div>
      <h1>Already Verified</h1>
      <p class="success"><?= htmlspecialchars($message) ?></p>
      
      <div class="action-buttons">
        <button onclick="window.location.href='login.php'" class="btn-primary btn-large">
          Go to Login
        </button>
      </div>
      
    <?php else: ?>
      <div class="verification-icon error">✗</div>
      <h1>Verification Failed</h1>
      <p class="error"><?= htmlspecialchars($message) ?></p>
      
      <div class="info-box">
        <h3>Need Help?</h3>
        <p><strong>Here's what you can do:</strong></p>
        <ul>
          <li>Check if you clicked the correct link from your email</li>
          <li>Make sure the link hasn't expired (valid for 24 hours)</li>
          <li>Request a new verification email if needed</li>
          <li>Contact support if the problem persists</li>
        </ul>
      </div>
      
      <div class="action-buttons">
        <button onclick="window.location.href='resend_verification.php'" class="btn-primary">
          Resend Verification Email
        </button>
        <button onclick="window.location.href='../index.php'" class="btn-secondary">
          Back to Home
        </button>
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
