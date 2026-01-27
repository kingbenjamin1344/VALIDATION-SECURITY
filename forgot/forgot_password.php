<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/src/Exception.php';
require '../vendor/src/PHPMailer.php';
require '../vendor/src/SMTP.php';

session_start();
require_once '../regform/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate OTP
        $otp = rand(100000, 999999);
        $otp_hash = password_hash($otp, PASSWORD_DEFAULT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Delete any existing OTP for this email
        $conn->query("DELETE FROM password_resets WHERE email = '$email'");
        
        // Insert new OTP
        $stmt = $conn->prepare("INSERT INTO password_resets (email, otp_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $email, $otp_hash, $expires_at);
        $stmt->execute();
        
        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Set the SMTP server to send through
            $mail->SMTPAuth   = true;
            $mail->Username   = 'johnreyherobind@gmail.com'; // REPLACE WITH YOUR GMAIL ADDRESS
            $mail->Password   = 'jvxaikgbsyjbpixo'; // REPLACE WITH GMAIL APP PASSWORD (NOT REGULAR PASSWORD)
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            //Recipients
            $mail->setFrom('johnreyherobind@gmail.com', 'Your App'); // REPLACE WITH YOUR GMAIL ADDRESS
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP';
            $mail->Body    = "Your OTP for password reset is: <b>$otp</b>. It expires in 10 minutes.";

            $mail->send();
            $_SESSION['otp_sent'] = true;
            $_SESSION['reset_email'] = $email;
            header('Location: verify_otp.php');
            exit();
        } catch (Exception $e) {
            $message = "OTP could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $message = 'Email not found.';
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
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="POST">
            <label for="email">Enter your email:</label>
            <input type="email" name="email" required>
            <button type="submit">Send OTP</button>
        </form>
        <a href="../logform/login.php">Back to Login</a>
    </div>
</body>
</html>