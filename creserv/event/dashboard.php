<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit;
}
if ($_SESSION['is_admin'] == 1) {
    header("Location: admin/admin_dashboard.php");
} else {
    header("Location: calendar.php");
}
exit;
?>
