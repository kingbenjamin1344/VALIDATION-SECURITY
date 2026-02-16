<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$countdown_durations = [15, 30, 60];

// Functions for attempt handling
function handleFailedLogin()
{
    global $countdown_durations;
    $_SESSION['error_message'] = 'Invalid username or password.';

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 2;
    } else {
        $_SESSION['login_attempts']--;

        if ($_SESSION['login_attempts'] <= 0) {
            $attempt_set = isset($_SESSION['attempt_set']) ? $_SESSION['attempt_set'] : 1;
            $idx = min($attempt_set - 1, count($countdown_durations) - 1);
            $countdown_duration = $countdown_durations[$idx];
            $_SESSION['cooldown_start_time'] = time() + $countdown_duration;
            $_SESSION['remaining_countdown'] = $countdown_duration;
            $_SESSION['login_attempts'] = 3; // reset after cooldown
            $_SESSION['attempt_set'] = $attempt_set + 1;
        }
    }
}

function resetLoginAttempts($successfulLogin = false)
{
    if ($successfulLogin) {
        $_SESSION['login_attempts'] = 3;
        $_SESSION['attempt_set'] = 1;
        unset($_SESSION['cooldown_start_time']);
    }
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if cooldown is active
    if (isset($_SESSION['cooldown_start_time'])) {
        $remainingCooldown = $_SESSION['cooldown_start_time'] - time();
        if ($remainingCooldown > 0) {
            $_SESSION['error_message'] = 'Please try again after the countdown!';
            header('Location: login.php');
            exit();
        } else {
            unset($_SESSION['login_attempts']);
            unset($_SESSION['cooldown_start_time']);
            unset($_SESSION['error_message']);
        }
    }

    if ($username === '' || $password === '') {
        $_SESSION['error_message'] = 'Please enter username and password.';
    } else {
        // Only attempt login if attempts remain (or not set yet)
        if (!isset($_SESSION['login_attempts']) || $_SESSION['login_attempts'] > 0) {
            $stmt = mysqli_prepare($conn, "SELECT id, fullname, username, password FROM admins WHERE username = ? LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $username);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_store_result($stmt);
                mysqli_stmt_bind_result($stmt, $id, $fullname, $user, $hash);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hash)) {
                        $_SESSION['admin'] = ['id' => $id, 'fullname' => $fullname, 'username' => $user];
                        $device = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                        $uname = $user;
                        if (function_exists('insert_activity')) insert_activity($conn, $uname, 'admin', 'login', $device);
                        resetLoginAttempts(true);
                        mysqli_stmt_close($stmt);
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        handleFailedLogin();
                    }
                } else {
                    handleFailedLogin();
                }
                mysqli_stmt_close($stmt);
            } else {
                $_SESSION['error_message'] = 'Server error. Please try again later.';
            }
        }
    }

    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../css/main.login.css">
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        history.pushState(null, null, document.URL);
        window.addEventListener("popstate", function () { history.pushState(null, null, document.URL); });
    });
    </script>
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container flex">
            <h1 class="logo">Leave Management System</h1>
            <nav>
                <ul>
                    <li><a href="../superadmin/login.php">Super Admin</a></li>
                    <li><a href="../logform/login.php">User</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Showcase -->
    <section class="showcase">
        <div class="container grid">

            <div class="showcase-text">
                <h1>South Loan & Finance Company Inc.</h1>
                <p>
                    In this website we will accommodate South Loan & Finance Company Inc.
                    employees' leave requests through a system.
                </p>
            </div>

            <div class="showcase-form card">
                <center><h2>Admin Account</h2></center>

                <!-- Display error message at the top of the form if it exists -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div id="error_message" style="color: red; text-align: center; margin-bottom: 10px;">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>

                <form name="myform" method="post" autocomplete="off" id="loginForm" onsubmit="return validateForm()">
                    <div class="form-control">
                        <input type="text" name="username" placeholder="Username" oninput="return clearErrorMessage()">
                        <span id="usernameerrormsg" style="color:red;"></span>
                    </div>
                    <div class="form-control">
                        <input type="password" name="password" placeholder="Password" >
                    </div>

                    <input type="submit" value="Login" class="btn btn-primary" 
                      <?php echo (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] <= 0) ? 'disabled' : ''; ?>>

                    <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] <= 1): ?>
                        <p style="text-align: center;">Forgot Password? <a href="../forgot/forgot_password.php">Click here</a></p>
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
