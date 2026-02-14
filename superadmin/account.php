<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }
include __DIR__ . '/../regform/config.php';

if (isset($conn)) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS admins (user_id INT PRIMARY KEY, assigned_at DATETIME)");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, username VARCHAR(255), email VARCHAR(255), action VARCHAR(255), device VARCHAR(512), created_at DATETIME)");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS superadmins (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(191) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, email VARCHAR(255), created_at DATETIME)");
}

// Promote via GET or POST
if (isset($_GET['promote'])) {
    $id = intval($_GET['promote']);
    if ($id > 0 && isset($conn)) {
        mysqli_query($conn, "REPLACE INTO admins (user_id,assigned_at) VALUES ($id,NOW())");
        $r = mysqli_query($conn, "SELECT username,email FROM users WHERE id=$id");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        if ($row) mysqli_query($conn, "INSERT INTO activity_logs (username,email,action,device,created_at) VALUES ('".mysqli_real_escape_string($conn,$row['username'])."','".mysqli_real_escape_string($conn,$row['email'])."','promoted_to_admin','".mysqli_real_escape_string($conn,$_SERVER['HTTP_USER_AGENT'] ?? '')."',NOW())");
    }
    header('Location: account.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $id = intval($_POST['user_id']);
    if ($id > 0 && isset($conn)) {
        mysqli_query($conn, "REPLACE INTO admins (user_id,assigned_at) VALUES ($id,NOW())");
        header('Location: account.php'); exit;
    }
}

// (Create-superadmin UI removed per request)

// Fetch users and admins
$users = [];
$admins = [];
if (isset($conn)) {
    $res = mysqli_query($conn, "SELECT id, firstname, lastname, username, email FROM users ORDER BY id DESC");
    if ($res) while ($r = mysqli_fetch_assoc($res)) $users[] = $r;

    $res2 = mysqli_query($conn, "SELECT a.user_id, u.username, u.email FROM admins a LEFT JOIN users u ON a.user_id=u.id ORDER BY a.assigned_at DESC");
    if ($res2) while ($r = mysqli_fetch_assoc($res2)) $admins[] = $r;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    </head>
    <head>
        <title>Add Account / Assign Admin</title>
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
                <h2>Add Account / Assign Admin</h2>
                <p class="muted">Assign an existing registered user as an admin.</p>

                <h3>Promote existing user to Admin</h3>
                <form method="post">
                    <div class="form-row">
                        <label>Select user</label>
                        <select name="user_id" required>
                            <option value="">-- choose user --</option>
                            <?php foreach($users as $u): ?>
                                <option value="<?=intval($u['id'])?>"><?=htmlspecialchars(($u['username']?:$u['firstname'].' '.$u['lastname']).' ('.($u['email']?:'').')')?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row"><button class="btn" type="submit">Assign Admin</button></div>
                </form>

                <h3 style="margin-top:20px">Current Admin Accounts</h3>
                <table>
                    <tr><th>User ID</th><th>Username</th><th>Email</th></tr>
                    <?php if(empty($admins)): ?>
                        <tr><td colspan="3">No admin accounts.</td></tr>
                    <?php else: foreach($admins as $a): ?>
                        <tr><td><?=intval($a['user_id'])?></td><td><?=htmlspecialchars($a['username']??'')?></td><td><?=htmlspecialchars($a['email']??'')?></td></tr>
                    <?php endforeach; endif; ?>
                </table>
            </div>
        </div>

</body>
</html>
