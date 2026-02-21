<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only superadmin can access
if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'superadmin') {
    if ($role === 'admin') header('Location: ../admin/dashboard.php');
    elseif ($role === 'upper_management') header('Location: ../upper_management/dashboard.php');
    else header('Location: ../logform/indexes.php');
    exit();
}
// Fetch superadmin user info for display
$sa_first = '';
$sa_last = '';
$sa_initials = '';
if (isset($_SESSION['username'])) {
    $uname = $_SESSION['username'];
    $pst = $conn->prepare('SELECT firstname, lastname FROM users WHERE username = ? LIMIT 1');
    if ($pst) {
        $pst->bind_param('s', $uname);
        $pst->execute();
        $gres = $pst->get_result();
        if ($gres && $gres->num_rows) {
            $rw = $gres->fetch_assoc();
            $sa_first = $rw['firstname'] ?? '';
            $sa_last = $rw['lastname'] ?? '';
            $sa_initials = strtoupper(substr(($sa_first ?: 'S'),0,1) . substr(($sa_last ?: 'A'),0,1));
        }
        $pst->close();
    }
}

// Fetch counts: total users (all), admins only, users only, total leave requests
$totalAll = 0; $countAdmin = 0; $countUser = 0; $totalLeaves = 0;
try {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM users");
    if ($r) { $row = $r->fetch_assoc(); $totalAll = (int)($row['cnt'] ?? 0); }
    $r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='admin'");
    if ($r) { $row = $r->fetch_assoc(); $countAdmin = (int)($row['cnt'] ?? 0); }
    $r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='user'");
    if ($r) { $row = $r->fetch_assoc(); $countUser = (int)($row['cnt'] ?? 0); }
    $r = $conn->query("SELECT COUNT(*) as cnt FROM leave_requests");
    if ($r) { $row = $r->fetch_assoc(); $totalLeaves = (int)($row['cnt'] ?? 0); }
} catch (mysqli_sql_exception $e) {
    // ignore - keep defaults
}

// Fetch all leave requests (include responded_at and responded_by if present)
$colRes = $conn->query("SHOW COLUMNS FROM leave_requests LIKE 'responded_at'");
$colRes2 = $conn->query("SHOW COLUMNS FROM leave_requests LIKE 'responded_by'");
if ($colRes && $colRes->num_rows > 0 && $colRes2 && $colRes2->num_rows > 0) {
    // include responder name via LEFT JOIN to users table when responded_by exists
    $hist = $conn->query("SELECT lr.id, lr.username, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, lr.responded_at, lr.responded_by, r.firstname AS responder_firstname, r.middlename AS responder_middlename, r.lastname AS responder_lastname, r.role AS responder_role FROM leave_requests lr LEFT JOIN users r ON lr.responded_by = r.username ORDER BY lr.created_at DESC");
} elseif ($colRes && $colRes->num_rows > 0) {
    $hist = $conn->query("SELECT id, username, leave_type, start_date, end_date, reason, status, created_at, responded_at, NULL as responded_by, NULL as responder_firstname, NULL as responder_middlename, NULL as responder_lastname FROM leave_requests ORDER BY created_at DESC");
} else {
    $hist = $conn->query("SELECT id, username, leave_type, start_date, end_date, reason, status, created_at, NULL as responded_at, NULL as responded_by, NULL as responder_firstname, NULL as responder_middlename, NULL as responder_lastname FROM leave_requests ORDER BY created_at DESC");
}
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
            <div class="role-circle-small">SA</div>
            <div class="role-label-small">Super Admin</div>
        </div>
        <ul>
                 <li><a href="../superadmin/dashboard.php" >Dashboard</a></li>
            <li><a href="../superadmin/user.php" >Block User</a></li>
            <li><a href="../superadmin/manage.php">Manage User List</a></li>
            <li><a href="../superadmin/history.php" class="active">Leave History</a></li>
            <li><a href="../superadmin/activitylog.php">Activity Logs</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()">&#9881;</span>
        <div class="navbar-title">Leave Management</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($sa_initials ?: 'SA'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($sa_first . ' ' . $sa_last) ?: ($_SESSION['username'] ?? 'Super Admin')); ?></div>
        </div>

        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main -->
    <main>
        <div style="max-width:1100px;margin:0 auto;padding:6px;">
            <h1>All Leave Requests</h1>

            <div style="background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #eee;">
                            <th style="padding:8px">Username</th>
                            <th style="padding:8px">Leave Type</th>
                            <th style="padding:8px">Start Date</th>
                            <th style="padding:8px">End Date</th>
                            <th style="padding:8px">Reason</th>
                            <th style="padding:8px">Status</th>
                            <th style="padding:8px">Requested At</th>
                            <th style="padding:8px">Responded At</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$hist || mysqli_num_rows($hist) === 0): ?>
                            <tr><td colspan="8" style="padding:12px">No requests found.</td></tr>
                        <?php else: ?>
                            <?php while ($r = mysqli_fetch_assoc($hist)): ?>
                                <tr style="border-bottom:1px solid #f1f1f1;">
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['username']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['leave_type']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['start_date']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['end_date']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['reason']); ?></td>
                                    <td style="padding:8px;text-transform:capitalize"><?php echo htmlspecialchars($r['status']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['created_at']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['responded_at'] ?? ''); ?></td>
                                    <td style="padding:8px">
                                        <?php
                                            if (!empty($r['responder_firstname']) || !empty($r['responder_lastname'])) {
                                                $name = trim(($r['responder_firstname'] ?? '') . ' ' . ($r['responder_middlename'] ?? '') . ' ' . ($r['responder_lastname'] ?? ''));
                                                $display = trim(preg_replace('/\s+/', ' ', $name));
                                                if (!empty($r['responder_role'])) $display .= ' (' . $r['responder_role'] . ')';
                                                echo htmlspecialchars($display);
                                            } else {
                                                $fallback = $r['responded_by'] ?? '';
                                                if (!empty($r['responder_role'])) $fallback .= ' (' . $r['responder_role'] . ')';
                                                echo htmlspecialchars($fallback);
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Right Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()">&times;</span>
        <ul>
            <li><a href="../security/input_security_question.php">Set Security</a></li>
        </ul>
    </div>

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
        // Redirect to server logout which destroys session and redirects to login
        window.location.href = '../logform/logout.php';
    }
</script>

</body>
</html>