<?php
session_start();
 $role = $_SESSION['role'] ?? 'superadmin';
 $current = basename($_SERVER['PHP_SELF']);
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../regform/config.php';

$msg = '';

// Handle delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    mysqli_query($conn, "DELETE FROM activity_logs WHERE id={$id}");
    header('Location: logs.php'); exit;
}

// Handle update from edit form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_log') {
    $id = intval($_POST['id']);
    // normalize and validate usertype
    $usertype = strtolower(trim($_POST['usertype'] ?? 'user'));
    $allowed_roles = ['admin','superadmin','user'];
    if (!in_array($usertype, $allowed_roles)) $usertype = 'user';
    // restrict action to login/logout
    $action_text = strtolower(trim($_POST['action_text'] ?? 'login'));
    if (!in_array($action_text, ['login','logout'])) $action_text = 'login';
    $device = mysqli_real_escape_string($conn, $_POST['device'] ?? '');
    $dt = mysqli_real_escape_string($conn, $_POST['datetime'] ?? '');
    $usertype_esc = mysqli_real_escape_string($conn, $usertype);
    $action_esc = mysqli_real_escape_string($conn, $action_text);
    mysqli_query($conn, "UPDATE activity_logs SET usertype='{$usertype_esc}', action='{$action_esc}', device='{$device}', created_at='{$dt}' WHERE id={$id}");
    $msg = 'Log updated.';
}

// Load edit row if requested
$editRow = null;
if (isset($_GET['edit']) && intval($_GET['edit'])>0) {
    $eid = intval($_GET['edit']);
    $resE = mysqli_query($conn, "SELECT * FROM activity_logs WHERE id={$eid} LIMIT 1");
    $editRow = mysqli_fetch_assoc($resE);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Branches - Superadmin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root{--blue:#1698ff;--nav-blue:#1f9bff;--bg:#f5f7fb;--card-bg:#fff}
        *{box-sizing:border-box}
        body{font-family:Inter,Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#243042;overflow-x:hidden;padding-bottom:64px}
        .app{display:flex;min-height:100vh}
        .sidebar{width:220px;background:var(--nav-blue);color:#fff;padding:28px 0 18px}
        .role-area{ text-align:center; padding:8px 18px 16px }
        .role-circle-small{ width:48px; height:48px; border-radius:50%; background:#fff; color:var(--nav-blue); display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto 8px }
        .role-label-small{ color:#fff; font-size:13px; text-transform:capitalize }
        .brand{font-weight:600;font-size:18px;margin-bottom:28px;padding:0 18px}
        .nav{display:flex;flex-direction:column;gap:12px}
        .nav a{color:#fff;text-decoration:none;padding:10px 18px;border-radius:6px;display:block;box-sizing:border-box;width:100%}
        .nav a:hover{background:rgba(255,255,255,0.08)}
        .nav a.active{background:#063970;border-radius:6px}
        .content{flex:1;display:flex;flex-direction:column}
        .header{height:64px;background:var(--blue);color:#fff;display:flex;align-items:center;padding:0 24px;justify-content:center;position:relative}
        .logout-icon{position:absolute;right:24px;top:50%;transform:translateY(-50%);font-size:20px;cursor:pointer}
        .container{padding:24px 28px;flex:1}
        .panel{background:#fff;border-radius:8px;padding:18px;box-shadow:0 6px 18px rgba(15,23,42,0.06)}
        form.row{display:flex;gap:10px;align-items:center}
        input[type=text]{padding:8px 10px;border:1px solid #e5e7eb;border-radius:6px;width:260px}
        button{padding:8px 12px;background:var(--blue);color:#fff;border:none;border-radius:6px;cursor:pointer}
        table{width:100%;border-collapse:collapse;margin-top:16px}
        th,td{padding:12px 10px;border-bottom:1px solid #eef2f7;text-align:left}
        th{background:#fafafa}
        .pagination{display:flex;gap:8px;justify-content:flex-end;margin-top:14px}
        .pagination a,.pagination span{padding:8px 10px;border-radius:6px;background:#fff;border:1px solid #e6eef9;color:var(--blue);text-decoration:none}
        .pagination .current{background:#fff;color:var(--blue);border-color:#e6eef9}
        .actions a{margin-right:8px;color:var(--blue);text-decoration:none}
            .actions a{margin-right:8px;text-decoration:none;display:inline-block;padding:6px 8px;border-radius:6px;font-size:13px}
            .actions .btn-delete{background:#dc3545;color:#fff;border:1px solid rgba(0,0,0,0.06)}
            .actions .btn-edit{background:linear-gradient(180deg,#ffbf4d,#ff9f1a);color:#000;border:1px solid rgba(0,0,0,0.06)}
            .actions .btn-disable{background:#1f9bff;color:#fff;border:1px solid rgba(0,0,0,0.06)}
        .msg{margin:10px 0;color:#064e3b;background:#ecfdf5;padding:8px;border-radius:6px;border:1px solid #bbf7d0}
        #footer{position:fixed;bottom:0;left:0;right:0;height:48px;background-color:#1E90FF;color:white;text-align:center;padding:0;z-index:1000;display:flex;align-items:center;justify-content:center}
        .footer-wrap{box-sizing:border-box;padding:10px 0;padding-left:220px;max-width:calc(100% - 220px)}
        @media (max-width:900px){.footer-wrap{padding-left:0;max-width:100%}}
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"></div>
        <div class="role-area">
            <div class="role-circle-small"><?=htmlspecialchars(strtoupper(substr($role,0,1)))?></div>
            <div class="role-label-small"><?=htmlspecialchars($role)?></div>
        </div>
        <nav class="nav">
            <a href="dashboard.php" <?= ($current === 'dashboard.php') ? 'class="active"' : '' ?>>Dashboard</a>
            <a href="addacc.php" <?= ($current === 'addacc.php') ? 'class="active"' : '' ?>>Add Account</a>
            <a href="userlist.php" <?= ($current === 'userlist.php') ? 'class="active"' : '' ?>>Employee List</a>
            <a href="create.php" <?= ($current === 'create.php') ? 'class="active"' : '' ?>>Create Admin</a>
            <a href="logs.php" <?= ($current === 'logs.php') ? 'class="active"' : '' ?>>Activity Logs</a>
        </nav>
    </aside>
    <div class="content">
        <header class="header"><div class="title">Leave Management</div>
            <span class="logout-icon" onclick="logout()">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
        </header>
        <div class="container">
            <div class="panel">
                <?php if ($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

                <?php if ($editRow): ?>
                    <h3>Edit Log #<?php echo intval($editRow['id']); ?></h3>
                    <form method="post" style="margin-bottom:14px">
                        <input type="hidden" name="action" value="update_log">
                        <input type="hidden" name="id" value="<?php echo intval($editRow['id']); ?>">
                        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px">
                            <div style="flex:1">
                                <label>User Type</label><br>
                                <select name="usertype">
                                    <?php $er_usertype = $editRow['usertype'] ?? 'user'; ?>
                                    <option value="admin" <?php if($er_usertype=='admin') echo 'selected';?>>Admin</option>
                                    <option value="superadmin" <?php if($er_usertype=='superadmin') echo 'selected';?>>Superadmin</option>
                                    <option value="user" <?php if($er_usertype=='user') echo 'selected';?>>User</option>
                                </select>
                            </div>
                            <div style="flex:2">
                                <label>Logs</label><br>
                                <select name="action_text">
                                    <?php $er_action = strtolower($editRow['action'] ?? 'login'); ?>
                                    <option value="login" <?php if($er_action=='login') echo 'selected';?>>login</option>
                                    <option value="logout" <?php if($er_action=='logout') echo 'selected';?>>logout</option>
                                </select>
                            </div>
                            <div style="flex:1">
                                <label>Device</label><br>
                                <input type="text" name="device" value="<?php echo htmlspecialchars($editRow['device']); ?>" style="width:100%">
                            </div>
                            <div style="flex:1">
                                <label>Date/Time</label><br>
                                <input type="text" name="datetime" value="<?php echo htmlspecialchars($editRow['created_at']); ?>" style="width:100%">
                            </div>
                        </div>
                        <div style="text-align:right"><button type="submit">Save</button> <a href="logs.php">Cancel</a></div>
                    </form>
                <?php endif; ?>

                <h3>Activity Logs</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Logs</th>
                            <th>Device</th>
                            <th>Date / Time</th>
                           
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Pagination: 5 rows per page
                        $per_page = 5;
                        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                        $offset = ($page - 1) * $per_page;
                        $total_rows = 0;
                        $countRes = mysqli_query($conn, "SELECT COUNT(*) FROM activity_logs");
                        if ($countRes) { $r2 = mysqli_fetch_row($countRes); $total_rows = (int)$r2[0]; }
                        $total_pages = max(1, (int)ceil($total_rows / $per_page));
                        $res = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}");
                        while ($row = mysqli_fetch_assoc($res)):
                            // Resolve user display by usertype where possible
                            $displayUser = htmlspecialchars($row['username'] ?? '');
                            $ut = strtolower($row['usertype'] ?? 'user');
                            if ($ut === 'admin') {
                                $u = mysqli_real_escape_string($conn, $row['username'] ?? '');
                                $rq = mysqli_query($conn, "SELECT id, fullname FROM admins WHERE username='{$u}' LIMIT 1");
                                if ($ru = mysqli_fetch_assoc($rq)) {
                                    $displayUser = htmlspecialchars($ru['fullname'] . ' (admin)');
                                }
                            } elseif ($ut === 'user') {
                                $u = mysqli_real_escape_string($conn, $row['username'] ?? '');
                                $rq = mysqli_query($conn, "SELECT id, firstname, lastname, username FROM users WHERE username='{$u}' LIMIT 1");
                                if ($ru = mysqli_fetch_assoc($rq)) {
                                    $name = trim(($ru['firstname'] ?? '') . ' ' . ($ru['lastname'] ?? ''));
                                    $displayUser = htmlspecialchars(($name !== '') ? $name . ' (user)' : ($ru['username'] ?? $u));
                                }
                            } elseif ($ut === 'superadmin') {
                                $displayUser = htmlspecialchars(($row['username'] ?? '') . ' (superadmin)');
                            }
                            // Normalize action to login/logout when possible
                            $action_val = strtolower($row['action'] ?? '');
                            if (!in_array($action_val, ['login','logout'])) {
                                // fall back to raw value if not one of the two
                                $action_val = htmlspecialchars($row['action'] ?? '');
                            }
                            // Compose log text: who did what (action is login/logout)
                            $logText = $displayUser . ' — ' . $action_val;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ut); ?></td>
                            <td><?php echo $logText; ?></td>
                            <td><?php echo htmlspecialchars($row['device']); ?></td>
                            <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            <td class="actions">
                             
                                
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>">&laquo; Prev</a>
                        <?php else: ?>
                            <span style="opacity:.5">&laquo; Prev</span>
                        <?php endif; ?>

                        <span class="current"><?php echo $page; ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                        <?php else: ?>
                            <span style="opacity:.5">Next &raquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:2000">
        <div style="background:#fff;padding:20px;border-radius:8px;width:320px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.2)">
            <h3 style="margin:0;color:var(--blue)">Confirm Logout</h3>
            <p style="color:#555">Are you sure you want to logout?</p>
            <div style="display:flex;gap:10px">
                <button style="flex:1;background:#dc3545;color:#fff;border:none;padding:10px;border-radius:6px;cursor:pointer" onclick="closeLogoutModal()">Cancel</button>
                <button style="flex:1;background:#28a745;color:#fff;border:none;padding:10px;border-radius:6px;cursor:pointer" onclick="confirmLogout()">Yes</button>
            </div>
        </div>
    </div>

    <script>
        function logout(){ document.getElementById('logoutModal').style.display='flex'; }
        function closeLogoutModal(){ document.getElementById('logoutModal').style.display='none'; }
        function confirmLogout(){ window.location.href='logout.php'; }
    </script>

    <div id="footer">
        <div class="footer-wrap"><p>@South Loan & Finance Company Inc. 2024</p></div>
    </div>

</body>
</html>

