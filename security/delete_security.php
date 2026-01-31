<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_SESSION['username'];
    
    // Get user ID
    $userStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $userStmt->bind_param('s', $username);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $userRow = $userResult->fetch_assoc();
    $user_id = $userRow['id'];
    
    // Delete security questions for user
    $stmt = $conn->prepare("DELETE FROM security_questions WHERE user_id=?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: security_question.php?deleted=1');
    exit();
}
?>