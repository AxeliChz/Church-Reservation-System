<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['user_id'])) {
    echo "Please log in first.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = trim($_POST["date"]);
    $start = trim($_POST["start_time"]);

    if (empty($date) || empty($start)) {
        echo "Please provide both date and time.";
        exit;
    }

    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM event WHERE event_date = :event_date AND start_time = :start_time");
    $stmt->execute([
        ':event_date' => $date,
        ':start_time' => $start
    ]);

    if ($stmt->fetchColumn() > 0) {
        echo "This time slot is already reserved.";
    } else {
        echo "This time slot is available! You can proceed with booking.";
    }
}
?>