<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }
include __DIR__ . '/../regform/config.php';

// Ensure helper tables exist (non-destructive)
if (isset($conn)) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS blocked_users (user_id INT PRIMARY KEY, blocked_at DATETIME)");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admins (user_id INT PRIMARY KEY, assigned_at DATETIME)");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, username VARCHAR(255), email VARCHAR(255), action VARCHAR(255), device VARCHAR(512), created_at DATETIME)");
}

// Actions: delete, block, unblock
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0 && isset($conn)) {
        $q = "SELECT username,email FROM users WHERE id=$id";
        $r = mysqli_query($conn, $q);
        $row = $r ? mysqli_fetch_assoc($r) : null;
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        mysqli_query($conn, "DELETE FROM admins WHERE user_id=$id");
        mysqli_query($conn, "DELETE FROM blocked_users WHERE user_id=$id");
        if ($row) {
            $u = mysqli_real_escape_string($conn, $row['username']);
            $e = mysqli_real_escape_string($conn, $row['email']);
            mysqli_query($conn, "INSERT INTO activity_logs (username,email,action,device,created_at) VALUES ('$u','$e','deleted_user','".mysqli_real_escape_string($conn, $_SERVER['HTTP_USER_AGENT'] ?? '')."', NOW())");
        }
    }
    header('Location: userlist.php'); exit;
}

if (isset($_GET['block'])) {
    $id = intval($_GET['block']);
    if ($id > 0 && isset($conn)) {
        mysqli_query($conn, "REPLACE INTO blocked_users (user_id,blocked_at) VALUES ($id,NOW())");
        $r = mysqli_query($conn, "SELECT username,email FROM users WHERE id=$id");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        if ($row) mysqli_query($conn, "INSERT INTO activity_logs (username,email,action,device,created_at) VALUES ('".mysqli_real_escape_string($conn,$row['username'])."','".mysqli_real_escape_string($conn,$row['email'])."','blocked_user','".mysqli_real_escape_string($conn,$_SERVER['HTTP_USER_AGENT'] ?? '')."',NOW())");
    }
    header('Location: userlist.php'); exit;
}

if (isset($_GET['unblock'])) {
    $id = intval($_GET['unblock']);
    if ($id > 0 && isset($conn)) {
        mysqli_query($conn, "DELETE FROM blocked_users WHERE user_id=$id");
    }
    header('Location: userlist.php'); exit;
}

// Fetch users
$users = [];
if (isset($conn)) {
    $sql = "SELECT u.*, (a.user_id IS NOT NULL) AS is_admin, (b.user_id IS NOT NULL) AS is_blocked FROM users u LEFT JOIN admins a ON u.id=a.user_id LEFT JOIN blocked_users b ON u.id=b.user_id ORDER BY u.id DESC";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) $users[] = $row;
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User List</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="topbar">
        <div class="inner">
            <div class="title">Leave Management</div>
            <div class="actions">
                
               
            </div>
        </div>
    </div>

    <div class="sidebar">
        <h3></h3>
        <a href="dashboard.php">Request Leave</a>
        <a href="userlist.php">Employee List</a>
        <a href="account.php">Add Account</a>
        <a href="activity.php">Activity Logs</a>
        
    </div>

    <div class="main">
        <div class="card">
            <h2>User List</h2>
            <p class="muted">Registered users from the registration form.</p>
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Admin</th><th>Blocked</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if (count($users)===0): ?>
                    <tr><td colspan="7">No users found.</td></tr>
                <?php else: foreach($users as $u): ?>
                    <tr>
                        <td><?=htmlspecialchars($u['id'] ?? '')?></td>
                        <td><?=htmlspecialchars(trim(($u['firstname']??'') . ' ' . ($u['lastname']??'')))?></td>
                        <td><?=htmlspecialchars($u['email'] ?? '')?></td>
                        <td><?=htmlspecialchars($u['username'] ?? '')?></td>
                        <td><?=($u['is_admin'] ? 'Yes' : 'No')?></td>
                        <td><?=($u['is_blocked'] ? 'Yes' : 'No')?></td>
                        <td>
                            <a class="btn" href="account.php?promote=<?=intval($u['id'])?>">Promote</a>
                            <?php if(!$u['is_blocked']): ?>
                                <a class="btn" href="?block=<?=intval($u['id'])?>">Block</a>
                            <?php else: ?>
                                <a class="btn" href="?unblock=<?=intval($u['id'])?>">Unblock</a>
                            <?php endif; ?>
                            <a class="btn danger" href="?delete=<?=intval($u['id'])?>" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
