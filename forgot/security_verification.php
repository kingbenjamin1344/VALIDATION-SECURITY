<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';
$message_type = '';
$show_modal = false;
$modal_title = '';
$modal_message = '';

// Get user and security questions
$userStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$userStmt->bind_param('s', $email);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$user_id = $user['id'];

// Get security questions
$secStmt = $conn->prepare("SELECT question_1, question_2, question_3 FROM security_questions WHERE user_id = ?");
$secStmt->bind_param('i', $user_id);
$secStmt->execute();
$secResult = $secStmt->get_result();
$securityQuestions = $secResult->fetch_assoc();

// Check if user has security questions
if (!$securityQuestions) {
    // No security questions, redirect to reset password
    header('Location: reset_password.php');
    exit();
}

// Initialize attempts tracker
if (!isset($_SESSION['sec_attempts'])) {
    $_SESSION['sec_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answer1 = trim($_POST['answer1'] ?? '');
    $answer2 = trim($_POST['answer2'] ?? '');
    $answer3 = trim($_POST['answer3'] ?? '');
    
    // Get stored answers
    $ansStmt = $conn->prepare("SELECT answer_1, answer_2, answer_3 FROM security_questions WHERE user_id = ?");
    $ansStmt->bind_param('i', $user_id);
    $ansStmt->execute();
    $ansResult = $ansStmt->get_result();
    $answers = $ansResult->fetch_assoc();
    
    // Check answers (case-insensitive, trimmed)
    $answer1_match = strtolower($answer1) === strtolower($answers['answer_1']);
    $answer2_match = strtolower($answer2) === strtolower($answers['answer_2']);
    $answer3_match = strtolower($answer3) === strtolower($answers['answer_3']);
    
    if ($answer1_match && $answer2_match && $answer3_match) {
        // All answers correct
        $_SESSION['security_verified'] = true;
        
        // Clean up session
        unset($_SESSION['sec_attempts']);
        
        header('Location: reset_password.php');
        exit();
    } else {
        // Incorrect answers
        $_SESSION['sec_attempts']++;
        
        if ($_SESSION['sec_attempts'] >= 5) {
            // Exceeded max attempts
            $message = 'Maximum attempts exceeded.';
            $message_type = 'error';
            $show_modal = true;
            $modal_title = 'Maximum Attempts Exceeded';
            $modal_message = 'You have exceeded the maximum number of attempts to answer security questions.';
        } else {
            $remaining = 5 - $_SESSION['sec_attempts'];
            $message = "Incorrect answers. $remaining attempt(s) remaining.";
            $message_type = 'error';
        }
    }
}

$can_continue = $_SESSION['sec_attempts'] < 5;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions Verification</title>
    <link rel="stylesheet" href="../css/main.login.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .security-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .security-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .security-info {
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
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-group input:disabled,
        .form-group textarea:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.65;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 60px;
        }
        .question-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
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
            margin-top: 10px;
        }
        .btn-submit:hover:not(:disabled) {
            background-color: #0056b3;
        }
        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
        .attempt-counter {
            text-align: center;
            color: #999;
            font-size: 14px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="security-container">
        <h2>Security Questions Verification</h2>
        <div class="security-info">
            <p>Please answer your security questions to proceed with password reset.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($can_continue): ?>
        <form method="POST" id="securityForm">
            <div class="form-group">
                <div class="question-label">Question 1:</div>
                <label><?php echo htmlspecialchars($securityQuestions['question_1']); ?></label>
                <input type="text" name="answer1" required placeholder="Your answer">
            </div>
            
            <div class="form-group">
                <div class="question-label">Question 2:</div>
                <label><?php echo htmlspecialchars($securityQuestions['question_2']); ?></label>
                <input type="text" name="answer2" required placeholder="Your answer">
            </div>
            
            <div class="form-group">
                <div class="question-label">Question 3:</div>
                <label><?php echo htmlspecialchars($securityQuestions['question_3']); ?></label>
                <input type="text" name="answer3" required placeholder="Your answer">
            </div>
            
            <button type="submit" class="btn-submit">Verify Answers</button>
            <div class="attempt-counter">
                Attempts: <?php echo $_SESSION['sec_attempts']; ?>/5
            </div>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="forgot_password.php">← Start Over</a>
        </div>
    </div>

    <!-- Modal for max attempts exceeded -->
    <div id="attemptsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $modal_title; ?></h2>
            </div>
            <div class="modal-body">
                <p><?php echo $modal_message; ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-primary" onclick="goToLogin()">Back to Login</button>
            </div>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
    <script>
        // Show modal if max attempts exceeded
        <?php if ($show_modal): ?>
        const attemptsModal = new Modal('attemptsModal');
        attemptsModal.show('<?php echo $modal_title; ?>', '<?php echo $modal_message; ?>', [
            {
                text: 'Back to Login',
                class: 'btn-primary',
                onclick: () => {
                    window.location.href = '../logform/login.php';
                }
            }
        ]);
        <?php endif; ?>

        function goToLogin() {
            window.location.href = '../logform/login.php';
        }
    </script>
</body>
</html>
