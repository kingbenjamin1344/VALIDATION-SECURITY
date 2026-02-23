<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only upper_management can access
if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'upper_management') {
    // redirect according to actual role
    if ($role === 'superadmin') header('Location: ../superadmin/dashboard.php');
    elseif ($role === 'admin') header('Location: ../admin/dashboard.php');
    else header('Location: ../logform/indexes.php');
    exit();
}
// Fetch upper management user info for display
$um_first = '';
$um_last = '';
$um_initials = '';
if (isset($_SESSION['username'])) {
    $uname = $_SESSION['username'];
    $pst = $conn->prepare('SELECT firstname, lastname FROM users WHERE username = ? LIMIT 1');
    if ($pst) {
        $pst->bind_param('s', $uname);
        $pst->execute();
        $gres = $pst->get_result();
        if ($gres && $gres->num_rows) {
            $rw = $gres->fetch_assoc();
            $um_first = $rw['firstname'] ?? '';
            $um_last = $rw['lastname'] ?? '';
            $um_initials = strtoupper(substr(($um_first ?: 'U'),0,1) . substr(($um_last ?: ' '),0,1));
        }
        $pst->close();
    }
}

// Fetch counts for roles
$countSuper = 0; $countAdmin = 0; $countUser = 0;
try {
    $r = $conn->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $roleName = $row['role'];
            $cnt = (int)$row['cnt'];
            if ($roleName === 'superadmin') $countSuper = $cnt;
            elseif ($roleName === 'admin') $countAdmin = $cnt;
            elseif ($roleName === 'user') $countUser = $cnt;
        }
    }
} catch (mysqli_sql_exception $e) {
    // ignore errors; counts remain 0
}

// Overall count
$countOverall = $countSuper + $countAdmin + $countUser;
// Ensure users table has is_blocked column
$colCheck = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_blocked'");
if (!$colCheck || $colCheck->num_rows === 0) {
    @ $conn->query("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
}

// Handle block/unblock user (from upper management page)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_user'])) {
    $uid = (int)$_POST['block_user'];
    $cur = $conn->query("SELECT is_blocked FROM users WHERE id=". $uid);
    $curVal = 0;
    if ($cur && $cur->num_rows) {
        $r = $cur->fetch_assoc();
        $curVal = (int)($r['is_blocked'] ?? 0);
    }
    $newVal = $curVal ? 0 : 1;
    $conn->query("UPDATE users SET is_blocked=". $newVal ." WHERE id=". $uid);
    header('Location: block.php');
    exit;
}

// Fetch users (include admin, user, and superadmin)
$users = mysqli_query($conn, "SELECT id, firstname, lastname, email, username, role, IFNULL(is_blocked,0) AS is_blocked FROM users WHERE role IN ('admin','user','superadmin') ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UI</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }

        /* Left Sidebar */
        .left-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background-color: #1E90FF;
            color: white;
            padding-top: 60px;
            box-sizing: border-box;
        }

        .left-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .role-area {
            text-align: center;
            padding: 18px 12px;
        }

        .role-circle-small {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ffffff;
            color: #1E90FF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
            margin-bottom: 6px;
        }

        .role-label-small {
            color: #ffffff;
            font-size: 13px;
            text-transform: capitalize;
            display: block;
        }

        .left-sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
            padding: 15px 20px;
        }

        .left-sidebar ul li a:hover,
        .left-sidebar ul li a.active {
            background-color: #063970;
            border-radius: 5px;
        }

        /* Top Navbar */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 220px;
            right: 0;
            height: 60px;
            background-color: #1E90FF;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 20px;
            gap: 18px;
            z-index: 999;
        }

        .navbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .settings-icon,
        .logout-icon {
            font-size: 22px;
            cursor: pointer;
            color: white;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 600;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffffff;
            color: #1E90FF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        main {
            margin-left: 220px;
            padding: 90px 20px;
            width: 100%;
            background: #f5f7fb;
            min-height: 100vh;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 12px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card h2 {
            margin: 0;
            font-size: 34px;
            color: #1E90FF;
        }

        .card p {
            margin: 5px 0 0;
            color: #555;
        }

        /* Right Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: #5a6268;
            color: white;
            transition: right 0.3s;
            z-index: 1000;
            padding: 20px;
        }

        .sidebar.open {
            right: 0;
        }

        .sidebar .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-confirm { background:#28a745; color:white; border:none; padding:10px; flex:1; }
        .btn-cancel  { background:#dc3545; color:white; border:none; padding:10px; flex:1; }
    </style>
</head>

<body>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-small">UM</div>
            <div class="role-label-small">Upper Management</div>
        </div>
        <ul>
             <li><a href="../upper_management/dashboard.php">Dashboard</a></li>
            <li><a href="../upper_management/block.php" class="active">Block User</a></li>
            <li><a href="../upper_management/manage.php">Manage Roles</a></li>
            <li><a href="../upper_management/userlist.php">Userlist</a></li>
             <li><a href="../upper_management/activity.php">Activity Logs</a></li>
            
            
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
      
        <div class="navbar-title">Leave Management</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($um_initials ?: 'UM'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($um_first . ' ' . $um_last) ?: ($_SESSION['username'] ?? 'Upper Management')); ?></div>
        </div>

        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main -->
    <main>
        <div style="max-width:1100px;margin:0 auto;padding:6px;">
            <h1>User List</h1>

            <div style="background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #eee;">
                            <th style="padding:8px">First Name</th>
                            <th style="padding:8px">Last Name</th>
                            <th style="padding:8px">Email</th>
                            <th style="padding:8px">Username</th>
                            <th style="padding:8px">Role</th>
                            <th style="padding:8px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users || mysqli_num_rows($users) === 0): ?>
                            <tr><td colspan="6" style="padding:12px">No users found.</td></tr>
                        <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($users)): ?>
                                <tr style="border-bottom:1px solid #f1f1f1;">
                                    <td style="padding:8px"><?php echo htmlspecialchars($row['firstname']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($row['lastname']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td style="padding:8px;text-transform:capitalize"><?php echo htmlspecialchars($row['role']); ?></td>
                                    <td style="padding:8px">
                                        <form method="post" style="display:inline">
                                            <input type="hidden" name="block_user" value="<?php echo (int)$row['id']; ?>">
                                            <?php if (!empty($row['is_blocked'])): ?>
                                                <button type="submit" style="background:#28a745;color:#fff;border:none;padding:6px 8px;border-radius:4px;">Unblock</button>
                                            <?php else: ?>
                                                <button type="submit" style="background:#ff8800;color:#fff;border:none;padding:6px 8px;border-radius:4px;">Block</button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

  

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmLogout()">Yes</button>
            </div>
        </div>
    </div>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    function logout() {
        document.getElementById('logoutModal').style.display = "flex";
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = "none";
    }

    function confirmLogout() {
        // Redirect to server logout which destroys session and redirects to login.php
        window.location.href = '../logform/logout.php';
    }
</script>

</body>
</html>
