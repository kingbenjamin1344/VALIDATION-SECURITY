<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username']) && !isset($_SESSION['admin'])) {
    header('Location: ../logform/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ensure schema
    $colCheck = $conn->query("SHOW COLUMNS FROM security_questions LIKE 'account_type'");
    if ($colCheck && $colCheck->num_rows == 0) {
        $conn->query("ALTER TABLE security_questions ADD COLUMN account_type VARCHAR(10) NOT NULL DEFAULT 'user', ADD COLUMN account_id INT NULL");
    }

    if (isset($_SESSION['admin'])) {
        $admin_id = $_SESSION['admin']['id'];
        $stmt = $conn->prepare("DELETE FROM security_questions WHERE account_type='admin' AND account_id=?");
        $stmt->bind_param('i', $admin_id);
    } else {
        $username = $_SESSION['username'];
        $userStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $userStmt->bind_param('s', $username);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userRow = $userResult->fetch_assoc();
        $user_id = $userRow['id'];
        $stmt = $conn->prepare("DELETE FROM security_questions WHERE user_id=?");
        $stmt->bind_param('i', $user_id);
    }

    $stmt->execute();
    $stmt->close();

    header('Location: security_question.php?deleted=1');
    exit();
}
?>