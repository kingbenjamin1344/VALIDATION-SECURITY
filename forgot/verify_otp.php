<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['otp'])) {
        $otp = $_POST['otp'];
        
        // Get OTP from DB
        $stmt = $conn->prepare("SELECT otp_hash, expires_at, attempts FROM password_resets WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            
            if ($reset['attempts'] >= 5) {
                $message = 'Too many attempts. Try again later.';
            } elseif (strtotime($reset['expires_at']) < time()) {
                $message = 'OTP expired.';
            } elseif (password_verify($otp, $reset['otp_hash'])) {
                // OTP correct
                $_SESSION['otp_verified'] = true;
                header('Location: reset_password.php');
                exit();
            } else {
                // Increment attempts
                $attempts = $reset['attempts'] + 1;
                $conn->query("UPDATE password_resets SET attempts = $attempts WHERE email = '$email'");
                $message = 'Invalid OTP. Attempts: ' . $attempts . '/5';
            }
        } else {
            $message = 'No OTP found.';
        }
    } elseif (isset($_POST['resend'])) {
        // Resend OTP
        $otp = rand(100000, 999999);
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");
        $stmt = $conn->prepare("INSERT INTO password_resets (email, otp_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $otp_hash, $expires_at);
        $stmt->execute();
        
        $message = "OTP resent: $otp (Demo)";
        $_SESSION['resend_time'] = time();
    }
}

$can_resend = !isset($_SESSION['resend_time']) || (time() - $_SESSION['resend_time']) > 60;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="../css/main.login.css">
</head>
<body>
    <div class="container">
        <h2>Verify OTP</h2>
        <p>OTP sent to <?php echo $email; ?></p>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST" id="otpForm">
            <label for="otp">Enter OTP:</label>
            <input type="text" name="otp" maxlength="6" required>
            <button type="submit">Verify</button>
        </form>
        <form method="POST" id="resendForm">
            <button type="submit" name="resend" <?php echo $can_resend ? '' : 'disabled'; ?>>Resend OTP</button>
        </form>
        <p id="timer"></p>
        <a href="forgot_password.php">Back</a>
    </div>

    <script>
        let countdown = 600; // 10 minutes
        const timerElement = document.getElementById('timer');
        const resendButton = document.querySelector('button[name="resend"]');

        function updateTimer() {
            const minutes = Math.floor(countdown / 60);
            const seconds = countdown % 60;
            timerElement.textContent = `Time left: ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            countdown--;
            if (countdown < 0) {
                clearInterval(timer);
                timerElement.textContent = 'OTP expired';
            }
        }

        updateTimer();
        const timer = setInterval(updateTimer, 1000);

        // Resend cooldown
        let resendCountdown = <?php echo $can_resend ? 0 : 60 - (time() - $_SESSION['resend_time']); ?>;
        if (resendCountdown > 0) {
            resendButton.disabled = true;
            const resendTimer = setInterval(() => {
                resendCountdown--;
                if (resendCountdown <= 0) {
                    resendButton.disabled = false;
                    clearInterval(resendTimer);
                }
            }, 1000);
        }
    </script>
</body>
</html>