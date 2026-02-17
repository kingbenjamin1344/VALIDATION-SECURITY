
<?php
session_start();
 $role = $_SESSION['role'] ?? 'superadmin';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root{--blue:#1698ff;--nav-blue:#1f9bff;--bg:#f5f7fb;--card-bg:#fff}
        *{box-sizing:border-box}
        body{font-family:Inter,Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#243042;overflow-x:hidden}
        .app{display:flex;min-height:100vh}
        .sidebar{width:220px;background:var(--nav-blue);color:#fff;padding:28px 18px 18px}
        .role-area{ text-align:center; padding:8px 0 16px }
        .role-circle-small{ width:48px; height:48px; border-radius:50%; background:#fff; color:var(--nav-blue); display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto 8px }
        .role-label-small{ color:#fff; font-size:13px; text-transform:capitalize }
        .brand{font-weight:600;font-size:18px;margin-bottom:28px}
        .nav{display:flex;flex-direction:column;gap:12px}
        .nav a{color:#fff;text-decoration:none;padding:10px 12px;border-radius:6px;display:block}
        .nav a:hover{background:rgba(255,255,255,0.08)}
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
        .actions a{margin-right:8px;color:var(--blue);text-decoration:none}
        .actions a{margin-right:8px;text-decoration:none;display:inline-block;padding:6px 8px;border-radius:6px;font-size:13px}
        .actions .btn-delete{background:#dc3545;color:#fff;border:1px solid rgba(0,0,0,0.06)}
        .actions .btn-edit{background:linear-gradient(180deg,#ffbf4d,#ff9f1a);color:#000;border:1px solid rgba(0,0,0,0.06)}
        .actions .btn-disable{background:#1f9bff;color:#fff;border:1px solid rgba(0,0,0,0.06)}
        .msg{margin:10px 0;color:#064e3b;background:#ecfdf5;padding:8px;border-radius:6px;border:1px solid #bbf7d0}
        #footer{position:fixed;bottom:0;left:0;right:0;height:48px;background-color:#1E90FF;color:white;text-align:center;padding:0;z-index:1000;display:flex;align-items:center;justify-content:center}
          /* inner wrapper keeps footer content aligned with page content (to the right of sidebar)
              use padding + max-width to avoid adding horizontal overflow */
          .footer-wrap{box-sizing:border-box;padding:10px 0;padding-left:220px;max-width:calc(100% - 220px)}
        @media (max-width:900px){.footer-wrap{padding-left:0;max-width:100%}}
        body{padding-bottom:64px}
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
            <a href="dashboard.php">Dashboard</a>
            <a href="addacc.php">Add Account</a>
            <a href="userlist.php">Employee List</a>
            <a href="create.php">Create Admin</a>
            <a href="logs.php">Activity Logs</a>
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
                <?php if($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3 style="margin:0">Admin List</h3>
                    <button id="openCreateBtn" style="background:var(--blue);color:#fff;padding:8px 12px;border-radius:6px;border:none;cursor:pointer">Create Admin</button>
                </div>
                <div style="width:100%">
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
                                            <a class="btn-disable" href="create.php?action=disable&id=<?php echo $row['id']; ?>">Disable</a>
                                        <?php else: ?>
                                            <a class="btn-disable" href="create.php?action=enable&id=<?php echo $row['id']; ?>">Enable</a>
                                        <?php endif; ?>
                                        <a class="btn-edit" href="create.php?edit=<?php echo $row['id']; ?>">Edit</a>
                                        <a class="btn-delete" href="create.php?action=delete&id=<?php echo $row['id']; ?>" onclick="return confirm('Delete this admin?')">Delete</a>
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

    <!-- Create Admin Modal -->
    <div id="createModal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.45);z-index:2500">
        <div style="position:relative;background:#fff;padding:20px;border-radius:8px;width:420px;box-shadow:0 10px 30px rgba(2,6,23,0.3);box-sizing:border-box">
            <button aria-label="Close" onclick="closeCreateModal()" style="position:absolute;right:12px;top:8px;border:none;background:transparent;font-size:22px;line-height:1;color:#374151;cursor:pointer">&times;</button>
            <h3 id="modalTitle">Create Admin</h3>
            <form id="createForm" method="post">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="formId" value="">
                <div style="margin-bottom:8px">
                    <label>Full name</label><br>
                    <input type="text" name="fullname" id="fullname" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px">
                </div>
                <div style="margin-bottom:8px">
                    <label>Username</label><br>
                    <input type="text" name="username" id="username" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px">
                </div>
                <div style="margin-bottom:8px" id="passwordRow">
                    <label>Password</label><br>
                    <input type="text" name="password" id="password" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px">
                </div>
                <div style="margin-bottom:8px">
                    <label>Email</label><br>
                    <input type="text" name="email" id="email" style="width:100%;padding:8px;border:1px solid #e5e7eb;border-radius:6px">
                </div>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" onclick="closeCreateModal()" style="padding:8px 12px;border-radius:6px;border:1px solid #e6eef9;background:#fff;cursor:pointer">Cancel</button>
                    <button type="submit" style="padding:8px 12px;background:var(--blue);color:#fff;border:none;border-radius:6px;cursor:pointer">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal handlers
        function openCreateModal(){
            document.getElementById('modalTitle').textContent = 'Create Admin';
            document.getElementById('formAction').value = 'create';
            document.getElementById('formId').value = '';
            document.getElementById('fullname').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('email').value = '';
            document.getElementById('passwordRow').style.display = 'block';
            document.getElementById('createModal').style.display = 'flex';
        }
        function openEditModal(btn){
            var id = btn.dataset.id || '';
            document.getElementById('modalTitle').textContent = 'Edit Admin';
            document.getElementById('formAction').value = 'update';
            document.getElementById('formId').value = id;
            document.getElementById('fullname').value = btn.dataset.fullname || '';
            document.getElementById('username').value = btn.dataset.username || '';
            document.getElementById('email').value = btn.dataset.email || '';
            // hide password on edit
            document.getElementById('passwordRow').style.display = 'none';
            document.getElementById('createModal').style.display = 'flex';
        }
        function closeCreateModal(){ document.getElementById('createModal').style.display = 'none'; }

        document.getElementById('openCreateBtn')?.addEventListener('click', openCreateModal);

        // convert existing Edit links to open modal when clicked
        document.querySelectorAll('a.btn-edit').forEach(function(a){
            // if link already has data attributes, use them; otherwise leave as-is
            a.addEventListener('click', function(e){
                e.preventDefault();
                var id = a.getAttribute('data-id');
                if (!id) {
                    // fallback: try to open edit via server (navigate)
                    window.location = a.href;
                    return;
                }
                openEditModal(a);
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e){ if (e.key === 'Escape') closeCreateModal(); });
    </script>

    <div id="footer">
        <div class="footer-wrap"><p>@South Loan & Finance Company Inc. 2024</p></div>
    </div>

</body>
</html>

