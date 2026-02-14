<?php
session_start();
if (!isset($_SESSION['superadmin'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Superadmin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topbar">
        <div class="inner">
            <div class="title">Leave Management</div>
            <div class="actions">
                <a href="#" title="Settings">⚙️</a>
                <a href="logout.php" title="Logout">⤴</a>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h3></h3>
        <a href="dashboard.php">Request Leave</a>
        <a href="userlist.php">Employee List</a>
        <a href="account.php">Add Account</a>
        <a href="activity.php">Activity Logs</a>
        <a href="logout.php" style="position:absolute;bottom:20px;left:12px">Logout</a>
    </div>

    <div class="main">
        <div class="card">
            <h1 style="margin-top:0">Welcome, <?=htmlspecialchars($_SESSION['superadmin'])?></h1>
            <p class="muted">Select an item from the left navigation.</p>
        </div>
    </div>
</body>
</html>
