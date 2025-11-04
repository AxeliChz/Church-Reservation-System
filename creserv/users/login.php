<?php
session_start();
require_once "../config/db.php";
require_once "../users/user.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $userObj = new User();
        $user = $userObj->login($email, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header("Location: ../calendar.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Login</title><link rel="stylesheet" href="../style/style.css"></head>
<body>
<div class="container">
<h1>Login</h1>
<p class="error"><?= htmlspecialchars($error) ?></p>
<form method="post">
<label>Email</label>
<input type="email" name="email" required>
<label>Password</label>
<input type="password" name="password" required>
<input type="submit" value="Login">
</form>
<p>Don't have an account? <a href="adduser.php">Sign Up</a></p>
</div>
</body>
</html>
