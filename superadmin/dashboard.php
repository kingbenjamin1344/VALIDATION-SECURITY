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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UI | Super Admin</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafd;
            display: flex;
            color: #1e293b;
        }

        /* Left Sidebar - Enhanced */
        .left-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, #0b2b4f 0%, #1a3a5f 100%);
            color: white;
            padding: 24px 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
            z-index: 100;
        }

        .role-area {
            text-align: center;
            padding: 20px 16px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .role-circle-large {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffffff 0%, #e6f0ff 100%);
            color: #0b2b4f;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            margin-bottom: 12px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .role-label {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 16px;
            border-radius: 30px;
            display: inline-block;
        }

        .left-sidebar ul {
            list-style: none;
            padding: 0 16px;
        }

        .left-sidebar ul li {
            margin-bottom: 6px;
        }

        .left-sidebar ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            display: block;
            padding: 12px 16px;
            border-radius: 10px;
            transition: all 0.2s ease;
        }

        .left-sidebar ul li a i {
            width: 24px;
            margin-right: 12px;
            font-size: 18px;
        }

        .left-sidebar ul li a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        .left-sidebar ul li a.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-left: 4px solid #ffd700;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Top Navbar - Enhanced */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 30px;
            gap: 24px;
            z-index: 99;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04);
        }

        .navbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 20px;
            font-weight: 600;
            color: #0b2b4f;
            letter-spacing: -0.3px;
        }

        .settings-icon {
            font-size: 20px;
            cursor: pointer;
            color: #64748b;
            transition: all 0.2s;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f1f5f9;
        }

        .settings-icon:hover {
            background: #e2e8f0;
            color: #0b2b4f;
            transform: rotate(90deg);
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px 6px 6px;
            border-radius: 40px;
            background: #f1f5f9;
            cursor: pointer;
            transition: all 0.2s;
        }

        .profile:hover {
            background: #e2e8f0;
        }

        .profile-circle {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0b2b4f 0%, #1e4b7a 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .profile-name {
            font-weight: 500;
            color: #1e293b;
            font-size: 14px;
        }

        .logout-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-icon:hover {
            background: #fecaca;
            transform: scale(1.05);
        }

        /* Main Content Area */
        main {
            margin-left: 260px;
            padding: 90px 30px 30px;
            width: 100%;
            min-height: 100vh;
            background: #f8fafd;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #0b2b4f;
            letter-spacing: -0.3px;
        }

        /* Stats Cards - Enhanced */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #0b2b4f, #1e4b7a);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #e8f0fe;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .card h2 {
            margin: 0;
            font-size: 34px;
            font-weight: 700;
            color: #0b2b4f;
            line-height: 1.2;
        }

        .card p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .card-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 12px;
        }

        /* Right Sidebar - Enhanced */
        .sidebar {
            position: fixed;
            top: 0;
            right: -320px;
            width: 320px;
            height: 100vh;
            background: white;
            box-shadow: -4px 0 20px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 200;
            padding: 24px;
        }

        .sidebar.open {
            right: 0;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #eef2f6;
        }

        .sidebar-header h3 {
            font-size: 20px;
            font-weight: 600;
            color: #0b2b4f;
        }

        .close-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            transition: all 0.2s;
            color: #64748b;
        }

        .close-btn:hover {
            background: #e2e8f0;
            color: #0b2b4f;
            transform: rotate(90deg);
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar ul li {
            margin-bottom: 8px;
        }

        .sidebar ul li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: #1e293b;
            font-weight: 500;
            transition: all 0.2s;
            background: #f8fafd;
        }

        .sidebar ul li a i {
            width: 20px;
            color: #0b2b4f;
        }

        .sidebar ul li a:hover {
            background: #e8f0fe;
            transform: translateX(4px);
        }

        /* Modal - Enhanced */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-box {
            background: white;
            padding: 32px;
            border-radius: 24px;
            width: 380px;
            max-width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .modal-icon.warning {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal-box h3 {
            font-size: 22px;
            font-weight: 600;
            color: #0b2b4f;
            margin-bottom: 8px;
            text-align: center;
        }

        .modal-box p {
            color: #64748b;
            text-align: center;
            margin-bottom: 24px;
            font-size: 15px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-modal {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal.confirm {
            background: #0b2b4f;
            color: white;
        }

        .btn-modal.confirm:hover {
            background: #1a3a5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 43, 79, 0.2);
        }

        .btn-modal.cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-modal.cancel:hover {
            background: #e2e8f0;
        }
    </style>
</head>

<body>
    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-large">SA</div>
            <span class="role-label">Super Admin</span>
        </div>
        <ul>
            <li><a href="../superadmin/dashboard.php" class="active"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
            <li><a href="../superadmin/user.php"><i class="fas fa-ban"></i>Block User</a></li>
            <li><a href="../superadmin/manage.php"><i class="fas fa-users-cog"></i>Manage Users</a></li>
            <li><a href="../superadmin/history.php"><i class="fas fa-history"></i>Leave History</a></li>
            <li><a href="../superadmin/activitylog.php"><i class="fas fa-clipboard-list"></i>Activity Logs</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Dashboard Overview</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($sa_initials ?: 'SA'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($sa_first . ' ' . $sa_last) ?: ($_SESSION['username'] ?? 'Super Admin')); ?></div>
        </div>

        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main Content -->
    <main>
        <div class="page-header">
            <h1>Analytics Dashboard</h1>
        </div>

        <!-- Stats Cards -->
        <div class="cards">
            <div class="card">
                <div class="card-icon"><i class="fas fa-users"></i></div>
                <h2><?php echo (int)$totalAll; ?></h2>
                <p>Total Users</p>
                <div class="card-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+12%</span>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-user-shield"></i></div>
                <h2><?php echo (int)$countAdmin; ?></h2>
                <p>Administrators</p>
                <div class="card-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+5%</span>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-user"></i></div>
                <h2><?php echo (int)$countUser; ?></h2>
                <p>Regular Users</p>
                <div class="card-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+8%</span>
                </div>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-calendar-check"></i></div>
                <h2><?php echo (int)$totalLeaves; ?></h2>
                <p>Leave Requests</p>
                <div class="card-trend">
                    <i class="fas fa-arrow-up"></i>
                    <span>+23%</span>
                </div>
            </div>
        </div>

        <!-- Additional Dashboard Content Could Go Here -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px;">
            <!-- Recent Activity Card -->
            <div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);">
                <h3 style="font-size: 18px; font-weight: 600; color: #0b2b4f; margin-bottom: 20px;">Recent Activity</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafd; border-radius: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: #e8f0fe; display: flex; align-items: center; justify-content: center; color: #0b2b4f;">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="font-weight: 500; margin-bottom: 4px;">New user registered</p>
                            <p style="font-size: 12px; color: #64748b;">5 minutes ago</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafd; border-radius: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: #e8f0fe; display: flex; align-items: center; justify-content: center; color: #0b2b4f;">
                            <i class="fas fa-calendar-plus"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="font-weight: 500; margin-bottom: 4px;">New leave request submitted</p>
                            <p style="font-size: 12px; color: #64748b;">15 minutes ago</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafd; border-radius: 12px;">
                        <div style="width: 36px; height: 36px; border-radius: 10px; background: #e8f0fe; display: flex; align-items: center; justify-content: center; color: #0b2b4f;">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <div style="flex: 1;">
                            <p style="font-weight: 500; margin-bottom: 4px;">User role updated</p>
                            <p style="font-size: 12px; color: #64748b;">1 hour ago</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Card -->
            <div style="background: linear-gradient(135deg, #0b2b4f 0%, #1a3a5f 100%); border-radius: 20px; padding: 24px; color: white;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 20px; opacity: 0.9;">Quick Stats</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <p style="opacity: 0.7; margin-bottom: 4px;">Pending Approvals</p>
                        <p style="font-size: 28px; font-weight: 700;">8</p>
                    </div>
                    <div>
                        <p style="opacity: 0.7; margin-bottom: 4px;">Active Sessions</p>
                        <p style="font-size: 28px; font-weight: 700;">24</p>
                    </div>
                    <div>
                        <p style="opacity: 0.7; margin-bottom: 4px;">System Health</p>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 28px; font-weight: 700;">98%</span>
                            <span style="background: #22c55e; padding: 4px 8px; border-radius: 20px; font-size: 12px; font-weight: 600;">Optimal</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Settings Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cog" style="margin-right: 8px;"></i>Settings</h3>
            <div class="close-btn" onclick="toggleSidebar()">&times;</div>
        </div>
        <ul>
            <li><a href="../security/input_security_question.php"><i class="fas fa-shield-alt"></i>Security Questions</a></li>
            <li><a href="../security/manage_security.php"><i class="fas fa-lock"></i>Security Answer Stored</a></li>

        </ul>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon warning">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout from your account?</p>
            <div class="modal-actions">
                <button class="btn-modal cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-modal confirm" onclick="confirmLogout()">Yes, Logout</button>
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
            window.location.href = '../logform/logout.php';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }

        // Close sidebar with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('sidebar').classList.remove('open');
            }
        });
    </script>
</body>
</html>