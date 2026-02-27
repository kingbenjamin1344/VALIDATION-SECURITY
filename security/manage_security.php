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
$user_id = $userRow['id'] ?? null;

if (!$user_id) {
    header('Location: ../logform/indexes.php');
    exit();
}

// Fetch stored security questions and answers
$stmt = $conn->prepare("SELECT question_1, answer_1, question_2, answer_2, question_3, answer_3 FROM security_questions WHERE user_id = ? LIMIT 1");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

// If no stored security questions, we'll show a modal prompting the user
$hasSecurity = true;
if (!$row) {
    $hasSecurity = false;
    // ensure variables exist for template
    $q1 = $q2 = $q3 = '';
    $a1 = $a2 = $a3 = '';
} else {
    $q1 = $row['question_1'];
    $q2 = $row['question_2'];
    $q3 = $row['question_3'];
    $a1 = $row['answer_1'];
    $a2 = $row['answer_2'];
    $a3 = $row['answer_3'];
}

function mask_answer($a) {
    $a = (string)$a;
    $len = mb_strlen($a);
    if ($len === 0) return '';
    if ($len <= 2) return str_repeat('•', $len);
    $visible = 2;
    return str_repeat('•', max(0, $len - $visible)) . mb_substr($a, -$visible);
}

// Determine dashboard link based on role (used for back button when empty)
$dashboardLink = '../logform/indexes.php';
$role = $_SESSION['role'] ?? null;
if (!$role) {
    $rstmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    if ($rstmt) {
        $rstmt->bind_param('i', $user_id);
        $rstmt->execute();
        $rr = $rstmt->get_result()->fetch_assoc();
        $role = $rr['role'] ?? null;
        $rstmt->close();
    }
}
if ($role === 'superadmin') $dashboardLink = '../superadmin/dashboard.php';
elseif ($role === 'admin') $dashboardLink = '../admin/dashboard.php';
elseif ($role === 'upper_management') $dashboardLink = '../upper_management/dashboard.php';
elseif ($role === 'supervisor') $dashboardLink = '../upper_management/dashboard.php';


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Security Questions</title>
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
        }

        .container {
            max-width: 800px;
            width: 100%;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.5s ease;
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

        /* Header */
        .header {
            background: linear-gradient(135deg, #4776E6 0%, #8E54E9 100%);
            padding: 30px;
            color: white;
            position: relative;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header p {
            font-size: 15px;
            opacity: 0.9;
            line-height: 1.5;
        }

        /* Content */
        .content {
            padding: 30px;
        }

        /* Info Card */
        .info-card {
            background: #F0F9FF;
            border: 1px solid #BAE6FD;
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-card i {
            font-size: 20px;
            color: #0369A1;
        }

        .info-card span {
            color: #0369A1;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.5;
            flex: 1;
        }

        /* Questions List */
        .questions-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-bottom: 30px;
        }

        /* Question Item */
        .question-item {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .question-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15);
            border-color: #8E54E9;
        }

        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .question-number {
            background: linear-gradient(135deg, #4776E6, #8E54E9);
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .question-text {
            font-size: 16px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 12px;
            line-height: 1.5;
        }

        .answer-box {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .answer-icon {
            color: #94A3B8;
            font-size: 16px;
        }

        .answer-value {
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 16px;
            color: #1E293B;
            letter-spacing: 2px;
            font-weight: 500;
            min-width: 120px;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            font-family: 'Inter', sans-serif;
        }

        .btn i {
            font-size: 14px;
        }

        .btn-edit {
            background: #EFF6FF;
            color: #4776E6;
        }

        .btn-edit:hover {
            background: #4776E6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(71, 118, 230, 0.4);
        }

        .btn-show {
            background: #F1F5F9;
            color: #475569;
        }

        .btn-show:hover {
            background: #CBD5E1;
            color: #1E293B;
            transform: translateY(-2px);
        }

        .btn-show.revealed {
            background: #FEF3C7;
            color: #92400E;
        }

        .btn-show.revealed:hover {
            background: #F59E0B;
            color: white;
        }

        .btn-delete {
            background: #FEF2F2;
            color: #DC2626;
        }

        .btn-delete:hover {
            background: #DC2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px -4px rgba(220, 38, 38, 0.4);
        }

        .btn-secondary {
            background: #F1F5F9;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #CBD5E1;
            color: #1E293B;
            transform: translateY(-2px);
        }

        /* Footer */
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 24px;
            border-top: 2px solid #E2E8F0;
        }

        .footer-left {
            display: flex;
            gap: 12px;
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748B;
            font-size: 13px;
        }

        .security-badge {
            background: #ECFDF3;
            color: #067647;
            padding: 6px 12px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-box {
            background: white;
            padding: 30px;
            border-radius: 24px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .modal-icon.warning {
            background: #FEF2F2;
            color: #DC2626;
        }

        .modal-icon.info {
            background: #EFF6FF;
            color: #3B82F6;
        }

        .modal-box h3 {
            font-size: 20px;
            font-weight: 600;
            color: #1E293B;
            margin-bottom: 8px;
            text-align: center;
        }

        .modal-box p {
            color: #64748B;
            text-align: center;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.6;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-modal {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal.confirm {
            background: #4776E6;
            color: white;
        }

        .btn-modal.confirm:hover {
            background: #8E54E9;
            transform: translateY(-2px);
        }

        .btn-modal.cancel {
            background: #F1F5F9;
            color: #475569;
        }

        .btn-modal.cancel:hover {
            background: #CBD5E1;
        }

        .btn-modal.delete {
            background: #DC2626;
            color: white;
        }

        .btn-modal.delete:hover {
            background: #B91C1C;
            transform: translateY(-2px);
        }

        a {
            text-decoration: none;
        }

        @media (max-width: 640px) {
            .content {
                padding: 20px;
            }
            
            .footer {
                flex-direction: column;
                gap: 16px;
            }
            
            .footer-left {
                width: 100%;
                justify-content: center;
            }
            
            .footer-right {
                justify-content: center;
            }
            
            .question-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .action-buttons {
                flex-wrap: wrap;
            }
            
            .btn {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>
            <i class="fas fa-shield-alt"></i>
            Security Questions
        </h1>
        <p>Manage your account recovery options. These questions will be used to verify your identity.</p>
    </div>

    <!-- Content -->
    <div class="content">
        <!-- Info Card -->
        <div class="info-card">
            <i class="fas fa-info-circle"></i>
            <span>Answers are masked for your security. Click "Show" to temporarily reveal an answer. Always ensure you're in a private environment.</span>
        </div>

        <!-- Questions List -->
        <?php if ($hasSecurity): ?>
        <div class="questions-list">
            <!-- Question 1 -->
            <div class="question-item">
                <div class="question-header">
                    <span class="question-number">
                        <i class="fas fa-question-circle"></i> QUESTION 1
                    </span>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($q1); ?></div>
                <div class="answer-box">
                    <i class="fas fa-lock answer-icon"></i>
                    <span class="answer-value" id="ans1"><?php echo htmlspecialchars(mask_answer($a1)); ?></span>
                </div>
                <div class="action-buttons">
                    <form action="input_security_question.php" method="post" style="display:inline">
                        <input type="hidden" name="edit" value="1">
                        <button class="btn btn-edit" type="submit">
                            <i class="fas fa-edit"></i>
                            Edit
                        </button>
                    </form>
                    <button class="btn btn-show" type="button" data-ans="<?php echo htmlspecialchars($a1, ENT_QUOTES); ?>" data-target="ans1">
                        <i class="fas fa-eye"></i>
                        Show
                    </button>
                </div>
            </div>

            <!-- Question 2 -->
            <div class="question-item">
                <div class="question-header">
                    <span class="question-number">
                        <i class="fas fa-question-circle"></i> QUESTION 2
                    </span>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($q2); ?></div>
                <div class="answer-box">
                    <i class="fas fa-lock answer-icon"></i>
                    <span class="answer-value" id="ans2"><?php echo htmlspecialchars(mask_answer($a2)); ?></span>
                </div>
                <div class="action-buttons">
                    <form action="input_security_question.php" method="post" style="display:inline">
                        <input type="hidden" name="edit" value="1">
                        <button class="btn btn-edit" type="submit">
                            <i class="fas fa-edit"></i>
                            Edit
                        </button>
                    </form>
                    <button class="btn btn-show" type="button" data-ans="<?php echo htmlspecialchars($a2, ENT_QUOTES); ?>" data-target="ans2">
                        <i class="fas fa-eye"></i>
                        Show
                    </button>
                </div>
            </div>

            <!-- Question 3 -->
            <div class="question-item">
                <div class="question-header">
                    <span class="question-number">
                        <i class="fas fa-question-circle"></i> QUESTION 3
                    </span>
                </div>
                <div class="question-text"><?php echo htmlspecialchars($q3); ?></div>
                <div class="answer-box">
                    <i class="fas fa-lock answer-icon"></i>
                    <span class="answer-value" id="ans3"><?php echo htmlspecialchars(mask_answer($a3)); ?></span>
                </div>
                <div class="action-buttons">
                    <form action="input_security_question.php" method="post" style="display:inline">
                        <input type="hidden" name="edit" value="1">
                        <button class="btn btn-edit" type="submit">
                            <i class="fas fa-edit"></i>
                            Edit
                        </button>
                    </form>
                    <button class="btn btn-show" type="button" data-ans="<?php echo htmlspecialchars($a3, ENT_QUOTES); ?>" data-target="ans3">
                        <i class="fas fa-eye"></i>
                        Show
                    </button>
                </div>
            </div>
        </div>
        <!-- Footer -->
        <div class="footer">
            <div class="footer-left">
                <button class="btn btn-delete" onclick="openDeleteModal()">
                    <i class="fas fa-trash-alt"></i>
                    Delete All
                </button>
                <a href="<?php echo htmlspecialchars($dashboardLink); ?>">
                    <button class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back
                    </button>
                </a>
            </div>
            <div class="footer-right">
                <div class="security-badge">
                    <i class="fas fa-shield-alt"></i>
                    <span>Encrypted</span>
                </div>
            </div>
        </div>
        <?php else: ?>
            <div class="info-card" style="margin-top:12px;">
                <i class="fas fa-exclamation-circle"></i>
                <span>You have not set your security questions yet. Please set them to enable account recovery options.</span>
            </div>
            <div style="margin-top:16px; display:flex; gap:8px; justify-content:flex-end;">
                <a href="<?php echo htmlspecialchars($dashboardLink); ?>">
                    <button class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </button>
                </a>
                <a href="security_question.php">
                    <button class="btn btn-modal confirm">Set Security Questions</button>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Need-to-set modal (auto-open when no security) -->
<div id="needSetModal" class="modal-overlay" style="<?php echo $hasSecurity ? 'display:none' : 'display:flex'; ?>">
    <div class="modal-box">
        <div class="modal-icon warning">
            <i class="fas fa-shield-alt"></i>
        </div>
        <h3>You need to set security questions first</h3>
        <p>Security questions help you recover access to your account. Please set them now.</p>
        <div class="modal-actions">
            <a href="security_question.php"><button class="btn-modal confirm">Set Security Questions</button></a>
            <button class="btn-modal cancel" onclick="closeNeedSetModal()">Maybe later</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Delete All Questions?</h3>
        <p>This action cannot be undone. All your security questions and answers will be permanently removed.</p>
        <div class="modal-actions">
            <button class="btn-modal cancel" onclick="closeDeleteModal()">Cancel</button>
            <form action="delete_security.php" method="post" style="display:inline" id="deleteForm">
                <button class="btn-modal delete" type="submit">Delete All</button>
            </form>
        </div>
    </div>
</div>

<!-- Reveal Confirmation Modal -->
<div id="revealModal" class="modal-overlay">
    <div class="modal-box">
        <div class="modal-icon info">
            <i class="fas fa-eye"></i>
        </div>
        <h3>Reveal Answer?</h3>
        <p>For security reasons, please confirm you want to temporarily reveal this answer. Make sure no one is watching.</p>
        <div class="modal-actions">
            <button class="btn-modal cancel" onclick="closeRevealModal()">Cancel</button>
            <button class="btn-modal confirm" id="confirmRevealBtn">Yes, Reveal</button>
        </div>
    </div>
</div>

<script>
    // State management
    let currentRevealBtn = null;
    let currentTarget = null;
    let currentAnswer = '';

    // Show/Hide functionality with confirmation
    document.querySelectorAll('.btn-show').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const target = document.getElementById(this.dataset.target);
            const answer = this.dataset.ans;
            
            if (this.classList.contains('revealed')) {
                // Hide the answer
                const masked = '•'.repeat(Math.max(3, (answer || '').length - 2)) + (answer || '').slice(-2);
                target.textContent = masked;
                this.classList.remove('revealed');
                this.innerHTML = '<i class="fas fa-eye"></i> Show';
            } else {
                // Show confirmation modal
                currentRevealBtn = this;
                currentTarget = target;
                currentAnswer = answer;
                document.getElementById('revealModal').style.display = 'flex';
            }
        });
    });

    // Confirm reveal
    document.getElementById('confirmRevealBtn')?.addEventListener('click', function() {
        if (currentRevealBtn && currentTarget) {
            currentTarget.textContent = currentAnswer;
            currentRevealBtn.classList.add('revealed');
            currentRevealBtn.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
        }
        closeRevealModal();
    });

    // Delete modal functions
    function openDeleteModal() {
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function closeRevealModal() {
        document.getElementById('revealModal').style.display = 'none';
        currentRevealBtn = null;
        currentTarget = null;
        currentAnswer = '';
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
            if (event.target.id === 'revealModal') {
                currentRevealBtn = null;
                currentTarget = null;
                currentAnswer = '';
            }
        }
    }

    // Close with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.style.display = 'none';
            });
            currentRevealBtn = null;
            currentTarget = null;
            currentAnswer = '';
        }
    });

    // Need-set modal close
    function closeNeedSetModal() {
        document.getElementById('needSetModal').style.display = 'none';
    }
</script>

</body>
</html>