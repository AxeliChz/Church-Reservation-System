<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST["event_name"]);
    $date = trim($_POST["event_date"]);
    $desc = trim($_POST["description"]);
    $start = $_POST["start_time"];
    $end = $_POST["end_time"];
    $today = date("Y-m-d");

    if (empty($name) || empty($date) || empty($start) || empty($end)) {
        $message = "Please fill in all fields.";
    } elseif ($date < $today) {
        $message = "You cannot reserve a past date.";
    } else {
        // Check overlap
        $check = $conn->prepare("SELECT COUNT(*) FROM event WHERE event_date = :event_date 
                                 AND ((:start_time BETWEEN start_time AND end_time)
                                 OR (:end_time BETWEEN start_time AND end_time))");
        $check->execute([
            ':event_date' => $date,
            ':start_time' => $start,
            ':end_time' => $end
        ]);

        if ($check->fetchColumn() > 0) {
            $message = "That date and time is reserved.";
        } else {
            $stmt = $conn->prepare("INSERT INTO event (user_id, event_name, event_date, description, start_time, end_time)
                                    VALUES (:user_id, :event_name, :event_date, :description, :start_time, :end_time)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':event_name' => $name,
                ':event_date' => $date,
                ':description' => $desc,
                ':start_time' => $start,
                ':end_time' => $end
            ]);
            $message = "Event reserved successfully!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reserve a Date</title>
  <link rel="stylesheet" href="style/style.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <div class="header-left">
        <a href="users/history.php"><?= htmlspecialchars($_SESSION['username']) ?></a>
      </div>
      <div class="header-right">
        <a href="users/logout.php" class="btn-secondary">Logout</a>
      </div>
    </div>

    <h1>Reserve a Date</h1>
    <p class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
      <?= htmlspecialchars($message) ?>
    </p>

    <form method="post">
      <label>Event Name:</label>
      <input type="text" name="event_name" required>

      <label>Description:</label>
      <textarea name="description" rows="3"></textarea>

      <label>Event Date:</label>
      <input type="date" name="event_date" min="<?= date('Y-m-d') ?>" required>

      <label>Start Time:</label>
      <input type="time" name="start_time" required>

      <label>End Time:</label>
      <input type="time" name="end_time" required>

      <input type="submit" value="Submit Reservation">
    </form>

    <button onclick="window.location.href='calendar.php'" class="btn-secondary">Back</button>
  </div>
</body>
</html>
