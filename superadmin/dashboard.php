<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only superadmin can access
if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'superadmin') {
    if ($role === 'admin') header('Location: ../admin/dashboard.php');
    elseif ($role === 'upper_management') header('Location: ../upper_management/dashboard.php');
    else header('Location: ../logform/indexes.php');
    exit();
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Superadmin Dashboard</title></head>
<body>
<h1>Superadmin - Dashboard</h1>
<ul>
  <li><a href="userlist.php">View Admins & Users</a></li>
 
</ul>
</body>
</html>
