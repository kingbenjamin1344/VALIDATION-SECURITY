<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../logform/login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Questions</title>
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
                        <li><a href="../logform/login.php">Home</a></li>
                        <li><a href="../regform/register.php">Register</a></li>
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
                    <center><h2>Security Questions</h2></center>
                    <p>Please select one security question from each category.</p>

                    <form method="post" action="input_sec_question.php">
                        <div class="form-control">
                            <label>Personal Question</label>
                            <select name="question1" required>
                                <option value="">Select a question</option>
                                <option value="What is your favorite color?">What is your favorite color?</option>
                                <option value="What month were you born?">What month were you born?</option>
                                <option value="What type of music do you like most?">What type of music do you like most?</option>
                                <option value="What is your favorite food?">What is your favorite food?</option>
                                <option value="What is your favorite hobby?">What is your favorite hobby?</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label>Childhood Question</label>
                            <select name="question2" required>
                                <option value="">Select a question</option>
                                <option value="What is your favorite movie?">What is your favorite movie?</option>
                                <option value="What was your favorite subject in school?">What was your favorite subject in school?</option>
                                <option value="What was your favorite cartoon as a child?">What was your favorite cartoon as a child?</option>
                                <option value="What was your first school level?">What was your first school level?</option>
                                <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                            </select>
                        </div>
                        <div class="form-control">
                            <label>Preference-based Question</label>
                            <select name="question3" required>
                                <option value="">Select a question</option>
                                <option value="What type of pet do you prefer?">What type of pet do you prefer?</option>
                                <option value="What time of day do you feel most productive?">What time of day do you feel most productive?</option>
                                <option value="What type of weather do you like most?">What type of weather do you like most?</option>
                                <option value="What kind of place do you prefer to visit?">What kind of place do you prefer to visit?</option>
                                <option value="What do you enjoy more?">What do you enjoy more?</option>
                            </select>
                        </div>

                        <input type="submit" value="Proceed" class="btn btn-primary">
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