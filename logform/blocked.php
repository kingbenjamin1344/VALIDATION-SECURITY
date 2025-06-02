
<?php
$timeLeft = isset($_GET['timeLeft']) ? (int)$_GET['timeLeft'] : 15; // Default to 15 seconds
if ($timeLeft > 60) {
    $timeLeft = 60; // Maximum block time is 60 seconds
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocked</title>
    <script>
        // Disable back and forward navigation
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function () {
            window.history.pushState(null, "", window.location.href);
        };
    </script>
</head>
<body>
    <h1>Too many failed login attempts</h1>
    <p>You are blocked for <span id="countdown"><?php echo $timeLeft; ?></span> seconds.</p>

    <script>
        let timeLeft = <?php echo $timeLeft; ?>;
        const countdownElement = document.getElementById('countdown');

        function updateCountdown() {
            countdownElement.innerHTML = timeLeft;
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateCountdown, 1000);  // Countdown every second
            } else {
                window.location.href = 'login.php';  // Redirect to login page after countdown
            }
        }

        updateCountdown();  // Start the countdown
    </script>
</body>
</html>