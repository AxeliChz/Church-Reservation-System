<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['admin_code']);
    if ($code === 'Churcev') {
        $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['is_admin'] = 1;
        $message = "Success! You are now an admin. You can access the admin panel from the calendar.";
        $message_type = "success";
    } else {
        $message = "Invalid admin code. Please try again.";
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings</title>
  <link rel="stylesheet" href="../style/style.css">
</head>
<body>
<div class="container">
  <h1>Account Settings</h1>

  <?php if ($message): ?>
    <p class="<?= $message_type ?>">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>

  <div class="account-info">
    <h3>Your Information</h3>
    <div class="info-item">
      <span class="info-label">Username:</span>
      <span class="info-value"><?= htmlspecialchars($_SESSION['username']) ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Email:</span>
      <span class="info-value"><?= htmlspecialchars($_SESSION['email']) ?></span>
    </div>
    <div class="info-item">
      <span class="info-label">Account Type:</span>
      <span class="info-value">
        <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
          <span style="color: #e65100; font-weight: 600;">Administrator</span>
        <?php else: ?>
          <span style="color: #1976d2; font-weight: 600;">Regular User</span>
        <?php endif; ?>
      </span>
    </div>
  </div>

  <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1): ?>
  <div class="admin-section">
    <h3>Become an Administrator</h3>
    <p style="margin-bottom: 20px; color: #bf360c;">
      If you have an admin code, enter it below to gain administrative privileges.
    </p>
    <form method="post">
      <label>Enter Admin Code:</label>
      <input type="text" name="admin_code" placeholder="Enter the admin code" required>
      <input type="submit" value="Submit Code" class="btn-primary" style="margin-top: 15px;">
    </form>
  </div>
  <?php else: ?>
  <div class="admin-section">
    <h3>Administrator Access</h3>
    <p style="margin-bottom: 20px; color: #bf360c;">
      You have full administrative access. You can manage all events and change requests.
    </p>
    <button onclick="window.location.href='../admin/admin_dashboard.php'" class="btn-primary">
      Go to Admin Dashboard
    </button>
  </div>
  <?php endif; ?>

  <div style="margin-top: 30px;">
    <button onclick="window.location.href='../calendar.php'" class="btn-secondary">Back to Calendar</button>
  </div>
</div>
</body>
</html>