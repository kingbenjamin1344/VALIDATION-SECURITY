<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }

// use existing DB connection
require_once __DIR__ . '/../regform/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    // fallback: try to create connection if config doesn't set $conn properly
    $conn = mysqli_connect('localhost','root','','it107_security_sql');
}

// One-time migration: create branches table if not exists
$create_sql = "CREATE TABLE IF NOT EXISTS branches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
@mysqli_query($conn, $create_sql);

$message = '';
$editItem = null;

// delete branch
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare('DELETE FROM branches WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    header('Location: addacc.php'); exit;
}

// show edit form
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare('SELECT id,name,created_at FROM branches WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $editItem = $res->fetch_assoc();
    $stmt->close();
}

// handle POST for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    $name = trim($_POST['branch_name'] ?? '');
    if ($action === 'add') {
        if ($name !== '') {
            $stmt = $conn->prepare('INSERT INTO branches (name) VALUES (?)');
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $stmt->close();
            $message = 'Branch added.';
        } else {
            $message = 'Branch name is required.';
        }
        header('Location: addacc.php'); exit;
    } elseif ($action === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        if ($name !== '') {
            $stmt = $conn->prepare('UPDATE branches SET name = ? WHERE id = ?');
            $stmt->bind_param('si', $name, $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Branch updated.';
        } else {
            $message = 'Branch name is required.';
        }
        header('Location: addacc.php'); exit;
    }
}

// fetch branches from DB
$branches = [];
$res = mysqli_query($conn, 'SELECT id,name,created_at FROM branches ORDER BY id DESC');
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) { $branches[] = $row; }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Branches - Superadmin</title>
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
        form.row{display:flex;gap:10px;align-items:center}
        input[type=text]{padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;width:260px}
        button{padding:8px 12px;background:var(--blue);color:#fff;border:none;border-radius:6px;cursor:pointer}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        th,td{padding:12px 10px;border-bottom:1px solid #eef2f7;text-align:left}
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
        <header class="header"><div class="title">Leave Management</div></header>
        <div class="container">
            <div class="panel">
                <?php if ($message): ?>
                    <div class="msg"><?=htmlspecialchars($message)?></div>
                <?php endif; ?>

                <?php if ($editItem): ?>
                    <h3>Edit Branch</h3>
                    <form method="post" class="row">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?=htmlspecialchars($editItem['id'])?>">
                        <input type="text" name="branch_name" value="<?=htmlspecialchars($editItem['name'])?>" required>
                        <button type="submit">Save Changes</button>
                        <a href="addacc.php" style="margin-left:8px;color:#374151;text-decoration:none">Cancel</a>
                    </form>
                <?php else: ?>
                    <h3>Add Branch</h3>
                    <form method="post" class="row">
                        <input type="hidden" name="action" value="add">
                        <input type="text" name="branch_name" placeholder="Branch name" required>
                        <button type="submit">Add Branch</button>
                    </form>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr><th>Branch Name</th><th>Date &amp; Time</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($branches)): ?>
                            <tr><td colspan="3" style="color:#6b7280">No branches yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($branches as $b): ?>
                                <tr>
                                    <td><?=htmlspecialchars($b['name'])?></td>
                                    <td><?=htmlspecialchars($b['created_at'])?></td>
                                    <td class="actions">
                                        <a href="addacc.php?action=edit&id=<?=urlencode($b['id'])?>">Edit</a>
                                        <a href="addacc.php?action=delete&id=<?=urlencode($b['id'])?>" onclick="return confirm('Delete this branch?')">Delete</a>
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

