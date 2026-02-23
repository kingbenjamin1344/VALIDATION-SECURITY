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
            /* Pagination */
            .pager-container { display:flex; justify-content:flex-end; margin-top:12px; }
            .pager { display:flex; gap:8px; align-items:center; }
            .pager-btn { padding:6px 10px; background:#1E90FF; color:white; border-radius:6px; text-decoration:none; font-weight:600; }
            .pager-btn.disabled { background:#e0e0e0; color:#888; pointer-events:none; }
            .pager-current { padding:6px 10px; background:#ffffff; border-radius:6px; border:1px solid #ddd; min-width:42px; text-align:center; font-weight:600; }
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
            <li><a href="../upper_management/block.php" >Block User</a></li>
            <li><a href="../upper_management/manage.php">Manage Roles</a></li>
            <li><a href="../upper_management/userlist.php">Userlist</a></li>
             <li><a href="../upper_management/activity.php" class="active">Activity Logs</a></li>
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
       
       
        <?php
        // Ensure activity_log table exists
        @mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL,
            action VARCHAR(20) NOT NULL,
            device_name TEXT,
            ip VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

        // Pagination setup: show 5 rows per page
        $perPage = 5;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $offset = ($page - 1) * $perPage;

        // Count total rows matching roles
        $totalRows = 0;
        $countSql = "SELECT COUNT(*) as cnt FROM activity_log al LEFT JOIN users u ON al.username = u.username WHERE u.role IN ('admin','user','superadmin')";
        $cres = $conn->query($countSql);
        if ($cres) {
            $crow = $cres->fetch_assoc();
            $totalRows = (int)($crow['cnt'] ?? 0);
        }
        $totalPages = max(1, (int)ceil($totalRows / $perPage));

        // Fetch paginated logs
        $logs = [];
        $sql = "SELECT al.username, al.action, al.device_name, al.ip, al.created_at,
                       u.firstname, u.middlename, u.lastname, u.suffix, u.role
                FROM activity_log al
                LEFT JOIN users u ON al.username = u.username
            WHERE u.role IN ('admin','user','superadmin')
                ORDER BY al.created_at DESC
                LIMIT ?, ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ii', $offset, $perPage);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $logs[] = $row;
            }
            $stmt->close();
        }
        ?>

        <div class="card">
            <h2>Activity Logs</h2>
            <div style="overflow:auto">
            <table style="width:100%;border-collapse:collapse;margin-top:10px;">
                <thead>
                    <tr style="text-align:left;border-bottom:2px solid #eee;">
                        <th style="padding:8px">Full Name</th>
                        <th style="padding:8px">Action</th>
                        <th style="padding:8px">Device</th>
                        <th style="padding:8px">Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($logs) === 0): ?>
                    <tr><td colspan="4" style="padding:12px;text-align:center;color:#666">No activity logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l):
                        $fname = trim(($l['firstname'] ?? '') . ' ' . ($l['middlename'] ?? '') . ' ' . ($l['lastname'] ?? '') . ' ' . ($l['suffix'] ?? ''));
                        $displayName = $fname !== '' ? preg_replace('/\s+/', ' ', $fname) : ($l['username'] ?? '');
                        $action = htmlspecialchars($l['action'] ?? '');
                        $device = htmlspecialchars($l['device_name'] ?? '');
                        $dt = htmlspecialchars($l['created_at'] ?? '');
                    ?>
                        <tr style="border-bottom:1px solid #f0f0f0;">
                            <td style="padding:8px"><?php echo htmlspecialchars($displayName); ?><?php echo isset($l['role']) ? ' (' . htmlspecialchars($l['role']) . ')' : ''; ?></td>
                            <td style="padding:8px;text-transform:capitalize"><?php echo $action; ?></td>
                            <td style="padding:8px;max-width:360px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $device; ?></td>
                            <td style="padding:8px"><?php echo $dt; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
            <div class="pager-container">
                <div class="pager" role="navigation" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a class="pager-btn" href="?page=<?php echo $page - 1; ?>">Prev</a>
                    <?php else: ?>
                        <span class="pager-btn disabled">Prev</span>
                    <?php endif; ?>

                    <span class="pager-current"><?php echo $page; ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a class="pager-btn" href="?page=<?php echo $page + 1; ?>">Next</a>
                    <?php else: ?>
                        <span class="pager-btn disabled">Next</span>
                    <?php endif; ?>
                </div>
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
