<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['admin_code']);
    if ($code === 'Churcev') {
        $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['is_admin'] = 1;
        $message = "You are now an admin.";
    } else {
        $message = "Invalid code.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Account Settings</title><link rel="stylesheet" href="../style/style.css"></head>
<body>
<div class="container">
<h1>Account Settings</h1>
<p><?= htmlspecialchars($message) ?></p>
<form method="post">
<label>Enter Admin Code:</label>
<input type="text" name="admin_code">
<input type="submit" value="Submit">
</form>
<a href="../calendar.php">Back</a>
</div>
</body>
</html>
