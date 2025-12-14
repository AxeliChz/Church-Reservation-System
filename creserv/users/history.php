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
$user_id = $_SESSION['user_id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $request = trim($_POST['requested_change']);
    
    if (!empty($request)) {
        $stmt = $conn->prepare("INSERT INTO change_requests (user_id, event_id, requested_change) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $event_id, $request]);
        $message = "Change request submitted successfully!";
    } else {
        $message = "Please describe the requested change.";
    }
}


if (isset($_GET['mark_read'])) {
    $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
}

$unread_count = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread_count->execute([$user_id]);
$unread = $unread_count->fetchColumn();


$notifs = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$notifs->execute([$user_id]);
$notifications = $notifs->fetchAll(PDO::FETCH_ASSOC);

$events = $conn->prepare("SELECT * FROM event WHERE user_id = ? ORDER BY event_date DESC");
$events->execute([$user_id]);
$list = $events->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My History</title>
  <link rel="stylesheet" href="../style/style.css">
  <style>
    .notifications-section {
      background: #e3f2fd;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 30px;
      border-left: 4px solid #2196f3;
    }
    
    .notification-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 15px;
    }
    
    .notification-item {
      background: white;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 10px;
      border-left: 4px solid #2196f3;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: all 0.3s ease;
    }
    
    .notification-item:hover {
      box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
      transform: translateX(5px);
    }
    
    .notification-item.success {
      border-left-color: #4caf50;
    }
    
    .notification-item.error {
      border-left-color: #f44336;
    }
    
    .notification-item.unread {
      background: #f5f9fc;
      font-weight: 600;
    }
    
    .notification-content {
      flex: 1;
    }
    
    .notification-time {
      font-size: 12px;
      color: #78909c;
      margin-left: 15px;
    }
    
    .notification-badge {
      background: #f44336;
      color: white;
      border-radius: 12px;
      padding: 2px 8px;
      font-size: 12px;
      font-weight: 700;
      margin-left: 10px;
    }
    
    .no-notifications {
      text-align: center;
      padding: 20px;
      color: #78909c;
    }
    
    .btn-mark-read {
      background: #2196f3;
      color: white;
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-mark-read:hover {
      background: #1976d2;
    }
  </style>
</head>
<body>
<div class="container">
  <h1>My Reservation History</h1>
  
  <?php if ($message): ?>
    <p class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>

 
  <div class="notifications-section">
    <div class="notification-header">
      <h2 style="margin: 0;">
        🔔 Notifications
        <?php if ($unread > 0): ?>
          <span class="notification-badge"><?= $unread ?></span>
        <?php endif; ?>
      </h2>
      <?php if ($unread > 0): ?>
        <button class="btn-mark-read" onclick="window.location.href='?mark_read=1'">
          Mark All as Read
        </button>
      <?php endif; ?>
    </div>
    
    <?php if (count($notifications) > 0): ?>
      <?php foreach ($notifications as $notif): ?>
        <div class="notification-item <?= $notif['type'] ?> <?= $notif['is_read'] == 0 ? 'unread' : '' ?>">
          <div class="notification-content">
            <?= htmlspecialchars($notif['message']) ?>
          </div>
          <div class="notification-time">
            <?= date('M d, Y g:i A', strtotime($notif['created_at'])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-notifications">
        No notifications yet.
      </div>
    <?php endif; ?>
  </div>

  <div class="info-box">
    <strong>Note:</strong> You can request changes to your reservations. An admin will review and approve your request.
  </div>

  <?php if (count($list) > 0): ?>
    <table class="table">
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Event</th>
          <th>Description</th>
          <th>Request Change</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $e): ?>
        <tr>
          <td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
          <td><?= date('g:i A', strtotime($e['start_time'])) ?></td>
          <td><strong><?= htmlspecialchars($e['event_name']) ?></strong></td>
          <td><?= htmlspecialchars($e['description'] ?: 'N/A') ?></td>
          <td>
            <form method="post" class="change-form">
              <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
              <textarea name="requested_change" placeholder="Describe your requested change..." rows="2" required></textarea>
              <input type="submit" value="Submit Request" class="btn-primary">
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <div class="no-events">
      <p>You don't have any reservations yet.</p>
      <button onclick="window.location.href='../reserve.php'" class="btn-primary" style="margin-top: 20px;">Make Your First Reservation</button>
    </div>
  <?php endif; ?>

  <div style="margin-top: 30px;">
    <button onclick="window.location.href='../calendar.php'" class="btn-secondary">Back to Calendar</button>
  </div>
</div>
</body>
</html>
