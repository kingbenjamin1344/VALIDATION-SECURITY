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

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['answers'])) {
    if (isset($_POST['edit'])) {
        // Load current questions
        $stmt = $conn->prepare("SELECT question_1, question_2, question_3 FROM security_questions WHERE user_id=?");
        $stmt->bind_param('i', $user_id);
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
        // Check if security questions already exist for user
        $checkStmt = $conn->prepare("SELECT id FROM security_questions WHERE user_id=?");
        $checkStmt->bind_param('i', $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE security_questions SET question_1=?, answer_1=?, question_2=?, answer_2=?, question_3=?, answer_3=? WHERE user_id=?");
            $stmt->bind_param('ssssssi', 
                $questions['q1'], $answer1, 
                $questions['q2'], $answer2, 
                $questions['q3'], $answer3, 
                $user_id
            );
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO security_questions (user_id, question_1, answer_1, question_2, answer_2, question_3, answer_3) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('issssss', 
                $user_id,
                $questions['q1'], $answer1, 
                $questions['q2'], $answer2, 
                $questions['q3'], $answer3
            );
        }
        
        if ($stmt->execute()) {
            unset($_SESSION['selected_questions']);
            $message = 'Security questions saved successfully!';
            $message_type = 'success';
            $show_success_modal = true;
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
    <title>Security Answers | Account Protection</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="../css/modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.05" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,170.7C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.1;
            pointer-events: none;
        }

        .answers-card {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease;
            position: relative;
            z-index: 1;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .answers-card h2 {
            color: #1e293b;
            margin-bottom: 12px;
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .answers-card h2 i {
            color: #667eea;
            margin-right: 10px;
        }

        .answers-card p {
            text-align: center;
            color: #64748b;
            margin-bottom: 24px;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, #e8f0fe 0%, #dbeafe 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 16px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .info-box i {
            font-size: 24px;
            color: #667eea;
            background: white;
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
        }

        .info-box .info-content {
            flex: 1;
        }

        .info-box .info-content strong {
            display: block;
            color: #1e293b;
            font-size: 16px;
            margin-bottom: 6px;
        }

        .info-box .info-content p {
            margin: 0;
            text-align: left;
            color: #475569;
            font-size: 14px;
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .alert i {
            font-size: 20px;
        }

        .alert-success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffe69c;
            color: #856404;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Question Cards */
        .question-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .question-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            border-color: #667eea;
        }

        .question-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .question-number {
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .question-text {
            flex: 1;
            font-weight: 500;
            color: #1e293b;
            font-size: 15px;
        }

        .question-icon {
            color: #667eea;
            font-size: 18px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #475569;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label i {
            color: #667eea;
            width: 18px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: #94a3b8;
            font-size: 16px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: white;
        }

        .form-group input:hover {
            border-color: #94a3b8;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group input::placeholder {
            color: #94a3b8;
            font-size: 14px;
        }

        /* Password strength indicator (optional) */
        .strength-meter {
            margin-top: 8px;
            height: 4px;
            background: #e2e8f0;
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-meter-fill {
            height: 100%;
            width: 0;
            transition: width 0.3s;
        }

        .strength-meter-fill.weak { background: #ef4444; width: 33.33%; }
        .strength-meter-fill.medium { background: #f59e0b; width: 66.66%; }
        .strength-meter-fill.strong { background: #10b981; width: 100%; }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            font-size: 18px;
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .back-link a {
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .back-link a i {
            transition: transform 0.2s;
        }

        .back-link a:hover {
            color: #667eea;
        }

        .back-link a:hover i {
            transform: translateX(-4px);
        }

        /* Success Modal - Enhanced */
        .success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            z-index: 3000;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .success-modal {
            background: white;
            padding: 32px;
            border-radius: 24px;
            max-width: 420px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalScale 0.3s ease;
        }

        @keyframes modalScale {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #10b981;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            animation: successPulse 2s infinite;
        }

        @keyframes successPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }
            70% {
                box-shadow: 0 0 0 15px rgba(16, 185, 129, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .success-modal h3 {
            margin: 0 0 12px;
            color: #1e293b;
            font-size: 24px;
            font-weight: 700;
        }

        .success-modal p {
            margin: 0 0 24px;
            color: #64748b;
            font-size: 15px;
            line-height: 1.6;
        }

        .ok-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .ok-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
        }

        .ok-btn i {
            font-size: 18px;
        }

        /* Loading State */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .answers-card {
                padding: 30px 20px;
            }

            .info-box {
                flex-direction: column;
                align-items: flex-start;
            }

            .question-header {
                flex-wrap: wrap;
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
            margin-left: 8px;
            color: #94a3b8;
            cursor: help;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        .tooltip-text {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #1e293b;
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            transition: all 0.2s;
            z-index: 10;
            pointer-events: none;
        }

        .tooltip-text::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: #1e293b transparent transparent transparent;
        }
    </style>
</head>
<body>
    <div class="answers-card">
        <h2>
            <i class="fas fa-shield-alt"></i>
            Security Answers
        </h2>
        <p>Provide answers that only you would know. These answers are case-insensitive but must be exact matches.</p>
        
        <!-- Enhanced Info Box -->
        <div class="info-box">
            <i class="fas fa-lightbulb"></i>
            <div class="info-content">
                <strong>Tips for creating secure answers:</strong>
                <p>• Use answers that are easy to remember but hard to guess<br>
                   • Avoid using public information like your birthday<br>
                   • Be consistent with capitalization and spelling</p>
            </div>
        </div>
        
        <?php if (isset($message) && $message): ?>
            <div class="alert alert-<?php echo $message_type ?? 'warning'; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form action="input_security_question.php" method="post" id="answersForm">
            <!-- Question 1 -->
            <div class="question-card">
                <div class="question-header">
                    <div class="question-number">1</div>
                    <div class="question-text"><?php echo htmlspecialchars($questions['q1'] ?? "What was your first pet's name?"); ?></div>
                    <div class="tooltip">
                        <i class="fas fa-question-circle"></i>
                        <span class="tooltip-text">This should be a name you remember well</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-paw"></i>
                        Your Answer
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="text" name="answers[a1]" required placeholder="Enter your answer" 
                               pattern=".*\S.*" title="Please enter a valid answer">
                    </div>
                    <div class="strength-meter">
                        <div class="strength-meter-fill" id="strength1"></div>
                    </div>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="question-card">
                <div class="question-header">
                    <div class="question-number">2</div>
                    <div class="question-text"><?php echo htmlspecialchars($questions['q2'] ?? 'What elementary school did you attend?'); ?></div>
                    <div class="tooltip">
                        <i class="fas fa-question-circle"></i>
                        <span class="tooltip-text">Use the full school name for consistency</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-school"></i>
                        Your Answer
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="text" name="answers[a2]" required placeholder="Enter your answer"
                               pattern=".*\S.*" title="Please enter a valid answer">
                    </div>
                    <div class="strength-meter">
                        <div class="strength-meter-fill" id="strength2"></div>
                    </div>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="question-card">
                <div class="question-header">
                    <div class="question-number">3</div>
                    <div class="question-text"><?php echo htmlspecialchars($questions['q3'] ?? 'What is your favorite book?'); ?></div>
                    <div class="tooltip">
                        <i class="fas fa-question-circle"></i>
                        <span class="tooltip-text">Include the full title for best results</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <i class="fas fa-book"></i>
                        Your Answer
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="text" name="answers[a3]" required placeholder="Enter your answer"
                               pattern=".*\S.*" title="Please enter a valid answer">
                    </div>
                    <div class="strength-meter">
                        <div class="strength-meter-fill" id="strength3"></div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-save"></i>
                <span>Save Security Answers</span>
            </button>
        </form>
        
        <div class="back-link">
            <a href="security_question.php">
                <i class="fas fa-arrow-left"></i>
                Back to Security Questions
            </a>
        </div>
    </div>

    <!-- Enhanced Success Modal -->
    <div id="successOverlay" class="success-overlay">
        <div class="success-modal" role="dialog" aria-modal="true" aria-labelledby="successTitle">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h3 id="successTitle">Successfully Saved!</h3>
            <p>Your security questions have been saved. These will be used to verify your identity if you need to reset your password.</p>
            <button id="successOk" class="ok-btn">
                <i class="fas fa-check-circle"></i>
                Got it, thanks!
            </button>
        </div>
    </div>

    <script src="../jsform/modal.js"></script>
    <script>
        (function() {
            // Success modal handling
            var show = <?php echo isset($show_success_modal) && $show_success_modal ? 'true' : 'false'; ?>;
            if (show) {
                var overlay = document.getElementById('successOverlay');
                var btn = document.getElementById('successOk');
                
                if (overlay && btn) {
                    overlay.style.display = 'flex';
                    btn.focus();
                    
                    function redirect() {
                        window.location.href = 'manage_security.php';
                    }
                    
                    btn.addEventListener('click', redirect);
                    
                    // Allow Enter/Escape
                    document.addEventListener('keydown', function(e) {
                        if (!overlay || overlay.style.display !== 'flex') return;
                        if (e.key === 'Enter' || e.key === 'Escape') {
                            redirect();
                        }
                    });
                }
            }

            // Form submission handling with loading state
            const form = document.getElementById('answersForm');
            const submitBtn = document.getElementById('submitBtn');

            if (form) {
                form.addEventListener('submit', function(e) {
                    // Validate all fields are filled
                    const inputs = this.querySelectorAll('input[required]');
                    let isValid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            isValid = false;
                            input.style.borderColor = '#ef4444';
                            
                            // Add shake animation
                            input.style.animation = 'shake 0.5s';
                            setTimeout(() => {
                                input.style.animation = '';
                            }, 500);
                        } else {
                            input.style.borderColor = '#10b981';
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        showNotification('Please fill in all answers', 'error');
                        return;
                    }

                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner loading-spinner"></i> Saving...';
                });
            }

            // Real-time validation and strength meter
            const inputs = document.querySelectorAll('input[type="text"]');
            inputs.forEach((input, index) => {
                input.addEventListener('input', function() {
                    const value = this.value.trim();
                    const strengthMeter = document.getElementById(`strength${index + 1}`);
                    
                    if (strengthMeter) {
                        if (value.length === 0) {
                            strengthMeter.className = 'strength-meter-fill';
                        } else if (value.length < 4) {
                            strengthMeter.className = 'strength-meter-fill weak';
                        } else if (value.length < 8) {
                            strengthMeter.className = 'strength-meter-fill medium';
                        } else {
                            strengthMeter.className = 'strength-meter-fill strong';
                        }
                    }
                    
                    // Visual feedback
                    if (value.length > 0) {
                        this.style.borderColor = '#10b981';
                    } else {
                        this.style.borderColor = '#e2e8f0';
                    }
                });
            });

            // Shake animation for error
            const style = document.createElement('style');
            style.textContent = `
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
                    20%, 40%, 60%, 80% { transform: translateX(4px); }
                }
            `;
            document.head.appendChild(style);

            // Custom notification function
            function showNotification(message, type = 'error') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type}`;
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.zIndex = '9999';
                notification.style.minWidth = '300px';
                notification.style.animation = 'slideInRight 0.3s ease';
                
                notification.innerHTML = `
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                    <span>${message}</span>
                `;
                
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOutRight 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }

            // Add slide animations
            const slideStyles = document.createElement('style');
            slideStyles.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes slideOutRight {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(slideStyles);

            // Prevent form resubmission on page refresh
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }

            // Auto-focus first input
            const firstInput = document.querySelector('input[name="answers[a1]"]');
            if (firstInput) {
                firstInput.focus();
            }

            // Warn before leaving if form has unsaved changes
            let formChanged = false;
            
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    formChanged = true;
                });
            });

            window.addEventListener('beforeunload', function(e) {
                if (formChanged && !submitBtn.disabled) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Clear warning on submit
            form.addEventListener('submit', () => {
                formChanged = false;
            });

        })();
    </script>
</body>
</html>