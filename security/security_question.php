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
    <title>Security Questions | Account Protection</title>
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

        .security-card {
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

        .security-card h2 {
            color: #1e293b;
            margin-bottom: 16px;
            text-align: center;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .security-card h2 i {
            color: #667eea;
            margin-right: 10px;
        }

        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 30px;
            font-size: 15px;
            line-height: 1.6;
        }

        /* Existing Questions Card */
        .existing-questions {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-left: 4px solid #667eea;
            padding: 24px;
            margin-bottom: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .existing-questions h3 {
            margin: 0 0 16px 0;
            color: #1e293b;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .existing-questions h3 i {
            color: #667eea;
        }

        .question-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .question-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .question-number {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            flex-shrink: 0;
        }

        .question-text {
            color: #334155;
            font-size: 14px;
            line-height: 1.5;
            flex: 1;
        }

        .question-text strong {
            color: #1e293b;
            display: block;
            margin-bottom: 4px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #1e293b;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group label i {
            color: #667eea;
            width: 20px;
        }

        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
            background: white;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%2364786b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
        }

        .form-group select:hover {
            border-color: #667eea;
        }

        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-group select option {
            padding: 12px;
        }

        /* Button Container */
        .btn-container {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        /* Buttons */
        .btn-primary {
            flex: 1;
            padding: 14px 24px;
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
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            flex: 1;
            padding: 14px 24px;
            background: #64748b;
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
            gap: 8px;
        }

        .btn-secondary:hover {
            background: #475569;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(71, 85, 105, 0.4);
        }

        .btn-danger {
            flex: 1;
            padding: 14px 24px;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            gap: 8px;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(239, 68, 68, 0.4);
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
            gap: 6px;
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

        /* Info Message */
        .info-message {
            background: #e8f0fe;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1e40af;
            font-size: 14px;
            border: 1px solid #bfdbfe;
        }

        .info-message i {
            font-size: 20px;
            color: #2563eb;
        }

        /* Loading State */
        .btn-loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .btn-loading i {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 640px) {
            .security-card {
                padding: 30px 20px;
            }

            .btn-container {
                flex-direction: column;
            }

            .question-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Success Animation */
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #10b981;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.3s ease;
        }

        .success-checkmark i {
            color: white;
            font-size: 40px;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <?php
    // This would typically be in your PHP block at the top
    // For demonstration, setting sample values if not defined
    $has_questions = false; // Set to true to test the "has questions" view
    $existingQuestions = [
        'question_1' => 'What was your first pet\'s name?',
        'question_2' => 'What elementary school did you attend?',
        'question_3' => 'What is your favorite book?'
    ];
    
    // Sample question arrays (these would come from your PHP)
    $personal_questions = [
        'What was your first pet\'s name?',
        'What was your mother\'s maiden name?',
        'What city were you born in?',
        'What is your father\'s middle name?'
    ];
    
    $childhood_questions = [
        'What was the name of your elementary school?',
        'What was your childhood nickname?',
        'What was your favorite childhood toy?',
        'What street did you grow up on?'
    ];
    
    $preference_questions = [
        'What is your favorite movie?',
        'What is your favorite food?',
        'What is your favorite book?',
        'Who is your favorite musician?'
    ];
    ?>

    <div class="security-card">
        <!-- Header -->
        <h2>
            <i class="fas fa-shield-alt"></i>
            <?php echo $has_questions ? 'Your Security Questions' : 'Set Security Questions'; ?>
        </h2>
        
        <?php if (!$has_questions): ?>
            <p class="subtitle">
                <i class="fas fa-info-circle" style="color: #667eea;"></i>
                Choose 3 security questions to help protect your account. These will be used to verify your identity if you forget your password.
            </p>
        <?php endif; ?>

        <?php if ($has_questions): ?>
            <!-- Existing Questions Display -->
            <div class="existing-questions">
                <h3>
                    <i class="fas fa-lock"></i>
                    Currently Set Questions
                </h3>
                <div class="question-list">
                    <div class="question-item">
                        <div class="question-number">1</div>
                        <div class="question-text">
                            <strong>Question 1:</strong>
                            <?php echo htmlspecialchars($existingQuestions['question_1']); ?>
                        </div>
                    </div>
                    <div class="question-item">
                        <div class="question-number">2</div>
                        <div class="question-text">
                            <strong>Question 2:</strong>
                            <?php echo htmlspecialchars($existingQuestions['question_2']); ?>
                        </div>
                    </div>
                    <div class="question-item">
                        <div class="question-number">3</div>
                        <div class="question-text">
                            <strong>Question 3:</strong>
                            <?php echo htmlspecialchars($existingQuestions['question_3']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="btn-container">
                <form action="input_security_question.php" method="post" style="flex: 1;">
                    <input type="hidden" name="edit" value="1">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-edit"></i>
                        Edit Questions
                    </button>
                </form>
                <form action="delete_security.php" method="post" style="flex: 1;" 
                      onsubmit="return confirmDelete(event);">
                    <button type="submit" class="btn-danger">
                        <i class="fas fa-trash-alt"></i>
                        Delete All
                    </button>
                </form>
            </div>

            <div class="info-message">
                <i class="fas fa-shield-alt"></i>
                <span>Your security questions help protect your account. Keep them memorable but secure.</span>
            </div>

        <?php else: ?>
            <!-- Question Selection Form -->
            <form action="input_security_question.php" method="post" id="securityForm">
                <div class="form-group">
                    <label for="personal">
                        <i class="fas fa-heart"></i>
                        Personal Question
                    </label>
                    <select name="personal" id="personal" required>
                        <option value="" disabled selected>-- Select a personal question --</option>
                        <?php foreach ($personal_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="childhood">
                        <i class="fas fa-child"></i>
                        Childhood Question
                    </label>
                    <select name="childhood" id="childhood" required>
                        <option value="" disabled selected>-- Select a childhood question --</option>
                        <?php foreach ($childhood_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="preference">
                        <i class="fas fa-star"></i>
                        Preference-based Question
                    </label>
                    <select name="preference" id="preference" required>
                        <option value="" disabled selected>-- Select a preference question --</option>
                        <?php foreach ($preference_questions as $q): ?>
                            <option value="<?php echo htmlspecialchars($q); ?>"><?php echo htmlspecialchars($q); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="btn-container">
                    <button type="submit" class="btn-primary" id="submitBtn">
                        <i class="fas fa-arrow-right"></i>
                        Continue
                    </button>
                    <button type="button" class="btn-secondary" onclick="window.location.href='../logform/indexes.php'">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <!-- Back Link -->
        <div class="back-link">
            <a href="../logform/indexes.php">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 1000;">
        <div class="modal-box" style="background: white; padding: 32px; border-radius: 24px; width: 380px; max-width: 90%; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); animation: modalSlide 0.3s ease;">
            <div class="modal-icon warning" style="width: 64px; height: 64px; border-radius: 50%; background: #fee2e2; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; font-size: 28px; color: #991b1b;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="font-size: 22px; font-weight: 600; color: #1e293b; margin-bottom: 8px; text-align: center;">Confirm Delete</h3>
            <p style="color: #64748b; text-align: center; margin-bottom: 24px; font-size: 15px; line-height: 1.5;">
                Are you sure you want to delete your security questions? This action cannot be undone.
            </p>
            <div class="modal-actions" style="display: flex; gap: 12px;">
                <button class="btn-modal cancel" onclick="closeDeleteModal()" style="flex: 1; padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; background: #f1f5f9; color: #475569;">Cancel</button>
                <button class="btn-modal confirm" id="confirmDeleteBtn" style="flex: 1; padding: 12px; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; background: #dc2626; color: white;">Yes, Delete</button>
            </div>
        </div>
    </div>

    <form id="deleteForm" action="delete_security.php" method="post" style="display: none;">
        <input type="hidden" name="confirm_delete" value="1">
    </form>

    <script src="../jsform/modal.js"></script>
    <script>
        // Form submission with loading state
        document.getElementById('securityForm')?.addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.classList.add('btn-loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner"></i> Processing...';
        });

        // Confirm delete with custom modal
        function confirmDelete(event) {
            event.preventDefault();
            document.getElementById('deleteConfirmModal').style.display = 'flex';
            return false;
        }

        function closeDeleteModal() {
            document.getElementById('deleteConfirmModal').style.display = 'none';
        }

        // Handle delete confirmation
        document.getElementById('confirmDeleteBtn')?.addEventListener('click', function() {
            document.getElementById('deleteForm').submit();
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteConfirmModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Escape key to close modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Prevent duplicate submissions
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function() {
                const buttons = this.querySelectorAll('button[type="submit"]');
                buttons.forEach(button => {
                    button.disabled = true;
                });
            });
        });

        // Show success message if coming from edit
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('updated') === '1') {
            showSuccessMessage('Security questions updated successfully!');
        } else if (urlParams.get('saved') === '1') {
            showSuccessMessage('Security questions saved successfully!');
        }

        function showSuccessMessage(message) {
            const alert = document.createElement('div');
            alert.className = 'success-message';
            alert.innerHTML = `
                <div class="success-checkmark">
                    <i class="fas fa-check"></i>
                </div>
                <p>${message}</p>
            `;
            alert.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 20px;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                z-index: 9999;
                animation: slideInRight 0.3s ease;
                text-align: center;
                min-width: 280px;
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        }

        // Add animation keyframes
        const style = document.createElement('style');
        style.textContent = `
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
        document.head.appendChild(style);
    </script>
</body>
</html>