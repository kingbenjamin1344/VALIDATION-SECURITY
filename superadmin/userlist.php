<?php
session_start();
$role = $_SESSION['role'] ?? 'superadmin';
 $current = basename($_SERVER['PHP_SELF']);
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
$res = mysqli_query($conn, "SELECT id,id_no,firstname,middlename,lastname,suffix,username,email,birthdate,age,sex,purok,barangay,municipality,province,zipcode,country FROM users ORDER BY id DESC");
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
        table{width:100%;border-collapse:collapse;margin-top:8px}
        th,td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left}
        th{background:#fafafa}
        .actions a{margin-right:8px;text-decoration:none;display:inline-block;padding:6px 8px;border-radius:6px;font-size:13px}
        .actions .btn-delete{background:#dc3545;color:#fff;border:1px solid rgba(0,0,0,0.06)}
        .actions .btn-edit{background:linear-gradient(180deg,#ffbf4d,#ff9f1a);color:#000;border:1px solid rgba(0,0,0,0.06)}
        .actions .btn-disable{background:#1f9bff;color:#fff;border:1px solid rgba(0,0,0,0.06)}
        .msg{margin:10px 0;color:#064e3b;background:#ecfdf5;padding:8px;border-radius:6px;border:1px solid #bbf7d0}
        /* Address sidebar */
        .address-sidebar{position:fixed;top:0;right:-420px;width:380px;height:100%;background:#fff;box-shadow:0 8px 30px rgba(2,6,23,0.2);transition:right .28s ease;z-index:1500;padding:20px;box-sizing:border-box}
        .address-sidebar.open{right:0}
        .address-sidebar h4{margin:0 0 12px;color:var(--blue)}
        .address-row{margin:8px 0;color:#333}
        .btn-address{background:var(--blue);color:#fff;padding:6px 10px;border-radius:6px;border:none;cursor:pointer;text-decoration:none}
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
                 <h3>Employee List</h3>
                <?php if ($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
                <table>
                    <thead>
                        <tr>
                            
                            <th>ID No</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Birthdate</th>
                            <th>Sex</th>
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
                                                <td><?=htmlspecialchars(trim($u['firstname'].' '.$u['middlename'].' '.$u['lastname']))?></td>
                                                
                                                <td><?=htmlspecialchars($u['username'])?></td>
                                                <td><?=htmlspecialchars($u['email'])?></td>
                                                <td><?=htmlspecialchars($u['birthdate'])?></td>
                                                <td><?=htmlspecialchars($u['sex'])?></td>
                                                <td><?=htmlspecialchars($u['age'])?></td>
                                                <td class="actions">
                                                    <a class="btn-delete" href="userlist.php?action=delete&id=<?=urlencode($u['id'])?>" onclick="return confirm('Delete this user?')">Delete</a>
                                                    <button
                                                        class="btn-address"
                                                        type="button"
                                                        onclick="showAddress(this)"
                                                        data-purok="<?=htmlspecialchars($u['purok'], ENT_QUOTES)?>"
                                                        data-barangay="<?=htmlspecialchars($u['barangay'], ENT_QUOTES)?>"
                                                        data-municipality="<?=htmlspecialchars($u['municipality'], ENT_QUOTES)?>"
                                                        data-province="<?=htmlspecialchars($u['province'], ENT_QUOTES)?>"
                                                        data-zipcode="<?=htmlspecialchars($u['zipcode'], ENT_QUOTES)?>"
                                                        data-country="<?=htmlspecialchars($u['country'], ENT_QUOTES)?>"
                                                    >Address Details</button>
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

    <!-- Address Sidebar -->
    <div id="addressSidebar" class="address-sidebar" aria-hidden="true">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h4>Address Details</h4>
            <button onclick="closeAddress()" style="background:transparent;border:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <div class="address-row"><strong>Purok:</strong> <span id="addr-purok"></span></div>
        <div class="address-row"><strong>Barangay:</strong> <span id="addr-barangay"></span></div>
        <div class="address-row"><strong>Municipality:</strong> <span id="addr-municipality"></span></div>
        <div class="address-row"><strong>Province:</strong> <span id="addr-province"></span></div>
        <div class="address-row"><strong>Zipcode:</strong> <span id="addr-zipcode"></span></div>
        <div class="address-row"><strong>Country:</strong> <span id="addr-country"></span></div>
    </div>

    <script>
        function showAddress(btn){
            var sidebar = document.getElementById('addressSidebar');
            document.getElementById('addr-purok').textContent = btn.dataset.purok || '';
            document.getElementById('addr-barangay').textContent = btn.dataset.barangay || '';
            document.getElementById('addr-municipality').textContent = btn.dataset.municipality || '';
            document.getElementById('addr-province').textContent = btn.dataset.province || '';
            document.getElementById('addr-zipcode').textContent = btn.dataset.zipcode || '';
            document.getElementById('addr-country').textContent = btn.dataset.country || '';
            sidebar.classList.add('open');
            sidebar.setAttribute('aria-hidden', 'false');
        }
        function closeAddress(){
            var sidebar = document.getElementById('addressSidebar');
            sidebar.classList.remove('open');
            sidebar.setAttribute('aria-hidden', 'true');
        }
    </script>

    <div id="footer">
        <div class="footer-wrap"><p>@South Loan & Finance Company Inc. 2024</p></div>
    </div>

    <style>
        /* footer styles applied here to keep scope local */
        #footer{position:fixed;bottom:0;left:0;right:0;height:48px;background-color:#1E90FF;color:white;text-align:center;padding:0;z-index:1000;display:flex;align-items:center;justify-content:center}
        .footer-wrap{box-sizing:border-box;padding:10px 0;padding-left:220px;max-width:calc(100% - 220px)}
        @media (max-width:900px){.footer-wrap{padding-left:0;max-width:100%}}
    </style>

</body>
</html>

