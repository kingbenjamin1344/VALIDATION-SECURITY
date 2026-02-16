<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// If already logged in as admin, go to dashboard
if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

// Otherwise, use the centralized login form
header('Location: ../logform/login.php');
exit;
