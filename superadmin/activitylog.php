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
    <title>Activity Logs | Super Admin</title>
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
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 600;
            color: #0b2b4f;
            letter-spacing: -0.3px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .date-range {
            display: flex;
            align-items: center;
            gap: 8px;
            background: white;
            padding: 8px 16px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 14px;
        }

        .date-range i {
            color: #0b2b4f;
        }

        .refresh-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: white;
            border: 1px solid #e2e8f0;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background: #0b2b4f;
            color: white;
            transform: rotate(180deg);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .stat-icon.total {
            background: #e8f0fe;
            color: #0b2b4f;
        }

        .stat-icon.online {
            background: #d4edda;
            color: #155724;
        }

        .stat-icon.offline {
            background: #f8d7da;
            color: #721c24;
        }

        .stat-icon.devices {
            background: #fff3cd;
            color: #856404;
        }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            color: #0b2b4f;
            margin-bottom: 4px;
        }

        .stat-info p {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        /* Activity Card */
        .activity-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 i {
            color: #0b2b4f;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 0 16px;
            width: 320px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .search-box:focus-within {
            border-color: #0b2b4f;
            box-shadow: 0 0 0 3px rgba(11, 43, 79, 0.1);
        }

        .search-box i {
            color: #64748b;
            font-size: 14px;
        }

        .search-box input {
            border: none;
            background: transparent;
            padding: 14px 12px;
            width: 100%;
            outline: none;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }

        .filter-tabs {
            display: flex;
            gap: 8px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
        }

        .filter-tab {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            color: #64748b;
        }

        .filter-tab.active {
            background: white;
            color: #0b2b4f;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* Enhanced Table */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table thead tr {
            background: #f8fafd;
            border-radius: 12px;
        }

        .modern-table th {
            text-align: left;
            padding: 16px 12px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .modern-table td {
            padding: 16px 12px;
            border-bottom: 1px solid #eef2f6;
            font-size: 14px;
        }

        .modern-table tbody tr {
            transition: all 0.2s;
        }

        .modern-table tbody tr:hover {
            background: #f8fafd;
        }

        /* User Info */
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #e8f0fe;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .user-role {
            font-size: 12px;
            color: #64748b;
        }

        /* Device Info */
        .device-info {
            display: flex;
            align-items: center;
            gap: 8px;
            max-width: 360px;
        }

        .device-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0b2b4f;
        }

        .device-details {
            flex: 1;
            overflow: hidden;
        }

        .device-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }

        .device-ip {
            font-size: 11px;
            color: #64748b;
            font-family: monospace;
        }

        /* Time Badges */
        .time-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
        }

        .time-badge.login {
            background: #e8f0fe;
            color: #0b2b4f;
        }

        .time-badge.logout {
            background: #f1f5f9;
            color: #64748b;
        }

        .time-badge i {
            font-size: 12px;
        }

        .time-value {
            font-weight: 600;
            margin-left: 4px;
        }

        /* Status Indicator */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-indicator.online {
            background: #d4edda;
            color: #155724;
        }

        .status-indicator.offline {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.online {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a74530;
        }

        .status-dot.offline {
            background: #94a3b8;
            box-shadow: 0 0 0 2px #94a3b830;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }

        .empty-state p {
            color: #64748b;
            font-size: 16px;
        }

        /* Pagination - Enhanced */
        .pager-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .pager {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pager-btn {
            padding: 10px 16px;
            background: white;
            color: #0b2b4f;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pager-btn:hover:not(.disabled) {
            background: #0b2b4f;
            color: white;
            border-color: #0b2b4f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 43, 79, 0.2);
        }

        .pager-btn.disabled {
            background: #f1f5f9;
            color: #94a3b8;
            border-color: #e2e8f0;
            pointer-events: none;
            opacity: 0.6;
        }

        .pager-current {
            padding: 10px 18px;
            background: #0b2b4f;
            color: white;
            border-radius: 10px;
            min-width: 50px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #0b2b4f;
        }

        /* Sidebar */
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

        /* Modal */
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
            <li><a href="../superadmin/dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
            <li><a href="../superadmin/user.php"><i class="fas fa-ban"></i>Block User</a></li>
            <li><a href="../superadmin/manage.php"><i class="fas fa-users-cog"></i>Manage Users</a></li>
            <li><a href="../superadmin/history.php"><i class="fas fa-history"></i>Leave History</a></li>
            <li><a href="../superadmin/activitylog.php" class="active"><i class="fas fa-clipboard-list"></i>Activity Logs</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Activity Monitoring</div>

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

        // Fetch all logs for pairing (login + next logout) then paginate
        $raw = [];
        $sql = "SELECT al.username, al.action, al.device_name, al.ip, al.created_at,
                       u.firstname, u.middlename, u.lastname, u.suffix, u.role
                FROM activity_log al
                LEFT JOIN users u ON al.username = u.username
                WHERE u.role IN ('admin','user','superadmin')
                ORDER BY al.created_at ASC";
        $resAll = $conn->query($sql);
        if ($resAll) {
            while ($r = $resAll->fetch_assoc()) $raw[] = $r;
        }

        // Calculate online users (those with login but no logout)
        $onlineUsers = 0;
        $totalSessions = 0;
        $uniqueDevices = [];

        // Pair logins with the next logout for the same username
        $pending = [];
        $paired = [];
        foreach ($raw as $row) {
            $u = $row['username'] ?? '';
            $device = $row['device_name'] ?? '';
            $ip = $row['ip'] ?? '';
            $act = strtolower(trim((string)($row['action'] ?? '')));
            
            if (!empty($device) && !in_array($device, $uniqueDevices)) {
                $uniqueDevices[] = $device;
            }
            
            if ($act === 'login') {
                $pending[$u][] = $row;
                $totalSessions++;
            } elseif ($act === 'logout') {
                if (!empty($pending[$u])) {
                    $login = array_pop($pending[$u]);
                    $paired[] = [
                        'username' => $u,
                        'firstname' => $login['firstname'] ?? '',
                        'middlename' => $login['middlename'] ?? '',
                        'lastname' => $login['lastname'] ?? '',
                        'suffix' => $login['suffix'] ?? '',
                        'role' => $login['role'] ?? '',
                        'device' => $login['device_name'] ?? $row['device_name'] ?? '',
                        'ip' => $login['ip'] ?? $row['ip'] ?? '',
                        'login_at' => $login['created_at'] ?? null,
                        'logout_at' => $row['created_at'] ?? null,
                    ];
                    if (empty($pending[$u])) unset($pending[$u]);
                } else {
                    $paired[] = [
                        'username' => $u,
                        'firstname' => $row['firstname'] ?? '',
                        'middlename' => $row['middlename'] ?? '',
                        'lastname' => $row['lastname'] ?? '',
                        'suffix' => $row['suffix'] ?? '',
                        'role' => $row['role'] ?? '',
                        'device' => $row['device_name'] ?? '',
                        'ip' => $row['ip'] ?? '',
                        'login_at' => null,
                        'logout_at' => $row['created_at'] ?? null,
                    ];
                }
            }
        }

        // Count online users
        $onlineUsers = count($pending);

        // leftover pending logins (no logout yet)
        foreach ($pending as $uname => $list) {
            foreach ($list as $login) {
                $paired[] = [
                    'username' => $uname,
                    'firstname' => $login['firstname'] ?? '',
                    'middlename' => $login['middlename'] ?? '',
                    'lastname' => $login['lastname'] ?? '',
                    'suffix' => $login['suffix'] ?? '',
                    'role' => $login['role'] ?? '',
                    'device' => $login['device_name'] ?? '',
                    'ip' => $login['ip'] ?? '',
                    'login_at' => $login['created_at'] ?? null,
                    'logout_at' => null,
                ];
            }
        }

        // sort paired rows by latest event (logout if present else login) desc
        usort($paired, function($a, $b){
            $ta = $a['logout_at'] ?? $a['login_at'];
            $tb = $b['logout_at'] ?? $b['login_at'];
            $sa = $ta ? strtotime($ta) : 0;
            $sb = $tb ? strtotime($tb) : 0;
            return $sb <=> $sa;
        });

        $totalRows = count($paired);
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $totalPages) $page = $totalPages;
        $offset = ($page - 1) * $perPage;
        $logs = array_slice($paired, $offset, $perPage);
        ?>

      
        <!-- Stats Cards -->
        

        <!-- Activity Card -->
        <div class="activity-card">
            <div class="card-header">
                <h2>
                    <i class="fas fa-clipboard-list"></i>
                    Session History
                </h2>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="filter_activity" placeholder="Search by name, device, or IP...">
                </div>
            </div>

            <div class="filter-tabs" style="margin-bottom: 20px;">
                <span class="filter-tab active" onclick="filterByStatus('all')">All Sessions</span>
                <span class="filter-tab" onclick="filterByStatus('online')">Online</span>
                <span class="filter-tab" onclick="filterByStatus('offline')">Completed</span>
            </div>

            <div style="overflow-x: auto;">
                <table class="modern-table" id="table_activity">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Device & IP</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (count($logs) === 0): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-history"></i>
                                    <p>No activity logs found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $l):
                            $fname = trim(($l['firstname'] ?? '') . ' ' . ($l['middlename'] ?? '') . ' ' . ($l['lastname'] ?? '') . ' ' . ($l['suffix'] ?? ''));
                            $displayName = $fname !== '' ? preg_replace('/\s+/', ' ', $fname) : ($l['username'] ?? '');
                            $device = htmlspecialchars($l['device'] ?? '');
                            $ip = htmlspecialchars($l['ip'] ?? '');
                            $loginDt = $l['login_at'] ?? '';
                            $logoutDt = $l['logout_at'] ?? '';
                            $isOnline = empty($logoutDt) && !empty($loginDt);
                            
                            // Format dates
                            $loginFormatted = !empty($loginDt) ? date('M d, Y H:i:s', strtotime($loginDt)) : '';
                            $logoutFormatted = !empty($logoutDt) ? date('M d, Y H:i:s', strtotime($logoutDt)) : '';
                            
                            // Determine device icon
                            $deviceIcon = 'fa-laptop';
                            if (strpos(strtolower($device), 'mobile') !== false || strpos(strtolower($device), 'phone') !== false) {
                                $deviceIcon = 'fa-mobile-alt';
                            } elseif (strpos(strtolower($device), 'tablet') !== false) {
                                $deviceIcon = 'fa-tablet-alt';
                            }
                        ?>
                            <tr data-status="<?php echo $isOnline ? 'online' : 'offline'; ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php 
                                                $initial = strtoupper(substr($l['firstname'] ?? $l['username'] ?? 'U', 0, 1));
                                                echo $initial;
                                            ?>
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name"><?php echo htmlspecialchars($displayName); ?></span>
                                            <span class="user-role"><?php echo htmlspecialchars($l['role'] ?? 'user'); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="device-info">
                                        <div class="device-icon">
                                            <i class="fas <?php echo $deviceIcon; ?>"></i>
                                        </div>
                                        <div class="device-details">
                                            <div class="device-name"><?php echo $device ?: 'Unknown Device'; ?></div>
                                            <div class="device-ip"><?php echo $ip ?: 'IP not recorded'; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($loginDt)): ?>
                                        <span class="time-badge login">
                                            <i class="fas fa-sign-in-alt"></i>
                                            <span class="time-value"><?php echo $loginFormatted; ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($logoutDt)): ?>
                                        <span class="time-badge logout">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span class="time-value"><?php echo $logoutFormatted; ?></span>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-indicator <?php echo $isOnline ? 'online' : 'offline'; ?>">
                                        <span class="status-dot <?php echo $isOnline ? 'online' : 'offline'; ?>"></span>
                                        <?php echo $isOnline ? 'Online' : 'Offline'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pager-container">
                <div class="pager" role="navigation" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a class="pager-btn" href="?page=<?php echo $page - 1; ?>">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                    <?php else: ?>
                        <span class="pager-btn disabled">
                            <i class="fas fa-chevron-left"></i> Prev
                        </span>
                    <?php endif; ?>

                    <span class="pager-current"><?php echo $page; ?></span>

                    <?php if ($page < $totalPages): ?>
                        <a class="pager-btn" href="?page=<?php echo $page + 1; ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="pager-btn disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
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

        // Filter table function
        function filterTable() {
            var input = document.getElementById('filter_activity');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('table_activity');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            var activeStatus = document.querySelector('.filter-tab.active')?.textContent.toLowerCase() || 'all sessions';
            
            for (var i = 0; i < rows.length; i++) {
                // Skip empty state row
                if (rows[i].querySelector('.empty-state')) continue;
                
                var txt = rows[i].textContent.toLowerCase();
                var matchesSearch = txt.indexOf(filter) > -1;
                
                var rowStatus = rows[i].getAttribute('data-status') || '';
                var matchesStatus = activeStatus === 'all sessions' || 
                                   (activeStatus === 'online' && rowStatus === 'online') ||
                                   (activeStatus === 'completed' && rowStatus === 'offline');
                
                rows[i].style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        // Filter by status
        function filterByStatus(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (status === 'all' && tab.textContent.toLowerCase().includes('all')) {
                    tab.classList.add('active');
                } else if (status === 'online' && tab.textContent.toLowerCase().includes('online')) {
                    tab.classList.add('active');
                } else if (status === 'offline' && tab.textContent.toLowerCase().includes('completed')) {
                    tab.classList.add('active');
                }
            });
            
            filterTable();
        }

        // Auto-refresh every 30 seconds (optional)
        // setInterval(() => location.reload(), 30000);

        // Event listeners
        document.getElementById('filter_activity')?.addEventListener('input', filterTable);

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }

        // Close sidebar with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.getElementById('sidebar').classList.remove('open');
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>