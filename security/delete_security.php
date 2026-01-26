<?php
session_start();
require_once '../regform/config.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("UPDATE users SET security_q1=NULL, security_a1=NULL, security_q2=NULL, security_a2=NULL, security_q3=NULL, security_a3=NULL WHERE username=?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->close();
    header('Location: security_question.php?deleted=1');
    exit();
}
?>