<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

// For availability check
$stmt = $conn->query("SELECT event_date, start_time, end_time FROM event WHERE event_date >= CURDATE()");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
$eventSlots = [];
foreach ($events as $e) {
    $eventSlots[] = [
        'date' => $e['event_date'],
        'start' => $e['start_time'],
        'end' => $e['end_time']
    ];
}

$is_admin = $_SESSION['is_admin'] ?? 0;
$username = htmlspecialchars($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Church Reservation Dashboard</title>
  <link rel="stylesheet" href="style/style.css">
</head>
<body>
  <div class="container">

    <!-- Header -->
    <div class="header">
      <div class="header-left">
        <a href="users/history.php"><?= $username ?></a>
      </div>
      <div class="header-right">
        <?php if ($is_admin): ?>
          <a href="admin/admin_dashboard.php" class="btn-secondary">Admin</a>
        <?php endif; ?>
        <a href="users/logout.php" class="btn-secondary">Logout</a>
      </div>
    </div>

    <h1>Book the Church for Your Event with Ease</h1>
    <p>Reserve a date for weddings, baptisms, and other church events.</p>
    <button onclick="window.location.href='reserve.php'">Book Now</button>

    <hr style="margin:25px 0;">

    <h2>Check Availability</h2>
    <form method="post" id="checkForm" onsubmit="return checkDateAvailability(event)">
      <label for="check_date">Select a Date:</label>
      <input type="date" id="check_date" name="check_date" min="<?= date('Y-m-d') ?>">

      <label for="check_start">Start Time:</label>
      <input type="time" id="check_start" name="check_start">

      <label for="check_end">End Time:</label>
      <input type="time" id="check_end" name="check_end">

      <input type="submit" value="Check Availability">
    </form>

    <p id="resultMsg"></p>
  </div>

  <script>
    const bookedSlots = <?= json_encode($eventSlots) ?>;

    function checkDateAvailability(e) {
      e.preventDefault();
      const date = document.getElementById("check_date").value;
      const start = document.getElementById("check_start").value;
      const end = document.getElementById("check_end").value;
      const msg = document.getElementById("resultMsg");

      if (!date || !start || !end) {
        msg.textContent = "Please select a full date and time range.";
        msg.className = "error";
        return false;
      }

      let overlap = false;
      for (const event of bookedSlots) {
        if (event.date === date) {
          if ((start >= event.start && start < event.end) || (end > event.start && end <= event.end)) {
            overlap = true;
            break;
          }
        }
      }

      if (overlap) {
        msg.textContent = "That date and time is reserved.";
        msg.className = "error";
      } else {
        msg.textContent = "That date and time is available!";
        msg.className = "success";
      }
      return false;
    }
  </script>
</body>
</html>
