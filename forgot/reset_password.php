<?php
session_start();
require_once '../regform/config.php';

// Check if either OTP verified or both OTP and Security verified
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header('Location: forgot_password.php');
    exit();
}

$email = $_SESSION['reset_email'];
$message = '';
$message_type = '';
$show_success_modal = false;
$modal_title = '';
$modal_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $message = 'Please fill in all password fields.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'error';
    } else {
        // Update password (support admin accounts)
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $account_type = $_SESSION['reset_account_type'] ?? 'user';
        if ($account_type === 'admin') {
            $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE email = ?");
        } else {
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        }
        $stmt->bind_param('ss', $hashed_password, $email);
        
        if ($stmt->execute()) {
            // Clean up password reset records
            $conn->query("DELETE FROM password_resets WHERE email = '$email'");
            
            // Clean up session
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);
            unset($_SESSION['security_verified']);
            unset($_SESSION['sec_attempts']);
            // Do not destroy session yet so we can show success modal on this page
            $message = 'Password reset successfully!';
            $message_type = 'success';
            // Show modal to allow user to click to login
            $show_success_modal = true;
            $modal_title = 'Password Reset Successful';
            $modal_message = 'Your password has been reset successfully. Click the button to go to login.';
        } else {
            $message = 'Failed to reset password. Please try again.';
            $message_type = 'error';
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../css/main.login.css">
    <link rel="stylesheet" href="../css/modal.css">
    <style>
        .reset-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .reset-container h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        .reset-info {
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
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border-color 0.3s, background-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .form-group input.password-weak {
            border-color: #dc3545;
            background-color: #fff5f5;
        }
        .form-group input.password-medium {
            border-color: #ffc107;
            background-color: #fffdf5;
        }
        .form-group input.password-strong {
            border-color: #28a745;
            background-color: #f6fff6;
        }
        .pw-message { margin-top: 8px; font-size: 14px; }
        .pw-message.weak { color: #dc3545; }
        .pw-message.moderate { color: #ffc107; }
        .pw-message.strong { color: #28a745; }
        /* Removed visual password-strength classes and requirements box per request */
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
    </style>
</head>
<body>
    <div class="reset-container">
        <h2>Reset Password</h2>
        <div class="reset-info">
            <p>Enter your new password below.</p>
        </div>
        
        <?php if ($message && empty($show_success_modal)): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <!-- Password requirements removed; client-side validation may be supplied separately -->
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input 
                    type="password" 
                    name="new_password" 
                    id="new_password" 
                    required 
                    minlength="8"
                    placeholder="Enter new password"
                >
                <div id="pwStrengthMessage" class="pw-message"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input 
                    type="password" 
                    name="confirm_password" 
                    id="confirm_password" 
                    required 
                    minlength="8"
                    placeholder="Confirm password"
                >
                <div id="pwMatchMessage" class="pw-message"></div>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">Reset Password</button>
        </form>
        
        <div class="back-link">
            <a href="forgot_password.php">← Start Over</a>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="successModalTitle"></h2>
            </div>
            <div class="modal-body">
                <p id="successModalMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-primary" id="gotoLoginBtn">Go to Login</button>
            </div>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
    <script>
        // Password strength and match indicator (moved out of modal)
        (function(){
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const submitBtn = document.getElementById('submitBtn');
            const pwStrengthMessage = document.getElementById('pwStrengthMessage');
            const pwMatchMessage = document.getElementById('pwMatchMessage');

            function evaluatePassword() {
                const password = newPasswordInput.value;
                const reenterpassword = confirmPasswordInput.value;

                if (password.length === 0) {
                    pwStrengthMessage.textContent = '';
                    newPasswordInput.classList.remove('password-weak','password-medium','password-strong');
                } else if (password.length < 8) {
                    pwStrengthMessage.textContent = 'Your password is weak';
                    pwStrengthMessage.className = 'pw-message weak';
                    newPasswordInput.classList.add('password-weak');
                    newPasswordInput.classList.remove('password-medium','password-strong');
                } else {
                    const hasNumber = /\d/.test(password);
                    const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);
                    if (hasNumber && hasSymbol) {
                        pwStrengthMessage.textContent = 'Your password is strong';
                        pwStrengthMessage.className = 'pw-message strong';
                        newPasswordInput.classList.add('password-strong');
                        newPasswordInput.classList.remove('password-weak','password-medium');
                    } else {
                        pwStrengthMessage.textContent = 'Your password is moderate';
                        pwStrengthMessage.className = 'pw-message moderate';
                        newPasswordInput.classList.add('password-medium');
                        newPasswordInput.classList.remove('password-weak','password-strong');
                    }
                }

                // Match check
                if (reenterpassword.length === 0) {
                    pwMatchMessage.textContent = '';
                    pwMatchMessage.className = 'pw-message';
                } else if (password === reenterpassword) {
                    pwMatchMessage.textContent = 'Password matched.';
                    pwMatchMessage.className = 'pw-message strong';
                    submitBtn.disabled = false;
                } else {
                    pwMatchMessage.textContent = 'Password not matched';
                    pwMatchMessage.className = 'pw-message weak';
                    submitBtn.disabled = true;
                }

                // If both non-empty and matched, ensure submit enabled
                if (password.length > 0 && reenterpassword.length > 0 && password === reenterpassword) {
                    submitBtn.disabled = false;
                }
            }

            newPasswordInput.addEventListener('input', evaluatePassword);
            confirmPasswordInput.addEventListener('input', evaluatePassword);
            evaluatePassword();
        })();
    </script>
    <script>
        // Show success modal if password was reset
        <?php if (!empty($show_success_modal)): ?>
            const successModal = new Modal('successModal');
            successModal.show('<?php echo addslashes($modal_title); ?>', '<?php echo addslashes($modal_message); ?>', [
                {
                    text: 'Go to Login',
                    class: 'btn-primary',
                    onclick: function(){ window.location.href = '../logform/login.php'; }
                }
            ]);
        <?php endif; ?>

        // Attach button handler to redirect to login
        (function(){
            const gotoLoginBtn = document.getElementById('gotoLoginBtn');
            if (gotoLoginBtn) {
                gotoLoginBtn.addEventListener('click', () => {
                    window.location.href = '../logform/login.php';
                });
            }
        })();
    </script>
</body>
</html>