<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/src/Exception.php';
require '../vendor/src/PHPMailer.php';
require '../vendor/src/SMTP.php';

session_start();
require_once '../regform/config.php';

// configurable block duration (minutes)
$blockDuration = 1;

if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';
$message_type = '';
$show_modal = false;
$modal_title = '';
$modal_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle resend first to avoid accidental OTP submission being treated as resend
    if (isset($_POST['resend'])) {
        // Resend OTP handling
        // Get latest OTP record to check resend count
        $stmt = $conn->prepare("SELECT resend_count, is_blocked, blocked_until FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            
            // Check if email is blocked
            if ($reset['is_blocked'] && strtotime($reset['blocked_until']) > time()) {
                $remainingTime = ceil((strtotime($reset['blocked_until']) - time()) / 60);
                $message = "Email blocked. Try again in $remainingTime minute(s).";
                $message_type = 'error';
            } 
            // Check if max resend reached (allow 2 resends)
            elseif ($reset['resend_count'] >= 2) {
                // Block email for configured minutes
                $blocked_until = date('Y-m-d H:i:s', strtotime('+' . $blockDuration . ' minutes'));
                $conn->query("UPDATE password_resets SET is_blocked = 1, blocked_until = '$blocked_until' WHERE email = '$email'");
                
                $remainingTime = $blockDuration;
                $message = "Maximum resend attempts exceeded. Email blocked for $remainingTime minutes.";
                $message_type = 'error';
                $show_modal = true;
                $modal_title = 'Maximum Resend Exceeded';
                $modal_message = "You have exceeded the maximum number of OTP resends. Please try again in $remainingTime minutes.";
            } 
            else {
                // Generate new OTP
                $new_otp = rand(100000, 999999);
                $expires_at = date('Y-m-d H:i:s', strtotime('+3 minutes'));
                $newResendCount = $reset['resend_count'] + 1;
                
                // Delete old record and create new one
                $conn->query("DELETE FROM password_resets WHERE email = '$email'");
                
                $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, otp_attempts, resend_count, expires_at, last_resend_time) VALUES (?, ?, 0, ?, ?, NOW())");
                $stmt->bind_param('ssss', $email, $new_otp, $newResendCount, $expires_at);
                
                if ($stmt->execute()) {
                    // Send OTP via email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'johnreyherobind@gmail.com';
                        $mail->Password   = 'jvxaikgbsyjbpixo';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('johnreyherobind@gmail.com', 'Security System');
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = 'New Password Reset OTP';
                        $mail->Body    = "Your new OTP for password reset is: <b>$new_otp</b>. It expires in 3 minutes.";

                        $mail->send();
                        
                        $_SESSION['last_resend_time'] = time();
                        $_SESSION['otp_resend_success'] = true;
                    } catch (Exception $e) {
                        $_SESSION['otp_resend_success'] = false;
                    }
                }
            }
        }
    }
    elseif (isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
        
        // Get latest OTP record
        $stmt = $conn->prepare("SELECT otp, expires_at, otp_attempts, is_blocked, blocked_until FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            
            // Check if email is blocked
            if ($reset['is_blocked'] && strtotime($reset['blocked_until']) > time()) {
                $remainingTime = ceil((strtotime($reset['blocked_until']) - time()) / 60);
                $message = "Email blocked. Try again in $remainingTime minute(s).";
                $message_type = 'error';
                $show_modal = true;
                $modal_title = 'Email Blocked';
                $modal_message = "This email has exceeded maximum attempts. Please try again in $remainingTime minute(s).";
            } 
            // Check if OTP expired
            elseif (strtotime($reset['expires_at']) < time()) {
                $message = 'OTP expired. Please request a new one.';
                $message_type = 'error';
                $show_modal = true;
                $modal_title = 'OTP Expired';
                $modal_message = 'Your OTP has expired. Please request a new OTP.';
            } 
            // Check OTP match
            elseif ($otp === $reset['otp']) {
                // OTP correct - check if user has security questions
                $userStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $userStmt->bind_param('s', $email);
                $userStmt->execute();
                $userResult = $userStmt->get_result();
                $user = $userResult->fetch_assoc();
                
                // Check if user has security questions
                $secStmt = $conn->prepare("SELECT id FROM security_questions WHERE user_id = ?");
                $secStmt->bind_param('i', $user['id']);
                $secStmt->execute();
                $secResult = $secStmt->get_result();
                
                $_SESSION['otp_verified'] = true;
                
                if ($secResult->num_rows > 0) {
                    // User has security questions, go to security verification
                    header('Location: security_verification.php');
                } else {
                    // No security questions, go directly to reset password
                    header('Location: reset_password.php');
                }
                exit();
            } 
            // Incorrect OTP - increment attempts
            else {
                $newAttempts = $reset['otp_attempts'] + 1;
                
                if ($newAttempts >= 3) {
                    // Block email for configured minutes
                    $blocked_until = date('Y-m-d H:i:s', strtotime('+' . $blockDuration . ' minutes'));
                    $conn->query("UPDATE password_resets SET otp_attempts = $newAttempts, is_blocked = 1, blocked_until = '$blocked_until' WHERE email = '$email'");
                    
                    $remainingTime = $blockDuration;
                    $message = "Maximum OTP attempts exceeded. Email blocked for $remainingTime minutes.";
                    $message_type = 'error';
                    $show_modal = true;
                    $modal_title = 'Maximum Attempts Exceeded';
                    $modal_message = "You have exceeded the maximum number of OTP attempts. Please try again in $remainingTime minutes.";
                } else {
                    $remaining = 3 - $newAttempts;
                    $conn->query("UPDATE password_resets SET otp_attempts = $newAttempts WHERE email = '$email'");
                    $message = "Invalid OTP. $remaining attempt(s) remaining.";
                    $message_type = 'error';
                }
            }
        } else {
            $message = 'No OTP found. Please request a new one.';
            $message_type = 'error';
        }
    } 
    elseif (isset($_POST['resend'])) {
        // Get latest OTP record to check resend count
        $stmt = $conn->prepare("SELECT resend_count, is_blocked, blocked_until FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset = $result->fetch_assoc();
            
            // Check if email is blocked
            if ($reset['is_blocked'] && strtotime($reset['blocked_until']) > time()) {
                $remainingTime = ceil((strtotime($reset['blocked_until']) - time()) / 60);
                $message = "Email blocked. Try again in $remainingTime minute(s).";
                $message_type = 'error';
            } 
            // Check if max resend reached
            elseif ($reset['resend_count'] >= 3) {
                // Block email for 10 minutes
                $blocked_until = date('Y-m-d H:i:s', strtotime('+1 minutes'));
                $conn->query("UPDATE password_resets SET is_blocked = 1, blocked_until = '$blocked_until' WHERE email = '$email'");
                
                $remainingTime = 1;
                $message = "Maximum resend attempts exceeded. Email blocked for $remainingTime minutes.";
                $message_type = 'error';
                $show_modal = true;
                $modal_title = 'Maximum Resend Exceeded';
                $modal_message = "You have exceeded the maximum number of OTP resends. Please try again in $remainingTime minutes.";
            } 
            else {
                // Generate new OTP
                $new_otp = rand(100000, 999999);
                $expires_at = date('Y-m-d H:i:s', strtotime('+3 minutes'));
                $newResendCount = $reset['resend_count'] + 1;
                
                // Delete old record and create new one
                $conn->query("DELETE FROM password_resets WHERE email = '$email'");
                
                $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, otp_attempts, resend_count, expires_at, last_resend_time) VALUES (?, ?, 0, ?, ?, NOW())");
                $stmt->bind_param('ssss', $email, $new_otp, $newResendCount, $expires_at);
                
                if ($stmt->execute()) {
                    // Send OTP via email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'johnreyherobind@gmail.com';
                        $mail->Password   = 'jvxaikgbsyjbpixo';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;

                        $mail->setFrom('johnreyherobind@gmail.com', 'Security System');
                        $mail->addAddress($email);

                        $mail->isHTML(true);
                        $mail->Subject = 'New Password Reset OTP';
                        $mail->Body    = "Your new OTP for password reset is: <b>$new_otp</b>. It expires in 3 minutes.";

                        $mail->send();
                        
                        $_SESSION['last_resend_time'] = time();
                        $_SESSION['otp_resend_success'] = true;
                    } catch (Exception $e) {
                        $_SESSION['otp_resend_success'] = false;
                    }
                }
            }
        }
    }
}

// Check if last resend was recent for cooldown
$lastResendTime = $_SESSION['last_resend_time'] ?? 0;
$timeSinceResend = time() - $lastResendTime;
$canResend = $timeSinceResend >= 60;
$resendCooldown = max(0, 60 - $timeSinceResend);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="../css/main.login.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .otp-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .otp-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .otp-info {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 18px;
            letter-spacing: 4px;
            text-align: center;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-group input:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.65;
        }
        .timer-display {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin: 15px 0;
            padding: 10px;
            background-color: #e7f3ff;
            border-radius: 4px;
        }
        .timer-display.warning {
            color: #ff6b6b;
            background-color: #ffe7e7;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .btn-group button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit {
            background-color: #007bff;
            color: white;
        }
        .btn-submit:hover:not(:disabled) {
            background-color: #0056b3;
        }
        .btn-resend {
            background-color: #6c757d;
            color: white;
        }
        .btn-resend:hover:not(:disabled) {
            background-color: #5a6268;
        }
        .btn-resend:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .resend-cooldown {
            text-align: center;
            color: #999;
            font-size: 14px;
            margin-top: 10px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }
        .back-link a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="otp-container">
        <h2>Verify OTP</h2>
        <div class="otp-info">
            <p>OTP sent to: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="otpForm">
            <div class="form-group">
                <label for="otp">Enter OTP (6 digits):</label>
                <input 
                    type="text" 
                    name="otp" 
                    id="otp" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    required 
                    placeholder="000000"
                    <?php echo ($show_modal ? 'disabled' : ''); ?>
                >
            </div>
            <div class="timer-display" id="timerDisplay">OTP expires in: 3:00</div>
            
            <div class="btn-group">
                <button type="submit" class="btn-submit" id="submitBtn" <?php echo ($show_modal ? 'disabled' : ''); ?>>Verify OTP</button>
                <button type="button" class="btn-resend" id="resendBtn" <?php echo ($show_modal || !$canResend ? 'disabled' : ''); ?> onclick="submitResend()">Resend OTP</button>
            </div>
            
            <div id="cooldownDisplay" style="display: none;" class="resend-cooldown">
                Resend available in: <span id="cooldownTimer">60</span>s
            </div>
        </form>
        
        <div class="back-link">
            <a href="forgot_password.php">← Request Different Email</a>
        </div>
    </div>

    <!-- Modal for blocking/errors -->
    <div id="alertModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><?php echo $modal_title; ?></h2>
            </div>
            <div class="modal-body">
                <p id="modalMessage"><?php echo $modal_message; ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-primary" onclick="handleModalAction()">OK</button>
            </div>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
    <script>
        const otpExpiryTime = <?php echo (strtotime(date('Y-m-d H:i:s')) + 180); ?> * 1000; // 3 minutes
        let otp_countdown = 180;
        const timerElement = document.getElementById('timerDisplay');
        const otpInput = document.getElementById('otp');
        const submitBtn = document.getElementById('submitBtn');
        const resendBtn = document.getElementById('resendBtn');

        // OTP Timer
        function updateOtpTimer() {
            const minutes = Math.floor(otp_countdown / 60);
            const seconds = otp_countdown % 60;
            timerElement.textContent = `OTP expires in: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (otp_countdown <= 30) {
                timerElement.classList.add('warning');
            }
            
            otp_countdown--;
            
            if (otp_countdown < 0) {
                clearInterval(otpTimer);
                timerElement.textContent = 'OTP expired';
                otpInput.disabled = true;
                submitBtn.disabled = true;
            }
        }

        updateOtpTimer();
        const otpTimer = setInterval(updateOtpTimer, 1000);

        // Resend Cooldown
        let resendCooldown = <?php echo $resendCooldown; ?>;
        const cooldownDisplay = document.getElementById('cooldownDisplay');
        const cooldownTimer = document.getElementById('cooldownTimer');

        if (resendCooldown > 0) {
            cooldownDisplay.style.display = 'block';
            resendBtn.disabled = true;
            
            const cooldownInterval = setInterval(() => {
                resendCooldown--;
                cooldownTimer.textContent = resendCooldown;
                
                if (resendCooldown <= 0) {
                    clearInterval(cooldownInterval);
                    cooldownDisplay.style.display = 'none';
                    resendBtn.disabled = false;
                }
            }, 1000);
        }

        function submitResend() {
            // Create a dedicated form for resending to avoid sending OTP field
            const resendForm = document.createElement('form');
            resendForm.method = 'POST';
            resendForm.style.display = 'none';
            const resendInput = document.createElement('input');
            resendInput.type = 'hidden';
            resendInput.name = 'resend';
            resendInput.value = '1';
            resendForm.appendChild(resendInput);
            document.body.appendChild(resendForm);
            resendForm.submit();
        }

        // Show modal if needed
        <?php if ($show_modal): ?>
        const alertModal = new Modal('alertModal');
        alertModal.show('<?php echo $modal_title; ?>', '<?php echo $modal_message; ?>', [
            {
                text: 'OK',
                class: 'btn-primary',
                onclick: () => {
                    window.location.href = 'forgot_password.php';
                }
            }
        ]);
        <?php endif; ?>

        // Check if resend was successful
        <?php if (isset($_SESSION['otp_resend_success'])): ?>
            <?php if ($_SESSION['otp_resend_success']): ?>
                // Reload page to reset timer and show new OTP countdown
                alert('OTP resent successfully! New timer started.');
                location.reload();
            <?php endif; ?>
            <?php unset($_SESSION['otp_resend_success']); ?>
        <?php endif; ?>

        // OTP Input formatting (only numbers)
        otpInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html>