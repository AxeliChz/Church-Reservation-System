<?php
session_start();
require_once "../config/db.php";
require_once "../includes/email_handler.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../calendar.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$message = "";
$message_type = "";


if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM event WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Event deleted successfully!";
    $message_type = "success";
}


if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    
   
    $reqData = $conn->prepare("SELECT cr.*, u.email, u.username, e.event_name, e.event_date, e.start_time 
                               FROM change_requests cr 
                               JOIN users u ON cr.user_id = u.id 
                               JOIN event e ON cr.event_id = e.id 
                               WHERE cr.id = ?");
    $reqData->execute([$id]);
    $reqInfo = $reqData->fetch(PDO::FETCH_ASSOC);
    
    
    $conn->prepare("UPDATE change_requests SET status='approved' WHERE id=?")->execute([$id]);
    
    
    $notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'success')");
    $notif->execute([$reqInfo['user_id'], "Your change request for '{$reqInfo['event_name']}' has been approved!"]);
    
    
    sendChangeRequestNotification($reqInfo['email'], $reqInfo['username'], $reqInfo['event_name'], 'approved');
    
    $message = "Change request approved! User has been notified via email and notification.";
    $message_type = "success";
}


if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    
   
    $reqData = $conn->prepare("SELECT cr.*, u.email, u.username, e.event_name, e.event_date, e.start_time 
                               FROM change_requests cr 
                               JOIN users u ON cr.user_id = u.id 
                               JOIN event e ON cr.event_id = e.id 
                               WHERE cr.id = ?");
    $reqData->execute([$id]);
    $reqInfo = $reqData->fetch(PDO::FETCH_ASSOC);
    
    
    $conn->prepare("UPDATE change_requests SET status='rejected' WHERE id=?")->execute([$id]);
    
    
    $notif = $conn->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'error')");
    $notif->execute([$reqInfo['user_id'], "Your change request for '{$reqInfo['event_name']}' has been rejected."]);
    
    
    sendChangeRequestNotification($reqInfo['email'], $reqInfo['username'], $reqInfo['event_name'], 'rejected');
    
    $message = "Change request rejected! User has been notified via email and notification.";
    $message_type = "success";
}

// Calculate KPIs
$total_events = $conn->query("SELECT COUNT(*) FROM event")->fetchColumn();
$upcoming_events = $conn->query("SELECT COUNT(*) FROM event WHERE event_date >= CURDATE()")->fetchColumn();
$past_events = $total_events - $upcoming_events;

$pending_requests = $conn->query("SELECT COUNT(*) FROM change_requests WHERE status='pending'")->fetchColumn();
$approved_requests = $conn->query("SELECT COUNT(*) FROM change_requests WHERE status='approved'")->fetchColumn();
$rejected_requests = $conn->query("SELECT COUNT(*) FROM change_requests WHERE status='rejected'")->fetchColumn();
$total_requests = $pending_requests + $approved_requests + $rejected_requests;

$total_users = $conn->query("SELECT COUNT(*) FROM users WHERE is_verified = 1")->fetchColumn();
$unverified_users = $conn->query("SELECT COUNT(*) FROM users WHERE is_verified = 0")->fetchColumn();


$events = $conn->query("SELECT e.*, u.username, u.email FROM event e LEFT JOIN users u ON e.user_id = u.id ORDER BY e.event_date")->fetchAll(PDO::FETCH_ASSOC);


$requests = $conn->query("SELECT cr.*, u.username, u.email, e.event_name, e.event_date 
FROM change_requests cr 
JOIN users u ON cr.user_id = u.id 
JOIN event e ON cr.event_id = e.id 
ORDER BY cr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../style/style.css">
  <style>
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 15px;
      margin-bottom: 30px;
    }
    
    .kpi-card {
      background: white;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      border: 2px solid #e3f2fd;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
    }
    
    .kpi-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    
    .kpi-card.events {
      border-color: #2196f3;
      background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    }
    
    .kpi-card.pending {
      border-color: #ff9800;
      background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
    }
    
    .kpi-card.approved {
      border-color: #4caf50;
      background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    }
    
    .kpi-card.rejected {
      border-color: #f44336;
      background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%);
    }
    
    .kpi-card.users {
      border-color: #9c27b0;
      background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
    }
    
    .kpi-number {
      font-size: 36px;
      font-weight: 700;
      margin: 10px 0;
    }
    
    .kpi-card.events .kpi-number { color: #1565c0; }
    .kpi-card.pending .kpi-number { color: #e65100; }
    .kpi-card.approved .kpi-number { color: #2e7d32; }
    .kpi-card.rejected .kpi-number { color: #c62828; }
    .kpi-card.users .kpi-number { color: #6a1b9a; }
    
    .kpi-label {
      font-size: 14px;
      font-weight: 600;
      color: #546e7a;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .kpi-sublabel {
      font-size: 12px;
      color: #78909c;
      margin-top: 5px;
    }
    
    .action-buttons {
      display: flex;
      gap: 10px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    
    .btn-download {
      background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
      color: white;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-download:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }
    
    .notification-badge {
      position: relative;
      display: inline-block;
      margin-left: 10px;
    }
    
    .notification-badge .badge {
      position: absolute;
      top: -8px;
      right: -8px;
      background: #f44336;
      color: white;
      border-radius: 50%;
      padding: 2px 6px;
      font-size: 11px;
      font-weight: 700;
    }
    
    .email-indicator {
      display: inline-block;
      padding: 2px 8px;
      background: #e3f2fd;
      color: #1565c0;
      border-radius: 4px;
      font-size: 11px;
      font-weight: 600;
      margin-left: 5px;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="admin-header">
    <h1>Admin Dashboard</h1>
    <p>Manage all church reservations and change requests</p>
  </div>

  <?php if ($message): ?>
    <p class="<?= $message_type ?>">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>

  
  <div class="kpi-grid">
    <div class="kpi-card events">
      <div class="kpi-label">Total Events</div>
      <div class="kpi-number"><?= $total_events ?></div>
      <div class="kpi-sublabel"><?= $upcoming_events ?> upcoming</div>
    </div>
    
    <div class="kpi-card pending">
      <div class="kpi-label">Pending Requests</div>
      <div class="kpi-number"><?= $pending_requests ?></div>
      <div class="kpi-sublabel">Awaiting review</div>
    </div>
    
    <div class="kpi-card approved">
      <div class="kpi-label">Approved</div>
      <div class="kpi-number"><?= $approved_requests ?></div>
      <div class="kpi-sublabel">Change requests</div>
    </div>
    
    <div class="kpi-card rejected">
      <div class="kpi-label">Rejected</div>
      <div class="kpi-number"><?= $rejected_requests ?></div>
      <div class="kpi-sublabel">Change requests</div>
    </div>
    
    <div class="kpi-card users">
      <div class="kpi-label">Verified Users</div>
      <div class="kpi-number"><?= $total_users ?></div>
      <div class="kpi-sublabel"><?= $unverified_users ?> unverified</div>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="action-buttons">
    <button class="btn-download" onclick="downloadKPIReport()">
      📊 Download KPI Report
    </button>
    <button class="btn-download" onclick="downloadEventsCSV()">
      📥 Download Events CSV
    </button>
  </div>

  
  <h2>Manage Events</h2>
  <?php if (count($events) > 0): ?>
    <table class="table" id="eventsTable">
      <thead>
        <tr>
          <th>Date</th>
          <th>Time</th>
          <th>Event</th>
          <th>User</th>
          <th>Email</th>
          <th>Description</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($events as $e): ?>
        <tr>
          <td><?= date('M d, Y', strtotime($e['event_date'])) ?></td>
          <td><?= date('g:i A', strtotime($e['start_time'])) ?></td>
          <td><strong><?= htmlspecialchars($e['event_name']) ?></strong></td>
          <td><?= htmlspecialchars($e['username'] ?: 'N/A') ?></td>
          <td><?= htmlspecialchars($e['email'] ?: 'N/A') ?></td>
          <td><?= htmlspecialchars($e['description'] ?: 'N/A') ?></td>
          <td class="action-links">
            <a href="?delete=<?= $e['id'] ?>" onclick="return confirm('Are you sure you want to delete this event?')" style="color: #d32f2f;">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align: center; color: #78909c; padding: 30px;">No events found.</p>
  <?php endif; ?>

 
  <h2 style="margin-top: 40px;">
    Change Requests
    <?php if ($pending_requests > 0): ?>
      <span class="notification-badge">
        <span class="badge"><?= $pending_requests ?></span>
      </span>
    <?php endif; ?>
    <span class="email-indicator">📧 Email notifications enabled</span>
  </h2>
  
  <div class="info-box" style="margin-bottom: 20px;">
    <strong>ℹ️ Notification System:</strong> When you approve or reject a change request, the user will automatically receive:
    <ul style="margin: 10px 0 0 20px;">
      <li>📧 Email notification with details</li>
      <li>🔔 In-app notification in their account</li>
    </ul>
  </div>
  
  <?php if (count($requests) > 0): ?>
    <table class="table">
      <thead>
        <tr>
          <th>User</th>
          <th>Email</th>
          <th>Event</th>
          <th>Date</th>
          <th>Requested Change</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['username']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= htmlspecialchars($r['event_name']) ?></td>
          <td><?= date('M d, Y', strtotime($r['event_date'])) ?></td>
          <td><?= htmlspecialchars($r['requested_change']) ?></td>
          <td>
            <span class="status-badge status-<?= $r['status'] ?>">
              <?= strtoupper($r['status']) ?>
            </span>
          </td>
          <td class="action-links">
            <?php if ($r['status'] == 'pending'): ?>
              <a href="?approve=<?= $r['id'] ?>">Approve</a> |
              <a href="?reject=<?= $r['id'] ?>">Reject</a>
            <?php else: ?>
              <span style="color: #90a4ae;">✓ Notified</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="text-align: center; color: #78909c; padding: 30px;">No change requests found.</p>
  <?php endif; ?>

  <div style="margin-top: 30px;">
    <button onclick="window.location.href='../calendar.php'" class="btn-secondary">Back to Calendar</button>
  </div>
</div>

<script>
function downloadKPIReport() {
  window.location.href = '../includes/download_kpi.php?format=pdf';
}

function downloadEventsCSV() {
  window.location.href = '../includes/download_events.php?format=csv';
}
</script>
</body>
</html>
