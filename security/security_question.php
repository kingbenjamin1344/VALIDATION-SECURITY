<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}

$username = $_SESSION['username'];

// Get user ID
$userStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$userStmt->bind_param('s', $username);
$userStmt->execute();
$userResult = $userStmt->get_result();
$userRow = $userResult->fetch_assoc();
$user_id = $userRow['id'];

// Check if security questions already exist
$stmt = $conn->prepare("SELECT question_1, question_2, question_3 FROM security_questions WHERE user_id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existingQuestions = $result->fetch_assoc();

$has_questions = !empty($existingQuestions);

$personal_questions = [
    "What is your favorite color?",
    "What is your favorite food?",
    "What is your favorite hobby?",
    "What is your favorite movie genre?",
    "What type of music do you like most?"
];

$childhood_questions = [
    "What was your favorite subject in school?",
    "What was your first pet?",
    "What was your childhood nickname?",
    "What was your favorite cartoon as a child?",
    "What city were you born in?"
];

$preference_questions = [
    "What type of pet do you prefer?",
    "What kind of place do you enjoy visiting most?",
    "What is your favorite season or type of weather?",
    "What do you enjoy doing in your free time?",
    "What type of books or movies do you prefer?"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .security-card {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .security-card h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
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
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn-primary {
            flex: 1;
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
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            flex: 1;
            padding: 12px;
            background-color: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        .btn-danger {
            flex: 1;
            padding: 12px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .existing-questions {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .existing-questions h3 {
            margin: 0 0 10px 0;
            color: #0056b3;
        }
        .existing-questions p {
            margin: 5px 0;
            color: #333;
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
    <div class="security-card">
        <?php if ($has_questions): ?>
            <h2>Your Security Questions</h2>
            <div class="existing-questions">
                <h3>Currently Set Questions:</h3>
                <p><strong>Question 1:</strong> <?php echo htmlspecialchars($existingQuestions['question_1']); ?></p>
                <p><strong>Question 2:</strong> <?php echo htmlspecialchars($existingQuestions['question_2']); ?></p>
                <p><strong>Question 3:</strong> <?php echo htmlspecialchars($existingQuestions['question_3']); ?></p>
            </div>
            <div class="btn-container">
                <form action="input_security_question.php" method="post" style="flex: 1;">
                    <input type="hidden" name="edit" value="1">
                    <button type="submit" class="btn-primary" style="width: 100%; margin: 0;">Edit</button>
                </form>
                <form action="delete_security.php" method="post" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete your security questions?');">
                    <button type="submit" class="btn-danger" style="width: 100%; margin: 0;">Delete</button>
                </form>
            </div>
        <?php else: ?>
            <h2>Set Your Security Questions</h2>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">
                Choose 3 security questions to help protect your account.
            </p>
            <form action="input_security_question.php" method="post">
                <div class="form-group">
                    <label for="personal">Personal Question:</label>
                    <select name="personal" id="personal" required>
                        <option value="">-- Select a question --</option>
                        <?php foreach ($personal_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="childhood">Childhood Question:</label>
                    <select name="childhood" id="childhood" required>
                        <option value="">-- Select a question --</option>
                        <?php foreach ($childhood_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="preference">Preference-based Question:</label>
                    <select name="preference" id="preference" required>
                        <option value="">-- Select a question --</option>
                        <?php foreach ($preference_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Next</button>
            </form>
        <?php endif; ?>
        <div class="back-link">
            <a href="../logform/indexes.php">← Back to Dashboard</a>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
</body>
</html>