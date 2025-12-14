<?php
session_start();
require_once "../config/db.php";
require_once "/user.php";

$error = "";
$warning = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $db = new Database();
        $conn = $db->connect();
        
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            
            if ($user['is_verified'] == 0) {
                $warning = "Please verify your email before logging in. <a href='resend_verification.php' style='color: #2196f3; font-weight: 600;'>Resend verification email</a>";
            } else {
              
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                header("Location: ../calendar.php");
                exit;
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Church Reservation System</title>
  <link rel="stylesheet" href="../style/style.css">
  <style>
    .warning-box {
      background: #fff3e0;
      border-left: 4px solid #ff9800;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      color: #e65100;
    }
    
    .warning-box a {
      color: #2196f3;
      font-weight: 600;
      text-decoration: underline;
    }
  </style>
</head>
<body>
<div class="container login-container">
  <div class="login-header">
    <h1>Welcome Back</h1>
    <p>Log in to manage your church reservations</p>
  </div>

  <?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  
  <?php if ($warning): ?>
    <div class="warning-box">
      ⚠️ <?= $warning ?>
    </div>
  <?php endif; ?>

  <div class="login-form">
    <form method="post">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="your.email@example.com" required>

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>

      <input type="submit" value="Login" class="btn-primary" style="width: 100%; margin-top: 20px;">
    </form>
  </div>

  <div style="text-align: center; margin-top: 20px;">
    <p>
      Don't have an account? <a href="adduser.php" style="font-weight: 600;">Sign Up</a>
    </p>
    <p style="margin-top: 10px;">
      <a href="resend_verification.php" style="color: #666;">Resend verification email</a>
    </p>
  </div>
</div>
</body>
</html>
