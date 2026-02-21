<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only admin can access
if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'admin') {
    if ($role === 'superadmin') header('Location: ../superadmin/dashboard.php');
    elseif ($role === 'upper_management') header('Location: ../upper_management/dashboard.php');
    else header('Location: ../logform/indexes.php');
    exit();
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Dashboard</title></head>
<body>
<h1>Admin - Dashboard</h1>
<ul>
  <li><a href="list.php">User List</a></li>
 
</ul>
</body>
</html>
