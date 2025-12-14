<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: users/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Reservation System</title>
    <link rel="stylesheet" href="style/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <span class="username"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </div>
            <div class="header-right">
                <a href="users/history.php" class="btn-link">My History</a>
                <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
                    <a href="admin/admin_dashboard.php" class="btn-link admin-link">Admin Panel</a>
                <?php else: ?>
                    <a href="users/account.php" class="btn-link">Account</a>
                <?php endif; ?>
                <a href="users/logout.php" class="btn-secondary logout-btn">Logout</a>
            </div>
        </div>

        <div class="welcome-section">
            <h1>Book the Church for Your Event with Ease</h1>
            <p>Reserve a date for weddings, baptisms, and other church events.</p>
            <button class="btn-primary btn-large" onclick="window.location.href='reserve.php'">Book Now</button>
        </div>

        <hr class="divider">

        <div class="check-section">
            <h2>Check Availability</h2>
            <form id="checkForm" onsubmit="return checkAvailability(event)">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select a Date:</label>
                        <input type="date" name="date" id="date" min="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Start Time:</label>
                        <input type="time" name="start_time" id="start_time" required>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Check Availability</button>
            </form>

            <p id="result" class="availability-message"></p>
        </div>
    </div>

    <script>
        async function checkAvailability(event) {
            event.preventDefault();
            const date = document.getElementById('date').value;
            const start = document.getElementById('start_time').value;
            const resultEl = document.getElementById('result');

            if (!date || !start) {
                resultEl.textContent = "Please select a date and time.";
                resultEl.className = "availability-message error";
                return;
            }

            resultEl.textContent = "Checking...";
            resultEl.className = "availability-message";

            try {
                const response = await fetch('check_availability.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `date=${encodeURIComponent(date)}&start_time=${encodeURIComponent(start)}`
                });
                const result = await response.text();
                resultEl.textContent = result;
                resultEl.className = result.includes('available') ? 'availability-message success' : 'availability-message error';
            } catch (error) {
                resultEl.textContent = "Error checking availability. Please try again.";
                resultEl.className = "availability-message error";
            }
        }
    </script>
</body>
</html>