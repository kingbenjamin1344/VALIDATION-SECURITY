<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }
include __DIR__ . '/../regform/config.php';

$logs = [];
if (isset($conn)) {
    // Ensure activity_logs has the expected columns (add email if missing)
    $colRes = @mysqli_query($conn, "SHOW COLUMNS FROM activity_logs LIKE 'email'");
    if (!($colRes && mysqli_num_rows($colRes) > 0)) {
        @mysqli_query($conn, "ALTER TABLE activity_logs ADD COLUMN email VARCHAR(255) NULL AFTER username");
    }

    $q = "SELECT username,email,action,device,created_at FROM activity_logs ORDER BY created_at DESC LIMIT 200";
    $res = mysqli_query($conn, $q);
    if ($res) while ($r = mysqli_fetch_assoc($res)) $logs[] = $r;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topbar">
        <div class="inner">
            <div class="title">Leave Management</div>
            <div class="actions">
                <a href="#" title="Settings"></a>
                <a href="logout.php" title="Logout"></a>
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h3></h3>
        <a href="dashboard.php">Request Leave</a>
        <a href="userlist.php">Employee List</a>
        <a href="account.php">Add Account</a>
        <a href="activity.php">Activity Logs</a>
        <a href="logout.php" style="position:absolute;bottom:20px;left:12px"></a>
    </div>

    <div class="main">
        <div class="card">
            <h2>Activity Logs</h2>
            <p class="muted">Recent actions and login events.</p>
            <table>
                <thead><tr><th>Username</th><th>Email</th><th>Action</th><th>Date / Time</th><th>Device</th></tr></thead>
                <tbody>
                <?php if(empty($logs)): ?>
                    <tr><td colspan="5">No activity logs.</td></tr>
                <?php else: foreach($logs as $l): ?>
                    <tr>
                        <td><?=htmlspecialchars($l['username'] ?? '')?></td>
                        <td><?=htmlspecialchars($l['email'] ?? '')?></td>
                        <td><?=htmlspecialchars($l['action'] ?? '')?></td>
                        <td><?=htmlspecialchars($l['created_at'] ?? '')?></td>
                        <td><?=htmlspecialchars($l['device'] ?? '')?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
