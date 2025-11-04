<?php
require_once "../config/db.php";
require_once "../users/user.php";

$user = ["email" => "", "username" => "", "password" => ""];
$errors = ["email" => "", "username" => "", "password" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user["email"] = trim($_POST["email"]);
    $user["username"] = trim($_POST["username"]);
    $user["password"] = trim($_POST["password"]);

    if (empty($user["email"])) $errors["email"] = "Email is required";
    elseif (!filter_var($user["email"], FILTER_VALIDATE_EMAIL)) $errors["email"] = "Invalid email format";
    if (empty($user["username"])) $errors["username"] = "Username is required";
    if (empty($user["password"])) $errors["password"] = "Password is required";
    elseif (strlen($user["password"]) < 6) $errors["password"] = "Password must be at least 6 characters";

    if (empty(array_filter($errors))) {
        $userObj = new User();
        $userObj->email = $user["email"];
        $userObj->username = $user["username"];
        $userObj->password = password_hash($user["password"], PASSWORD_DEFAULT);

        if ($userObj->addUser()) {
            header("Location: login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Sign Up</title><link rel="stylesheet" href="../style/style.css"></head>
<body>
<div class="container">
<h1>Sign Up</h1>
<form method="post">
<label>Email</label>
<input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
<p class="error"><?= $errors['email'] ?></p>

<label>Username</label>
<input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>">
<p class="error"><?= $errors['username'] ?></p>

<label>Password</label>
<input type="password" name="password">
<p class="error"><?= $errors['password'] ?></p>

<input type="submit" value="Create Account">
</form>
<p>Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
