<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: ../calendar.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// Delete event
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM event WHERE id = ?");
    $stmt->execute([$id]);
}

// Approve/reject change requests
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    $conn->prepare("UPDATE change_requests SET status='approved' WHERE id=?")->execute([$id]);
}
if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $conn->prepare("UPDATE change_requests SET status='rejected' WHERE id=?")->execute([$id]);
}

$events = $conn->query("SELECT * FROM event ORDER BY event_date")->fetchAll(PDO::FETCH_ASSOC);
$requests = $conn->query("SELECT cr.*, u.username, e.event_name 
FROM change_requests cr 
JOIN users u ON cr.user_id = u.id 
JOIN event e ON cr.event_id = e.id 
ORDER BY cr.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>Admin Dashboard</title><link rel="stylesheet" href="../style/style.css"></head>
<body>
<div class="container">
<h1>Admin Dashboard</h1>
<h2>Manage Events</h2>
<table class="table">
<tr><th>Date</th><th>Event</th><th>Actions</th></tr>
<?php foreach ($events as $e): ?>
<tr>
<td><?= htmlspecialchars($e['event_date']) ?></td>
<td><?= htmlspecialchars($e['event_name']) ?></td>
<td>
<a href="?delete=<?= $e['id'] ?>" onclick="return confirm('Delete this event?')">Delete</a>
</td>
</tr>
<?php endforeach; ?>
</table>

<h2>Change Requests</h2>
<table class="table">
<tr><th>User</th><th>Event</th><th>Request</th><th>Status</th><th>Actions</th></tr>
<?php foreach ($requests as $r): ?>
<tr>
<td><?= htmlspecialchars($r['username']) ?></td>
<td><?= htmlspecialchars($r['event_name']) ?></td>
<td><?= htmlspecialchars($r['requested_change']) ?></td>
<td><?= htmlspecialchars($r['status']) ?></td>
<td>
<a href="?approve=<?= $r['id'] ?>">Approve</a> |
<a href="?reject=<?= $r['id'] ?>">Reject</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<a href="../calendar.php">Back</a>
</div>
</body>
</html>
