<?php
require_once "../config/db.php";
require_once "../includes/email_handler.php";

$message = "";
$messageType = "";
$emailSent = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $messageType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        $db = new Database();
        $conn = $db->connect();
        
       
        $stmt = $conn->prepare("SELECT id, username, email, is_verified, verification_token FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($user['is_verified'] == 1) {
                $message = "This email is already verified. You can login now.";
                $messageType = "info";
            } else {
                
                $newToken = generateVerificationToken();
                
              
                $updateStmt = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
                $updateStmt->execute([$newToken, $user['id']]);
                
               
                if (sendVerificationEmail($user['email'], $user['username'], $newToken)) {
                    $message = "Verification email sent! Please check your inbox.";
                    $messageType = "success";
                    $emailSent = true;
                } else {
                    $message = "Failed to send verification email. Please try again later.";
                    $messageType = "error";
                }
            }
        } else {
           
            $message = "If this email is registered, you will receive a verification email shortly.";
            $messageType = "info";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resend Verification - Church Reservation System</title>
  <link rel="stylesheet" href="../style/style.css">
  <style>
    .resend-container {
      max-width: 500px;
      margin: 80px auto;
    }
    
    .resend-header {
      text-align: center;
      margin-bottom: 30px;
    }
    
    .resend-header h1 {
      font-size: 28px;
      margin-bottom: 10px;
    }
    
    .email-icon {
      font-size: 60px;
      margin: 20px 0;
      text-align: center;
    }
    
    .info-notice {
      background: #e3f2fd;
      border-left: 4px solid #2196f3;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
    }
    
    .success-notice {
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(33, 150, 243, 0.15);
      text-align: center;
    }
  </style>
</head>
<body>
<div class="container resend-container">
  
  <?php if ($emailSent): ?>
   
    <div class="success-notice">
      <div class="email-icon">📧</div>
      <h2 style="color: #4caf50; margin-bottom: 20px;">Verification Email Sent!</h2>
      <p class="success"><?= htmlspecialchars($message) ?></p>
      
      <div class="info-notice" style="text-align: left; margin-top: 30px;">
        <h4>Next Steps:</h4>
        <ul style="margin: 15px 0 0 20px;">
          <li>Check your email inbox</li>
          <li>Look for an email from Church Reservation System</li>
          <li>Click the verification link in the email</li>
          <li>Complete verification within 24 hours</li>
        </ul>
        <p style="margin-top: 15px;"><small><strong>Note:</strong> Check your spam folder if you don't see the email.</small></p>
      </div>
      
      <div style="margin-top: 30px;">
        <button onclick="window.location.href='login.php'" class="btn-primary">
          Go to Login
        </button>
      </div>
    </div>
    
  <?php else: ?>
  
    <div class="resend-header">
      <div class="email-icon">📬</div>
      <h1>Resend Verification Email</h1>
      <p>Enter your email to receive a new verification link</p>
    </div>

    <?php if ($message && $messageType === 'info'): ?>
      <p class="success"><?= htmlspecialchars($message) ?></p>
    <?php elseif ($message && $messageType === 'error'): ?>
      <p class="error"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <div class="info-notice">
      <strong>ℹ️ Why resend?</strong>
      <ul style="margin: 10px 0 0 20px;">
        <li>Didn't receive the original email</li>
        <li>Verification link expired (24 hours)</li>
        <li>Email went to spam folder</li>
      </ul>
    </div>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(33, 150, 243, 0.15);">
      <form method="post">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="your.email@example.com" required style="width: 100%; margin-bottom: 20px;">
        
        <input type="submit" value="Resend Verification Email" class="btn-primary" style="width: 100%;">
      </form>
    </div>

    <p style="text-align: center; margin-top: 20px;">
      <a href="login.php">← Back to Login</a> | 
      <a href="../index.php">Home</a>
    </p>
  <?php endif; ?>
</div>
</body>
</html>
