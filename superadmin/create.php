
<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }

require_once __DIR__ . '/../regform/config.php';

$msg = '';

// Handle create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    if ($fullname === '' || $username === '' || $password === '' || $email === '') {
        $msg = 'Please fill all fields.';
    } else {
        $sql = "INSERT INTO admins (fullname, username, password, email) VALUES ('{$fullname}', '{$username}', '{$password}', '{$email}')";
        if (mysqli_query($conn, $sql)) {
            $msg = 'Admin created successfully.';
        } else {
            $msg = 'Error: ' . mysqli_error($conn);
        }
    }
}

// Handle enable/disable/delete
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($_GET['action'] === 'disable') {
        mysqli_query($conn, "UPDATE admins SET status = 0 WHERE id = {$id}");
        header('Location: create.php'); exit;
    }
    if ($_GET['action'] === 'enable') {
        mysqli_query($conn, "UPDATE admins SET status = 1 WHERE id = {$id}");
        header('Location: create.php'); exit;
    }
    if ($_GET['action'] === 'delete') {
        mysqli_query($conn, "DELETE FROM admins WHERE id = {$id}");
        header('Location: create.php'); exit;
    }
}

// Handle edit/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $sql = "UPDATE admins SET fullname='{$fullname}', username='{$username}', email='{$email}' WHERE id={$id}";
    if (mysqli_query($conn, $sql)) {
        $msg = 'Admin updated.';
        header('Location: create.php'); exit;
    } else {
        $msg = 'Update error: ' . mysqli_error($conn);
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin - Create</title>
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
                <?php if($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

                <?php
                // If editing, load the admin
                $editAdmin = null;
                if (isset($_GET['edit']) && intval($_GET['edit'])>0) {
                    $eid = intval($_GET['edit']);
                    $r = mysqli_query($conn, "SELECT * FROM admins WHERE id={$eid} LIMIT 1");
                    $editAdmin = mysqli_fetch_assoc($r);
                }
                ?>

                <div style="display:flex;gap:20px;align-items:flex-start">
                    <div style="flex:0 0 420px;background:#f8fafc;padding:18px;border-radius:6px">
                        <h3 style="margin:0 0 10px"><?php echo $editAdmin ? 'Edit Admin' : 'Create Admin'; ?></h3>
                        <form method="post">
                            <?php if ($editAdmin): ?>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo intval($editAdmin['id']); ?>">
                            <?php else: ?>
                                <input type="hidden" name="action" value="create">
                            <?php endif; ?>
                            <div style="margin-bottom:8px">
                                <label>Full name</label><br>
                                <input type="text" name="fullname" value="<?php echo $editAdmin ? htmlspecialchars($editAdmin['fullname']) : ''; ?>" style="width:100%">
                            </div>
                            <div style="margin-bottom:8px">
                                <label>Username</label><br>
                                <input type="text" name="username" value="<?php echo $editAdmin ? htmlspecialchars($editAdmin['username']) : ''; ?>" style="width:100%">
                            </div>
                            <?php if (!$editAdmin): ?>
                            <div style="margin-bottom:8px">
                                <label>Password</label><br>
                                <input type="text" name="password" value="" style="width:100%">
                            </div>
                            <?php endif; ?>
                            <div style="margin-bottom:8px">
                                <label>Email</label><br>
                                <input type="text" name="email" value="<?php echo $editAdmin ? htmlspecialchars($editAdmin['email']) : ''; ?>" style="width:100%">
                            </div>
                            <div style="text-align:right">
                                <button type="submit"><?php echo $editAdmin ? 'Update' : 'Create'; ?></button>
                            </div>
                        </form>
                    </div>

                    <div style="flex:1">
                        <h3 style="margin-top:0">Admin List</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Full name</th>
                                    <th>Username</th>
                                    <th>Password</th>
                                    <th>Email</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = mysqli_query($conn, "SELECT * FROM admins ORDER BY created_at DESC");
                                while ($row = mysqli_fetch_assoc($res)):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td style="font-family:monospace;"><?php echo htmlspecialchars(substr($row['password'],0,20)); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                    <td class="actions">
                                        <?php if ($row['status']): ?>
                                            <a href="create.php?action=disable&id=<?php echo $row['id']; ?>">Disable</a>
                                        <?php else: ?>
                                            <a href="create.php?action=enable&id=<?php echo $row['id']; ?>">Enable</a>
                                        <?php endif; ?>
                                        <a href="create.php?edit=<?php echo $row['id']; ?>">Edit</a>
                                        <a href="create.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this admin?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>

