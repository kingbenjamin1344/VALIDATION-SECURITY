<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answer1 = trim($_POST['answer1']);
    $answer2 = trim($_POST['answer2']);
    $answer3 = trim($_POST['answer3']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get user id first
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_row = $result->fetch_assoc();
    $user_id = $user_row['id'];
    
    // Get security questions and answers
    $stmt = $conn->prepare("SELECT question1, answer1, question2, answer2, question3, answer3 FROM user_security_questions WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sec = $result->fetch_assoc();
    
    if (password_verify($answer1, $sec['answer1']) &&
        password_verify($answer2, $sec['answer2']) &&
        password_verify($answer3, $sec['answer3'])) {
        
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param('ss', $hashed_password, $email);
            $stmt->execute();
            
            // Clean up
            $conn->query("DELETE FROM password_resets WHERE email = '$email'");
            session_destroy();
            
            $message = 'Password reset successfully. <a href="../logform/login.php">Login</a>';
        } else {
            $message = 'Passwords do not match.';
        }
    } else {
        $message = 'Incorrect security answers.';
    }
}

// Get questions
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user_row = $result->fetch_assoc();
if (!$user_row) {
    $message = 'User not found.';
} else {
    $user_id = $user_row['id'];

    $stmt = $conn->prepare("SELECT question1, question2, question3 FROM user_security_questions WHERE user_id = ?");
    if (!$stmt) {
        die('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $message = 'Security questions not set. Please contact support.';
    } else {
        $user = $result->fetch_assoc();
        if (!$user) {
            $message = 'Error loading questions.';
        }
    }
}

// Decode questions (assuming they are stored as hashes, but wait, questions are text, answers are hashed)
$questions = [
    "What is your favorite color?",
    "What was your first pet?",
    "What city were you born in?"
]; // Need to store questions too, but for simplicity, assume fixed or store in DB

// Actually, in the security_question.php, questions are predefined, but answers are hashed.
// For forgot, we need to ask the same questions.

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/main.login.css">
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
        <?php if (isset($user) && $user): ?>
        <form method="POST">
            <h3>Answer Security Questions</h3>
            <label><?php echo htmlspecialchars($user['question1']); ?></label>
            <input type="text" name="answer1" required>
            
            <label><?php echo htmlspecialchars($user['question2']); ?></label>
            <input type="text" name="answer2" required>
            
            <label><?php echo htmlspecialchars($user['question3']); ?></label>
            <input type="text" name="answer3" required>
            
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" required>
            
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            
            <button type="submit">Reset Password</button>
        </form>
        <?php endif; ?>
        <a href="forgot_password.php">Back</a>
    </div>
</body>
</html>