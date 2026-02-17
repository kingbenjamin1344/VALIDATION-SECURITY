<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username']) && !isset($_SESSION['admin'])) {
    header('Location: ../logform/login.php');
    exit();
}

 $username = $_SESSION['username'] ?? null;

// Ensure security_questions table has admin support columns
$colCheck = $conn->query("SHOW COLUMNS FROM security_questions LIKE 'account_type'");
if ($colCheck && $colCheck->num_rows == 0) {
    $conn->query("ALTER TABLE security_questions ADD COLUMN account_type VARCHAR(10) NOT NULL DEFAULT 'user', ADD COLUMN account_id INT NULL");
}

// Determine account type (user or admin) and id
$account_type = 'user';
$account_id = null;
if (isset($_SESSION['admin'])) {
    $account_type = 'admin';
    $account_id = $_SESSION['admin']['id'];
} elseif ($username) {
    // Get user ID
    $userStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $userStmt->bind_param('s', $username);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userRow = $userResult->fetch_assoc();
    $account_id = $userRow['id'] ?? null;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['answers'])) {
    if (isset($_POST['edit'])) {
        // Load current questions for this account
        if ($account_type === 'admin') {
            $stmt = $conn->prepare("SELECT question_1, question_2, question_3 FROM security_questions WHERE account_type='admin' AND account_id=?");
            $stmt->bind_param('i', $account_id);
        } else {
            $stmt = $conn->prepare("SELECT question_1, question_2, question_3 FROM security_questions WHERE user_id=?");
            $stmt->bind_param('i', $account_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $questions = $result->fetch_assoc();
        $_SESSION['selected_questions'] = [
            'q1' => $questions['question_1'],
            'q2' => $questions['question_2'],
            'q3' => $questions['question_3']
        ];
    } else {
        $personal = $_POST['personal'] ?? '';
        $childhood = $_POST['childhood'] ?? '';
        $preference = $_POST['preference'] ?? '';

        if (empty($personal) || empty($childhood) || empty($preference)) {
            $message = 'Please select all three questions.';
            $message_type = 'error';
            header('Location: security_question.php');
            exit();
        }

        // Store in session for next step
        $_SESSION['selected_questions'] = [
            'q1' => $personal,
            'q2' => $childhood,
            'q3' => $preference
        ];
    }
} else {
    if (!isset($_SESSION['selected_questions'])) {
        header('Location: security_question.php');
        exit();
    }
}

$questions = $_SESSION['selected_questions'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answers'])) {
    $answer1 = trim($_POST['answers']['a1'] ?? '');
    $answer2 = trim($_POST['answers']['a2'] ?? '');
    $answer3 = trim($_POST['answers']['a3'] ?? '');
    
    if (empty($answer1) || empty($answer2) || empty($answer3)) {
        $message = 'All fields are required.';
        $message_type = 'error';
    } else {
        // Check if security questions already exist for this account
        if ($account_type === 'admin') {
            $checkStmt = $conn->prepare("SELECT id FROM security_questions WHERE account_type='admin' AND account_id=?");
            $checkStmt->bind_param('i', $account_id);
        } else {
            $checkStmt = $conn->prepare("SELECT id FROM security_questions WHERE user_id=?");
            $checkStmt->bind_param('i', $account_id);
        }
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing record
            if ($account_type === 'admin') {
                $stmt = $conn->prepare("UPDATE security_questions SET question_1=?, answer_1=?, question_2=?, answer_2=?, question_3=?, answer_3=? WHERE account_type='admin' AND account_id=?");
                $stmt->bind_param('ssssssi', 
                    $questions['q1'], $answer1, 
                    $questions['q2'], $answer2, 
                    $questions['q3'], $answer3, 
                    $account_id
                );
            } else {
                $stmt = $conn->prepare("UPDATE security_questions SET question_1=?, answer_1=?, question_2=?, answer_2=?, question_3=?, answer_3=? WHERE user_id=?");
                $stmt->bind_param('ssssssi', 
                    $questions['q1'], $answer1, 
                    $questions['q2'], $answer2, 
                    $questions['q3'], $answer3, 
                    $account_id
                );
            }
        } else {
            // Insert new record
            if ($account_type === 'admin') {
                $stmt = $conn->prepare("INSERT INTO security_questions (account_type, account_id, question_1, answer_1, question_2, answer_2, question_3, answer_3) VALUES ('admin', ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issssss', 
                    $account_id,
                    $questions['q1'], $answer1, 
                    $questions['q2'], $answer2, 
                    $questions['q3'], $answer3
                );
            } else {
                $stmt = $conn->prepare("INSERT INTO security_questions (user_id, question_1, answer_1, question_2, answer_2, question_3, answer_3) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('issssss', 
                    $account_id,
                    $questions['q1'], $answer1, 
                    $questions['q2'], $answer2, 
                    $questions['q3'], $answer3
                );
            }
        }
        
        if ($stmt->execute()) {
            unset($_SESSION['selected_questions']);
            $message = 'Security questions saved successfully!';
            $message_type = 'success';
            header('Refresh: 1.5; url: security_question.php');
        } else {
            $message = 'Error saving security questions. Please try again.';
            $message_type = 'error';
        }
    }
}

$questions = $_SESSION['selected_questions'] ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Security Answers</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .answers-card {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .answers-card h2 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .answers-card p {
            text-align: center;
            color: #666;
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
            margin-top: 10px;
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
        .question-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .info-text {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="answers-card">
        <h2>Answer Your Security Questions</h2>
        <div class="info-text">
            Provide answers that only you would know. These answers are case-insensitive but must be exact matches.
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="input_security_question.php" method="post">
            <div class="form-group">
                <div class="question-label">Question 1:</div>
                
                <input type="text" name="answers[a1]" required placeholder="Your answer">
            </div>
            <div class="form-group">
                <div class="question-label">Question 2:</div>
                
                <input type="text" name="answers[a2]" required placeholder="Your answer">
            </div>
            <div class="form-group">
                <div class="question-label">Question 3:</div>
                
                <input type="text" name="answers[a3]" required placeholder="Your answer">
            </div>
            <button type="submit" class="btn-submit">Save Security Answers</button>
        </form>
        
        <div class="back-link">
            <a href="security_question.php">← Back to Security Questions</a>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
</body>
</html>