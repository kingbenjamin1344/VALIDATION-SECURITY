<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}

$username = $_SESSION['username'];
$stmt = $conn->prepare("SELECT security_q1, security_q2, security_q3 FROM users WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$has_questions = !empty($user['security_q1']);

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
    <title>Set Security Questions</title>
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
        select {
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
        <?php if ($has_questions): ?>
            <h2>Your Security Questions</h2>
            <p><?php echo htmlspecialchars($user['security_q1']); ?></p>
            <p><?php echo htmlspecialchars($user['security_q2']); ?></p>
            <p><?php echo htmlspecialchars($user['security_q3']); ?></p>
            <form action="input_security_question.php" method="post" style="display:inline;">
                <input type="hidden" name="edit" value="1">
                <button type="submit">Edit</button>
            </form>
            <form action="delete_security.php" method="post" style="display:inline;">
                <button type="submit" onclick="return confirm('Are you sure you want to delete your security questions?')">Delete</button>
            </form>
        <?php else: ?>
            <h2>Select Your Security Questions</h2>
            <form action="input_security_question.php" method="post">
                <div class="form-group">
                    <label for="personal">Personal Question:</label>
                    <select name="personal" id="personal" required>
                        <option value="">Select a question</option>
                        <?php foreach ($personal_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="childhood">Childhood Question:</label>
                    <select name="childhood" id="childhood" required>
                        <option value="">Select a question</option>
                        <?php foreach ($childhood_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="preference">Preference-based Question:</label>
                    <select name="preference" id="preference" required>
                        <option value="">Select a question</option>
                        <?php foreach ($preference_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Next</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>