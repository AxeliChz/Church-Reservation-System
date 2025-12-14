<?php
session_start();
require_once "config/db.php";
require_once "includes/email_handler.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $name = trim($_POST["event_name"]);
    $date = trim($_POST["event_date"]);
    $desc = trim($_POST["description"]);
    $start = $_POST["start_time"];
    $today = date("Y-m-d");

    if (empty($name) || empty($date) || empty($start)) {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    } elseif ($date < $today) {
        $message = "You cannot reserve a past date.";
        $message_type = "error";
    } else {
        $check = $conn->prepare("SELECT COUNT(*) FROM event WHERE event_date = :event_date AND start_time = :start_time");
        $check->execute([
            ':event_date' => $date,
            ':start_time' => $start
        ]);

        if ($check->fetchColumn() > 0) {
            $message = "That date and time is already reserved. Please choose another time slot.";
            $message_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO event (user_id, event_name, event_date, description, start_time)
                                    VALUES (:user_id, :event_name, :event_date, :description, :start_time)");
            $stmt->execute([
                ':user_id' => $user_id,
                ':event_name' => $name,
                ':event_date' => $date,
                ':description' => $desc,
                ':start_time' => $start
            ]);
            
           
            $formattedDate = date('F d, Y', strtotime($date));
            $formattedTime = date('g:i A', strtotime($start));
            
            sendEventConfirmation(
                $_SESSION['email'],
                $_SESSION['username'],
                $name,
                $formattedDate,
                $formattedTime
            );
            
            $message = "Event reserved successfully! A confirmation email has been sent to your email address.";
            $message_type = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reserve a Date</title>
  <link rel="stylesheet" href="style/style.css">
  <style>
    .email-notice {
      background: #e3f2fd;
      border-left: 4px solid #2196f3;
      padding: 15px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
    }
    
    .email-notice strong {
      color: #1565c0;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Reserve a Date</h1>
    
    <?php if ($message): ?>
      <p class="<?= $message_type ?>">
        <?= htmlspecialchars($message) ?>
      </p>
      
      <?php if ($message_type === 'success'): ?>
        <div class="email-notice">
          <strong>📧 Confirmation Sent!</strong><br>
          Check your email (<strong><?= htmlspecialchars($_SESSION['email']) ?></strong>) for the booking confirmation and event details.
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="reservation-form">
      <form method="post">
        <label>Event Name: *</label>
        <input type="text" name="event_name" placeholder="e.g., Wedding, Baptism, Mass" required>

        <label>Description:</label>
        <textarea name="description" rows="4" placeholder="Optional: Add any additional details about your event..."></textarea>

        <div class="form-row">
          <div class="form-group">
            <label>Event Date: *</label>
            <input type="date" name="event_date" min="<?= date('Y-m-d') ?>" required>
          </div>

          <div class="form-group">
            <label>Start Time: *</label>
            <input type="time" name="start_time" required>
          </div>
        </div>
        
        <div class="email-notice">
          <strong>📧 Email Confirmation</strong><br>
          Upon successful reservation, you will receive a confirmation email at <strong><?= htmlspecialchars($_SESSION['email']) ?></strong> with your event details.
        </div>

        <div class="form-actions">
          <input type="submit" value="Submit Reservation" class="btn-primary">
          <button type="button" onclick="window.location.href='calendar.php'" class="btn-secondary">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
