<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }

// DB connection
require_once __DIR__ . '/../regform/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost','root','','it107_security_sql');
}

$message = '';

// optional delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: userlist.php'); exit;
}

// fetch users
$users = [];
$res = mysqli_query($conn, "SELECT id,id_no,firstname,middlename,lastname,suffix,username,email,birthdate,age FROM users ORDER BY id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) { $users[] = $row; }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>User List - Superadmin</title>
    <style>
        :root{--blue:#1698ff;--nav-blue:#1f9bff;--bg:#f5f7fb;--card-bg:#fff}
        *{box-sizing:border-box}
        body{font-family:Inter,Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#243042}
        .app{display:flex;min-height:100vh}
        .sidebar{width:220px;background:var(--nav-blue);color:#fff;padding:28px 18px 18px}
        .brand{font-weight:600;font-size:18px;margin-bottom:28px}
        .nav{display:flex;flex-direction:column;gap:12px}
        .nav a{color:#fff;text-decoration:none;padding:10px 12px;border-radius:6px;display:block}
        .nav a:hover{background:rgba(255,255,255,0.08)}
        .content{flex:1;display:flex;flex-direction:column}
        .header{height:64px;background:var(--blue);color:#fff;display:flex;align-items:center;padding:0 24px;justify-content:center}
        .container{padding:24px 28px;flex:1}
        .panel{background:#fff;border-radius:8px;padding:18px;box-shadow:0 6px 18px rgba(15,23,42,0.06)}
        table{width:100%;border-collapse:collapse;margin-top:8px}
        th,td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left}
        th{background:#fafafa}
        .actions a{margin-right:8px;color:var(--blue);text-decoration:none}
        .msg{margin:10px 0;color:#064e3b;background:#ecfdf5;padding:8px;border-radius:6px;border:1px solid #bbf7d0}
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"></div>
        <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="addacc.php">Add Account</a>
            <a href="userlist.php">Employee List</a>
            <a href="create.php">Create Admin</a>
            <a href="logs.php">Activity Logs</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>
    <div class="content">
        <header class="header"><div class="title">User List</div></header>
        <div class="container">
            <div class="panel">
                 <h3>Employee List</h3>
                <?php if ($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            
                            <th>ID No</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Age</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" style="color:#6b7280">No users found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    
                                    <td><?=htmlspecialchars($u['id_no'])?></td>
                                    <td><?=htmlspecialchars(trim($u['firstname'].' '.$u['middlename'].' '.$u['lastname'].' '.$u['suffix']))?></td>
                                    <td><?=htmlspecialchars($u['username'])?></td>
                                    <td><?=htmlspecialchars($u['email'])?></td>
                                    <td><?=htmlspecialchars($u['age'])?></td>
                                    <td class="actions">
                                        <a href="userlist.php?action=delete&id=<?=urlencode($u['id'])?>" onclick="return confirm('Delete this user?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>

