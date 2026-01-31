<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/src/Exception.php';
require '../vendor/src/PHPMailer.php';
require '../vendor/src/SMTP.php';

session_start();
require_once '../regform/config.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists in users table
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Check if email is currently blocked
        $checkBlock = $conn->prepare("SELECT is_blocked, blocked_until FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $checkBlock->bind_param('s', $email);
        $checkBlock->execute();
        $blockResult = $checkBlock->get_result();
        
        if ($blockResult->num_rows > 0) {
            $blockData = $blockResult->fetch_assoc();
            if ($blockData['is_blocked'] && strtotime($blockData['blocked_until']) > time()) {
                $remainingTime = ceil((strtotime($blockData['blocked_until']) - time()) / 60);
                $message = "This email is blocked. Please try again in $remainingTime minute(s).";
                $message_type = 'error';
            } else {
                // Block has expired, proceed with new OTP
                $canProceed = true;
            }
        } else {
            $canProceed = true;
        }
        
        if (!isset($canProceed)) {
            $canProceed = false;
        }
        
        if ($canProceed) {
            // Generate OTP (6 digits)
            $otp = rand(100000, 999999);
            $expires_at = date('Y-m-d H:i:s', strtotime('+3 minutes'));
            
            // Clear old OTP records for this email
            $conn->query("DELETE FROM password_resets WHERE email = '$email'");
            
            // Insert new OTP
            $stmt = $conn->prepare("INSERT INTO password_resets (email, otp, otp_attempts, resend_count, expires_at, created_at) VALUES (?, ?, 0, 0, ?, NOW())");
            $stmt->bind_param('sss', $email, $otp, $expires_at);
            
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
                    $mail->Subject = 'Password Reset OTP';
                    $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. It expires in 3 minutes.";

                    $mail->send();
                    
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['otp_sent'] = true;
                    
                    // Redirect immediately to OTP verification page
                    header('Location: verify_otp.php');
                    exit();
                } catch (Exception $e) {
                    $message = "Failed to send OTP. Please try again.";
                    $message_type = 'error';
                }
            }
        }
    } else {
        $message = 'Email not found in our system.';
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../css/main.login.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .forgot-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .forgot-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .forgot-container p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
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
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #0056b3;
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
    <div class="forgot-container">
        <h2>Forgot Password?</h2>
        <p>Enter your email address and we'll send you an OTP to reset your password.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" name="email" id="email" required placeholder="Enter your email">
            </div>
            <button type="submit" class="btn-submit">Send OTP</button>
        </form>
        
        <div class="back-link">
            <a href="../logform/login.php">← Back to Login</a>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
</body>
</html>