
<?php
session_start();
// Redirect users with roles away from the general `indexes.php`
if (isset($_SESSION['username'])) {
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
    if ($role === 'upper_management') {
        header('Location: ../upper_management/dashboard.php');
        exit();
    } elseif ($role === 'superadmin') {
        header('Location: ../superadmin/dashboard.php');
        exit();
    } elseif ($role === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit();
    }
}
// require DB connection and fetch user info for UI
require_once __DIR__ . '/../regform/config.php';

// If not logged in, redirect to login
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$username = $_SESSION['username'];
$firstname = '';
$lastname = '';
$initials = '';
$totalRequests = $pending = $approved = $declined = 0;

// Fetch user info
$stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE username = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $firstname = $row['firstname'];
        $lastname = $row['lastname'];
        $initials = strtoupper(substr($firstname,0,1) . substr($lastname,0,1));
    }
    $stmt->close();
}

// Fetch counts from leave_requests table (uses username column)
$countStmt = null;
try {
    $countStmt = $conn->prepare("SELECT
        COUNT(*) as total,
        SUM(status = 'pending') as pending,
        SUM(status = 'approved') as approved,
        SUM(status = 'declined') as declined
        FROM leave_requests WHERE username = ?");
    if ($countStmt) {
        $countStmt->bind_param('s', $username);
        $countStmt->execute();
        $cr = $countStmt->get_result();
        if ($cr && $cr->num_rows > 0) {
            $c = $cr->fetch_assoc();
            $totalRequests = $c['total'] ?? 0;
            $pending = $c['pending'] ?? 0;
            $approved = $c['approved'] ?? 0;
            $declined = $c['declined'] ?? 0;
        }
        $countStmt->close();
    }
} catch (mysqli_sql_exception $e) {
    $countsError = 'leave_requests table not found or missing columns.';
    $schemaSql = "CREATE TABLE IF NOT EXISTS leave_requests (\n  id INT AUTO_INCREMENT PRIMARY KEY,\n  username VARCHAR(100) NOT NULL,\n  leave_type VARCHAR(50) NOT NULL,\n  start_date DATE NOT NULL,\n  end_date DATE DEFAULT NULL,\n  reason TEXT,\n  status ENUM('pending','approved','declined') DEFAULT 'pending',\n  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
}

// Fetch user's approved/declined requests
$historyRequests = [];
try {
    $hr = $conn->prepare("SELECT id, leave_type, start_date, end_date, reason, status, created_at FROM leave_requests WHERE username = ? AND status IN ('approved','declined') ORDER BY created_at DESC");
    if ($hr) {
        $hr->bind_param('s', $username);
        $hr->execute();
        $res = $hr->get_result();
        while ($rw = $res->fetch_assoc()) {
            $historyRequests[] = $rw;
        }
        $hr->close();
    }
} catch (mysqli_sql_exception $e) {
    // ignore
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
            <div class="role-circle-small">U</div>
            <div class="role-label-small">User</div>
        </div>
        <ul>
   <li><a href="../logform/indexes.php" >Dashboard</a></li>
            <li><a href="../logform/requestleave.php" >Request Leave</a></li>
            <li><a href="../logform/history.php" class="active">Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()">&#9881;</span>
        <div class="navbar-title">Leave Management</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($firstname . ' ' . $lastname) ?: $_SESSION['username']); ?></div>
        </div>

        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main -->
    <main>
        <div style="max-width:1100px;margin:0 auto;">
            <h1>Leave History</h1>
            <div style="background:#fff;padding:12px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #eee;">
                            <th style="padding:8px">Type</th>
                            <th style="padding:8px">Start</th>
                            <th style="padding:8px">End</th>
                            <th style="padding:8px">Reason</th>
                            <th style="padding:8px">Submitted</th>
                            <th style="padding:8px">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historyRequests)): ?>
                            <tr><td colspan="6" style="padding:12px">No approved or declined requests.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historyRequests as $r): ?>
                                <tr style="border-bottom:1px solid #f1f1f1;">
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['leave_type']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['start_date']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['end_date']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['reason']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars($r['created_at']); ?></td>
                                    <td style="padding:8px"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
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
        // Redirect to server logout which destroys the session and sends user to login.php
        window.location.href = 'logout.php';
    }
</script>

</body>
</html>
