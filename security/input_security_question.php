<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['answers'])) {
    if (isset($_POST['edit'])) {
        // Load current questions
        $username = $_SESSION['username'];
        $stmt = $conn->prepare("SELECT security_q1, security_q2, security_q3 FROM users WHERE username=?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        $_SESSION['selected_questions'] = [
            'q1' => $user['security_q1'],
            'q2' => $user['security_q2'],
            'q3' => $user['security_q3']
        ];
    } else {
        $personal = $_POST['personal'];
        $childhood = $_POST['childhood'];
        $preference = $_POST['preference'];

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
    $questions = $_SESSION['selected_questions'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['answers'])) {
    $answers = $_POST['answers'];
    $username = $_SESSION['username'];

    // Hash the answers
    $hashed_a1 = password_hash($answers['a1'], PASSWORD_DEFAULT);
    $hashed_a2 = password_hash($answers['a2'], PASSWORD_DEFAULT);
    $hashed_a3 = password_hash($answers['a3'], PASSWORD_DEFAULT);

    // Update the database
    $stmt = $conn->prepare("UPDATE users SET security_q1=?, security_a1=?, security_q2=?, security_a2=?, security_q3=?, security_a3=? WHERE username=?");
    $stmt->bind_param('sssssss', $questions['q1'], $hashed_a1, $questions['q2'], $hashed_a2, $questions['q3'], $hashed_a3, $username);
    $stmt->execute();
    $stmt->close();

    unset($_SESSION['selected_questions']);
    header('Location: ../logform/indexes.php?success=1');
    exit();
}

$questions = $_SESSION['selected_questions'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Security Answers</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <style>
        .card {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Provide Answers to Your Security Questions</h2>
        <form action="input_security_question.php" method="post">
            <div class="form-group">
                <label><?php echo htmlspecialchars($questions['q1']); ?></label>
                <input type="text" name="answers[a1]" required>
            </div>
            <div class="form-group">
                <label><?php echo htmlspecialchars($questions['q2']); ?></label>
                <input type="text" name="answers[a2]" required>
            </div>
            <div class="form-group">
                <label><?php echo htmlspecialchars($questions['q3']); ?></label>
                <input type="text" name="answers[a3]" required>
            </div>
            <button type="submit">Save Security Questions</button>
        </form>
    </div>
</body>
</html>