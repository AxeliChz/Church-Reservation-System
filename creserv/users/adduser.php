<?php
require_once "../config/db.php";
require_once "../users/user.php";
require_once "../includes/email_handler.php";

$user = ["email" => "", "username" => "", "password" => ""];
$errors = ["email" => "", "username" => "", "password" => ""];
$success = false;
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user["email"] = trim($_POST["email"]);
    $user["username"] = trim($_POST["username"]);
    $user["password"] = trim($_POST["password"]);

    
    if (empty($user["email"])) {
        $errors["email"] = "Email is required";
    } elseif (!filter_var($user["email"], FILTER_VALIDATE_EMAIL)) {
        $errors["email"] = "Invalid email format";
    }
    
    if (empty($user["username"])) {
        $errors["username"] = "Username is required";
    } elseif (strlen($user["username"]) < 3) {
        $errors["username"] = "Username must be at least 3 characters";
    }
    
    if (empty($user["password"])) {
        $errors["password"] = "Password is required";
    } elseif (strlen($user["password"]) < 6) {
        $errors["password"] = "Password must be at least 6 characters";
    }

    if (empty(array_filter($errors))) {
        $db = new Database();
        $conn = $db->connect();
        
        
        $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $checkEmail->execute([$user["email"]]);
        
        if ($checkEmail->fetch()) {
            $errors["email"] = "Email already exists";
        } else {
           
            $verificationToken = generateVerificationToken();
            $hashedPassword = password_hash($user["password"], PASSWORD_DEFAULT);
            
            
            $stmt = $conn->prepare("INSERT INTO users (email, username, password, verification_token, is_verified) VALUES (?, ?, ?, ?, 0)");
            
            if ($stmt->execute([$user["email"], $user["username"], $hashedPassword, $verificationToken])) {
                
                if (sendVerificationEmail($user["email"], $user["username"], $verificationToken)) {
                    $success = true;
                    $successMessage = "Registration successful! Please check your email to verify your account.";
                } else {
                    $errors["email"] = "Registration successful but failed to send verification email. Please contact support.";
                }
            } else {
                $errors["email"] = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign Up - Church Reservation System</title>
  <link rel="stylesheet" href="../style/style.css">
  <style>
    .verification-notice {
      background: #e3f2fd;
      border-left: 4px solid #2196f3;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
    }
    
    .verification-notice h3 {
      color: #1565c0;
      margin-bottom: 10px;
    }
    
    .verification-notice ul {
      margin: 10px 0 10px 20px;
    }
    
    .verification-notice li {
      margin: 5px 0;
    }
    
    .email-icon {
      font-size: 48px;
      text-align: center;
      margin: 20px 0;
    }
  </style>
</head>
<body>
<div class="container signup-container">
  
  <?php if ($success): ?>
    <!-- Success Message -->
    <div class="verification-notice">
      <div class="email-icon">📧</div>
      <h3 style="text-align: center;">Check Your Email!</h3>
      <p style="text-align: center; margin-bottom: 20px;"><?= htmlspecialchars($successMessage) ?></p>
      
      <div style="background: white; padding: 20px; border-radius: 8px;">
        <h4>Next Steps:</h4>
        <ul>
          <li><strong>Check your inbox</strong> for an email from Church Reservation System</li>
          <li><strong>Click the verification link</strong> in the email</li>
          <li><strong>Complete verification</strong> within 24 hours</li>
          <li><strong>Login</strong> and start booking events!</li>
        </ul>
      </div>
      
      <p style="margin-top: 20px; text-align: center; color: #666;">
        <small>Didn't receive the email? Check your spam folder or contact support.</small>
      </p>
      
      <div style="text-align: center; margin-top: 20px;">
        <a href="login.php" class="btn-primary">Go to Login</a>
      </div>
    </div>
  <?php else: ?>
    <!-- Registration Form -->
    <div class="signup-header">
      <h1>Create Account</h1>
      <p>Join us to start booking church events</p>
    </div>

    <div class="signup-form">
      <form method="post">
        <div class="form-group-with-error">
          <label>Email Address *</label>
          <input type="email" name="email" placeholder="your.email@example.com" value="<?= htmlspecialchars($user['email']) ?>" required>
          <?php if ($errors['email']): ?>
            <p class="error"><?= $errors['email'] ?></p>
          <?php endif; ?>
          <small style="color: #666;">We'll send a verification email to this address</small>
        </div>

        <div class="form-group-with-error">
          <label>Username *</label>
          <input type="text" name="username" placeholder="Choose a username (min. 3 characters)" value="<?= htmlspecialchars($user['username']) ?>" required>
          <?php if ($errors['username']): ?>
            <p class="error"><?= $errors['username'] ?></p>
          <?php endif; ?>
        </div>

        <div class="form-group-with-error">
          <label>Password *</label>
          <input type="password" name="password" placeholder="At least 6 characters" required>
          <?php if ($errors['password']): ?>
            <p class="error"><?= $errors['password'] ?></p>
          <?php endif; ?>
        </div>

        <div style="background: #f5f9fc; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 14px;">
          <strong>📋 Registration Process:</strong>
          <ol style="margin: 10px 0 0 20px; padding: 0;">
            <li>Fill in your details and submit</li>
            <li>Check your email for verification link</li>
            <li>Click the link to verify your account</li>
            <li>Login and start using the system</li>
          </ol>
        </div>

        <input type="submit" value="Create Account" class="btn-primary" style="width: 100%; margin-top: 20px;">
      </form>
    </div>

    <p style="text-align: center; margin-top: 20px;">
      Already have an account? <a href="login.php" style="font-weight: 600;">Login</a>
    </p>
  <?php endif; ?>
</div>
</body>
</html>
