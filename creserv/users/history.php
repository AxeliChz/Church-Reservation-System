<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $request = trim($_POST['requested_change']);
    $stmt = $conn->prepare("INSERT INTO change_requests (user_id, event_id, requested_change) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $event_id, $request]);
}

$events = $conn->prepare("SELECT * FROM event WHERE user_id = ?");
$events->execute([$user_id]);
$list = $events->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head><title>My History</title><link rel="stylesheet" href="../style/style.css"></head>
<body>
<div class="container">
<h1>My Reservation History</h1>
<table class="table">
<tr><th>Date</th><th>Event</th><th>Actions</th></tr>
<?php foreach ($list as $e): ?>
<tr>
<td><?= htmlspecialchars($e['event_date']) ?></td>
<td><?= htmlspecialchars($e['event_name']) ?></td>
<td>
<form method="post">
<input type="hidden" name="event_id" value="<?= $e['id'] ?>">
<textarea name="requested_change" placeholder="Describe your requested change..."></textarea>
<input type="submit" value="Request Change">
</form>
</td>
</tr>
<?php endforeach; ?>
</table>
<a href="../calendar.php">Back</a>
</div>
</body>
</html>
