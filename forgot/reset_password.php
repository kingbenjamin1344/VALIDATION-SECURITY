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
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
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
        .form-group input.password-weak {
            border-color: #dc3545;
            background-color: #ffe5e5;
        }
        .form-group input.password-medium {
            border-color: #ffc107;
            background-color: #fffae5;
        }
        .form-group input.password-strong {
            border-color: #28a745;
            background-color: #e5ffe5;
        }
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        .password-requirements {
            background-color: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
            color: #333;
        }
        .password-requirements ul {
            margin: 5px 0 0 0;
            padding-left: 20px;
        }
        .password-requirements li {
            margin: 5px 0;
            transition: color 0.3s;
        }
        .password-requirements li span {
            margin-right: 8px;
            font-weight: bold;
            display: inline-block;
            width: 20px;
            color: #999;
            transition: color 0.3s;
        }
        .password-requirements li.checked span {
            color: #28a745;
        }
        .password-requirements li.checked {
            color: #28a745;
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
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="password-requirements">
                <strong>Password Requirements:</strong>
                <ul>
                    <li><span id="reqLength">●</span> Minimum 8 characters</li>
                    <li><span id="reqUpperCase">●</span> Uppercase letter</li>
                    <li><span id="reqNumber">●</span> Number</li>
                    <li><span id="reqSymbol">●</span> Special character</li>
                    <li><span id="reqMatch">●</span> Passwords must match</li>
                </ul>
            </div>
            
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
                <h2 id="successModalTitle"><?php echo $modal_title; ?></h2>
            </div>
            <div class="modal-body">
                <p id="successModalMessage"><?php echo $modal_message; ?></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-primary" id="gotoLoginBtn">Go to Login</button>
            </div>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
    <script>
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const submitBtn = document.getElementById('submitBtn');
        
        // Requirement elements
        const reqLength = document.getElementById('reqLength');
        const reqUpperCase = document.getElementById('reqUpperCase');
        const reqNumber = document.getElementById('reqNumber');
        const reqSymbol = document.getElementById('reqSymbol');
        const reqMatch = document.getElementById('reqMatch');
        
        function updatePasswordStrength() {
            const password = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
            const matches = password === confirmPassword && password.length > 0;
            
            // Update requirement indicators
            updateRequirement(reqLength, hasLength);
            updateRequirement(reqUpperCase, hasUpperCase);
            updateRequirement(reqNumber, hasNumber);
            updateRequirement(reqSymbol, hasSymbol);
            updateRequirement(reqMatch, matches);
            
            // Update password field color (use classList so existing classes not clobbered)
            newPasswordInput.classList.remove('password-weak', 'password-medium', 'password-strong');
            if (password.length === 0) {
                // no class
            } else if (!hasLength) {
                newPasswordInput.classList.add('password-weak');
            } else if (hasLength && !hasUpperCase && !hasNumber && !hasSymbol) {
                newPasswordInput.classList.add('password-weak');
            } else if (hasLength && (hasUpperCase || hasNumber || hasSymbol)) {
                newPasswordInput.classList.add('password-medium');
            } else if (hasLength && hasUpperCase && hasNumber && hasSymbol) {
                newPasswordInput.classList.add('password-strong');
            }

            // Update confirm password field color
            confirmPasswordInput.classList.remove('password-weak', 'password-medium', 'password-strong');
            if (confirmPassword.length === 0) {
                // no class
            } else if (matches) {
                confirmPasswordInput.classList.add('password-strong');
            } else {
                confirmPasswordInput.classList.add('password-weak');
            }
        }
        
        function updateRequirement(element, isMet) {
            const li = element.parentElement;
            if (isMet) {
                li.classList.add('checked');
                element.textContent = '✓';
            } else {
                li.classList.remove('checked');
                element.textContent = '●';
            }
        }
        
        // Listen for input changes
        newPasswordInput.addEventListener('input', updatePasswordStrength);
        confirmPasswordInput.addEventListener('input', updatePasswordStrength);
    </script>
    <script>
        // Show success modal if password was reset
        <?php if (!empty($show_success_modal)): ?>
            const successModal = new Modal('successModal');
            successModal.show('<?php echo addslashes($modal_title); ?>', '<?php echo addslashes($modal_message); ?>', [
                {
                    text: 'Go to Login',
                    class: 'btn-primary',
                    onclick: () => { window.location.href = '../logform/login.php'; }
                }
            ]);
        <?php endif; ?>
        
        // Also attach button in markup if JS needs to handle
        const gotoLoginBtn = document.getElementById('gotoLoginBtn');
        if (gotoLoginBtn) {
            gotoLoginBtn.addEventListener('click', () => {
                window.location.href = '../logform/login.php';
            });
        }
    </script>
</body>
</html>