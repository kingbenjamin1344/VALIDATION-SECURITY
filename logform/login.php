<?php
session_start();
require_once '../regform/config.php'; // Assuming you have the database configuration here




// Set your desired countdown durations
$countdown_durations = [15, 30, 60]; // Durations for each failed attempt set



// Check if the login form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Check if cooldown is active
    if (isset($_SESSION['cooldown_start_time'])) {
        $remainingCooldown = $_SESSION['cooldown_start_time'] - time();
        if ($remainingCooldown > 0) {
            $_SESSION['error_message'] = "Please try again after the countdown!";
            header('Location: login.php');
            exit();
        } else {
            // Cooldown period is over, reset login attempts and cooldown session
            unset($_SESSION['login_attempts']);
            unset($_SESSION['cooldown_start_time']);
            unset($_SESSION['error_message']); // Clear error message after cooldown
        }
    }

    // If cooldown is not active, check login attempts
    if (!isset($_SESSION['login_attempts']) || $_SESSION['login_attempts'] > 0) {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username=?");
        if ($stmt === false) {
            die('Error preparing statement: ' . $conn->error);
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            die('Error executing statement: ' . $stmt->error);
        }

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row["password"])) {
                $_SESSION["username"] = $username;
                resetLoginAttempts(true);

                // Redirect to a different page after successful login
                header('Location: indexes.php');
                exit();
            } else {
                handleFailedLogin();
            }
        } else {
            handleFailedLogin();
        }

        $stmt->close();
    }

    // Redirect to prevent form resubmission
    header('Location: login.php');
    exit();
}

// Function to handle failed login
function handleFailedLogin() {
    global $countdown_durations;

    // Set the error message for incorrect login attempt
    $_SESSION['error_message'] = "Incorrect username or password.";

    // Ensure login_attempts is set
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 2; // Start with 2 remaining attempts
    } else {
        $_SESSION['login_attempts']--;

        // Check if attempts have run out
        if ($_SESSION['login_attempts'] <= 0) {
            $attempt_set = isset($_SESSION['attempt_set']) ? $_SESSION['attempt_set'] : 1;

            // Use the correct countdown duration
            $countdown_duration = $countdown_durations[min($attempt_set - 1, 2)];
            $_SESSION['cooldown_start_time'] = time() + $countdown_duration;
            $_SESSION['remaining_countdown'] = $countdown_duration;
            $_SESSION['login_attempts'] = 3; // Reset login attempts after the cooldown
            $_SESSION['attempt_set'] = $attempt_set + 1;
        }
    }
}

// Function to reset login attempts
function resetLoginAttempts($successfulLogin = false) {
    if ($successfulLogin) {
        $_SESSION['login_attempts'] = 3;
        $_SESSION['attempt_set'] = 1;
        unset($_SESSION['cooldown_start_time']);
    }
}

// Disable back navigation
echo '<script>
    document.addEventListener("DOMContentLoaded", function () {
        disableBackNavigation();
    });

    function disableBackNavigation() {
        history.pushState(null, null, document.URL);
        window.addEventListener("popstate", function () {
            history.pushState(null, null, document.URL);
        });
    }
</script>';
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../css/main.login.css">
    
</head>
<body>
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
                <center><h2>User Account</h2></center>

                <!-- Display error message at the top of the form if it exists -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div id="error_message" style="color: red; text-align: center; margin-bottom: 10px;">
                        <?php echo $_SESSION['error_message']; ?>
                        <?php unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <form name="myform" method="post" autocomplete="off" id="loginForm" onsubmit="return validateForm()">
                    <div class="form-control">
                        <input type="text" name="username" placeholder="Username"  oninput="return clearErrorMessage()">
                        <span id="usernameerrormsg" style="color:red;"></span>
                    </div>
                    <div class="form-control">
                        <input type="password" name="password" placeholder="Password" >
                    </div>

                    <input type="submit" value="Login" class="btn btn-primary" 
                      <?php echo (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] <= 0) ? 'disabled' : ''; ?>>

                    <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] <= 1): ?>
                        <p style="text-align: center;">Forgot Password? <a href="../forgotpass/forgot.php">Click here</a></p>
                    <?php endif; ?>

                </form>

                <!-- Countdown timer -->
                <div id="cooldown-timer" style="text-align: center; color: red;"></div>
            </div>
        </div>
    </section>

    <div id="footer">
        <p>@South Loan & Finance Company Inc. All Right Reserve 2024</p>
    </div>

    

    <script>
        


       document.addEventListener("DOMContentLoaded", function () {
    var remainingTime = <?php echo isset($_SESSION['cooldown_start_time']) ? max(0, $_SESSION['cooldown_start_time'] - time()) : 0; ?>;
    var countdownElement = document.getElementById('cooldown-timer');
    var errorMessage = document.getElementById('error_message');

    if (remainingTime > 0) {
        countdownElement.innerHTML = "You are blocked for: " + remainingTime + " seconds.";
        disableForm();

        var countdownInterval = setInterval(function () {
            if (remainingTime > 0) {
                remainingTime--;
                countdownElement.innerHTML = "You are blocked for: " + remainingTime + " seconds.";
            } else {
                clearInterval(countdownInterval); // Stop the countdown
                countdownElement.innerHTML = "";  // Clear the countdown text
                enableForm();

                // Hide the error message after the countdown ends
                if (errorMessage) {
                    errorMessage.style.display = "none";
                }
            }
        }, 1000);
    }

    // Add event listeners to inputs to clear the error message on typing
    var inputs = document.querySelectorAll('#loginForm input');
    inputs.forEach(function (input) {
        input.addEventListener('input', function () {
            if (errorMessage) {
                errorMessage.style.display = "none"; // Hide the error message on input
            }
        });
    });
});

function disableForm() {
    document.getElementById("loginForm").querySelectorAll("input").forEach(input => {
        input.disabled = true;
    });
}

function enableForm() {
    document.getElementById("loginForm").querySelectorAll("input").forEach(input => {
        input.disabled = false;
    });
}

    </script>   
   <script src="../regform/script.php?dir=jsform&file=login.js" defer></script>
</body>
</html>
