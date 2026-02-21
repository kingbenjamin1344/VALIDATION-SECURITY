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

// Handle role update (moved from addrole.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $uid = (int)$_POST['user_id'];
    $newrole = mysqli_real_escape_string($conn, $_POST['role']);

    // Update users.role
    mysqli_query($conn, "UPDATE users SET role='". $newrole ."' WHERE id=". $uid);

    // Remove from role tables
    mysqli_query($conn, "DELETE FROM upper_management WHERE user_id=". $uid);
    mysqli_query($conn, "DELETE FROM superadmin WHERE user_id=". $uid);
    mysqli_query($conn, "DELETE FROM admin WHERE user_id=". $uid);

    // Insert into chosen role table if applicable
    if ($newrole === 'upper_management') {
        mysqli_query($conn, "INSERT INTO upper_management (user_id) VALUES (". $uid .")");
    } elseif ($newrole === 'superadmin') {
        mysqli_query($conn, "INSERT INTO superadmin (user_id) VALUES (". $uid .")");
    } elseif ($newrole === 'admin') {
        mysqli_query($conn, "INSERT INTO admin (user_id) VALUES (". $uid .")");
    }

    header('Location: manage.php?updated=1');
    exit;
}

// Fetch users with expanded name fields
$users = mysqli_query($conn, "SELECT id, firstname, middlename, lastname, suffix, email, username, role FROM users ORDER BY id DESC");
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
            <li><a href="../upper_management/dashboard.php" >Dashboard</a></li>
            <li><a href="../upper_management/manage.php" class="active">Manage Roles</a></li>
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
            <h1>Manage Roles</h1>
            <?php if (isset($_GET['updated'])): ?>
                <div style="background:#e6ffed;border:1px solid #b7f3c7;padding:10px;border-radius:6px;color:#155724;margin-bottom:12px;">Role updated.</div>
            <?php endif; ?>

            <div style="background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #eee;">
                            <th style="padding:8px">First</th>
                            <th style="padding:8px">Middle</th>
                            <th style="padding:8px">Last</th>
                            <th style="padding:8px">Suffix</th>
                            <th style="padding:8px">Email</th>
                            <th style="padding:8px">Username</th>
                            <th style="padding:8px">Role</th>
                            <th style="padding:8px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$users || mysqli_num_rows($users) === 0): ?>
                            <tr><td colspan="8" style="padding:12px">No users found.</td></tr>
                        <?php else: ?>
                            <?php while ($row = mysqli_fetch_assoc($users)): ?>
                                <tr style="border-bottom:1px solid #f1f1f1;">
                                    <td style="padding:8px"><?= htmlspecialchars($row['firstname']) ?></td>
                                    <td style="padding:8px"><?= htmlspecialchars($row['middlename']) ?></td>
                                    <td style="padding:8px"><?= htmlspecialchars($row['lastname']) ?></td>
                                    <td style="padding:8px"><?= htmlspecialchars($row['suffix']) ?></td>
                                    <td style="padding:8px"><?= htmlspecialchars($row['email']) ?></td>
                                    <td style="padding:8px"><?= htmlspecialchars($row['username']) ?></td>
                                    <td style="padding:8px"><?= htmlspecialchars($row['role']) ?></td>
                                    <td style="padding:8px">
                                        <form method="post" style="display:flex;gap:6px;align-items:center;">
                                            <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                            <select name="role" style="padding:6px;border-radius:4px;border:1px solid #ddd;">
                                                <option value="user" <?= $row['role']==='user'?'selected':'' ?>>user</option>
                                                <option value="admin" <?= $row['role']==='admin'?'selected':'' ?>>admin</option>
                                                <option value="superadmin" <?= $row['role']==='superadmin'?'selected':'' ?>>superadmin</option>
                                                <option value="upper_management" <?= $row['role']==='upper_management'?'selected':'' ?>>upper_management</option>
                                            </select>
                                            <button type="submit" style="background:#1E90FF;color:#fff;border:none;padding:8px 10px;border-radius:6px;">Set Role</button>
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
