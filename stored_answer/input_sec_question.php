<?php
session_start();
include("../regform/config.php");

if (!isset($_SESSION['user_id'])) {
    header('Location: ../logform/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['question1'])) {
    // This is the form to select questions, store in session and show inputs
    $_SESSION['question1'] = $_POST['question1'];
    $_SESSION['question2'] = $_POST['question2'];
    $_SESSION['question3'] = $_POST['question3'];
} elseif ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['answer1'])) {
    // This is the form to submit answers
    $answer1 = $_POST['answer1'];
    $answer2 = $_POST['answer2'];
    $answer3 = $_POST['answer3'];

    // Insert into DB
    $sql = "INSERT INTO user_security_questions (user_id, question1, answer1, question2, answer2, question3, answer3) VALUES ('$user_id', '{$_SESSION['question1']}', '$answer1', '{$_SESSION['question2']}', '$answer2', '{$_SESSION['question3']}', '$answer3')";
    if (mysqli_query($conn, $sql)) {
        unset($_SESSION['user_id']);
        unset($_SESSION['question1']);
        unset($_SESSION['question2']);
        unset($_SESSION['question3']);
        echo "<script>alert('Security questions set successfully!'); window.location.href='../logform/login.php';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Answer Security Questions</title>
    <link rel="stylesheet" href="../css/main.login.css">
</head>
<body>
    <div class="main-content">
        <!-- Navbar -->
        <div class="navbar">
            <div class="container flex">
                <h1 class="logo">Leave Management System</h1>
                <nav>
                    <ul>
                       
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Showcase -->
        <section class="showcase">
            <div class="container grid">
                <div class="showcase-text">
                    <h1>South Loan & Finance Company Inc.</h1>
                    <p>In this website we will accommodate South Loan & Finance Company Inc. employees' leave requests through a system.</p>
                </div>

                <div class="showcase-form card">
                    <center><h2>Answer Security Questions</h2></center>
                    <p>Please provide answers to the selected security questions.</p>

                    <form method="post">
                        <div class="form-control">
                            <label><?php echo $_SESSION['question1']; ?></label>
                            <input type="text" name="answer1" required>
                        </div>
                        <div class="form-control">
                            <label><?php echo $_SESSION['question2']; ?></label>
                            <input type="text" name="answer2" required>
                        </div>
                        <div class="form-control">
                            <label><?php echo $_SESSION['question3']; ?></label>
                            <input type="text" name="answer3" required>
                        </div>

                        <input type="submit" value="Save and Login" class="btn btn-primary">
                    </form>
                </div>
            </div>
        </section>
    </div>

    <div id="footer">
        <p>@South Loan & Finance Company Inc. All Right Reserve 2024</p>
    </div>
</body>
</html>