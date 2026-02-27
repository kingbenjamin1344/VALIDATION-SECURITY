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
// Ensure users table has is_blocked column
$colCheck = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_blocked'");
if (!$colCheck || $colCheck->num_rows === 0) {
    // best-effort: attempt to add column, ignore failure
    @$conn->query("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) DEFAULT 0");
}

// Handle block/unblock user (from superadmin page)
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
    header('Location: user.php');
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
    <title>Block User | Super Admin</title>
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

        .header-badge {
            background: #fee2e2;
            color: #991b1b;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
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

        .stat-icon.active-users {
            background: #dcfce7;
            color: #166534;
        }

        .stat-icon.blocked-users {
            background: #fee2e2;
            color: #991b1b;
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

        /* Table Container */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
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

        /* Alert Message */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .alert.success {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .alert i {
            font-size: 20px;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
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

        .user-email {
            font-size: 12px;
            color: #64748b;
        }

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .role-badge.superadmin {
            background: #818cf8;
            color: #312e81;
        }

        .role-badge.admin {
            background: #fbbf24;
            color: #92400e;
        }

        .role-badge.user {
            background: #34d399;
            color: #065f46;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.active {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.blocked {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.active {
            background: #22c55e;
            box-shadow: 0 0 0 2px #22c55e30;
        }

        .status-dot.blocked {
            background: #ef4444;
            box-shadow: 0 0 0 2px #ef444430;
        }

        /* Action Buttons */
        .action-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .action-btn i {
            font-size: 14px;
        }

        .action-btn.block {
            background: #ff8800;
            color: white;
        }

        .action-btn.block:hover {
            background: #e67700;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 136, 0, 0.2);
        }

        .action-btn.unblock {
            background: #28a745;
            color: white;
        }

        .action-btn.unblock:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
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

        .modal-icon.success {
            background: #dcfce7;
            color: #166534;
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
    </style>
</head>

<body>
    <?php
    // This would typically be in your PHP block at the top
    // For demonstration, setting sample values if not defined
    if (!isset($sa_initials)) $sa_initials = 'SA';
    if (!isset($sa_first)) $sa_first = '';
    if (!isset($sa_last)) $sa_last = '';
    ?>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-large">SA</div>
            <span class="role-label">Super Admin</span>
        </div>
        <ul>
            <li><a href="../superadmin/dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
            <li><a href="../superadmin/user.php" class="active"><i class="fas fa-ban"></i>Block User</a></li>
            <li><a href="../superadmin/manage.php"><i class="fas fa-users-cog"></i>Manage Users</a></li>
            <li><a href="../superadmin/history.php"><i class="fas fa-history"></i>Leave History</a></li>
            <li><a href="../superadmin/activitylog.php"><i class="fas fa-clipboard-list"></i>Activity Logs</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">User Management</div>

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
       

        <!-- Quick Stats -->
       

        <!-- User List -->
        <div class="table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="filter_user" placeholder="Search by name, email, or username...">
                </div>
                <div class="filter-tabs">
                    <span class="filter-tab active" onclick="filterByStatus('all')">All Users</span>
                    <span class="filter-tab" onclick="filterByStatus('active')">Active</span>
                    <span class="filter-tab" onclick="filterByStatus('blocked')">Blocked</span>
                </div>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    User has been successfully deleted.
                </div>
            <?php endif; ?>

            <table class="modern-table" id="table_user">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users || mysqli_num_rows($users) === 0): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <p>No users found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($users)): 
                            $status = empty($row['is_blocked']) ? 'active' : 'blocked';
                        ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php 
                                                $initial = strtoupper(substr($row['firstname'] ?? '', 0, 1) ?: 'U');
                                                echo $initial;
                                            ?>
                                        </div>
                                        <div class="user-details">
                                            <span class="user-name"><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($row['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;"><?php echo htmlspecialchars($row['username']); ?></code></td>
                                <td>
                                    <span class="role-badge <?php echo $row['role']; ?>">
                                        <?php echo htmlspecialchars($row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status; ?>">
                                        <span class="status-dot <?php echo $status; ?>"></span>
                                        <?php echo $status === 'active' ? 'Active' : 'Blocked'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="block_user" value="<?php echo (int)$row['id']; ?>">
                                        <?php if (!empty($row['is_blocked'])): ?>
                                            <button type="submit" class="action-btn unblock" onclick="return confirmUnblock(event, '<?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>')">
                                                <i class="fas fa-unlock-alt"></i>
                                                <span>Unblock</span>
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="action-btn block" onclick="return confirmBlock(event, '<?php echo htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?>')">
                                                <i class="fas fa-ban"></i>
                                                <span>Block</span>
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
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

    <!-- Block Confirmation Modal -->
    <div id="blockConfirmModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 id="blockModalTitle">Confirm Block</h3>
            <p id="blockModalMessage">Are you sure you want to block this user?</p>
            <div class="modal-actions">
                <button class="btn-modal cancel" onclick="closeBlockModal()">Cancel</button>
                <button class="btn-modal confirm" id="confirmBlockBtn">Yes, Block</button>
            </div>
        </div>
    </div>

    <form id="blockForm" method="post" style="display:none">
        <input type="hidden" name="block_user" id="blockUserId" value="">
    </form>

    <script>
        let currentAction = null;
        let currentUserId = null;
        let currentUserName = '';

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
            var input = document.getElementById('filter_user');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('table_user');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var txt = rows[i].textContent.toLowerCase();
                rows[i].style.display = txt.indexOf(filter) > -1 ? '' : 'none';
            }
        }

        // Filter by status
        function filterByStatus(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.toLowerCase().includes(status)) {
                    tab.classList.add('active');
                }
            });

            var table = document.getElementById('table_user');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            
            for (var i = 0; i < rows.length; i++) {
                if (status === 'all') {
                    rows[i].style.display = '';
                } else {
                    var rowStatus = rows[i].getAttribute('data-status');
                    rows[i].style.display = rowStatus === status ? '' : 'none';
                }
            }
        }

        // Block confirmation
        function confirmBlock(event, userName) {
            event.preventDefault();
            currentAction = 'block';
            currentUserName = userName;
            const form = event.target.closest('form');
            const userId = form.querySelector('input[name="block_user"]').value;
            
            document.getElementById('blockUserId').value = userId;
            document.getElementById('blockModalTitle').textContent = 'Confirm Block';
            document.getElementById('blockModalMessage').textContent = `Are you sure you want to block ${userName}? They will lose access to the system.`;
            document.getElementById('confirmBlockBtn').textContent = 'Yes, Block';
            document.getElementById('confirmBlockBtn').className = 'btn-modal confirm';
            document.getElementById('blockConfirmModal').style.display = 'flex';
            
            return false;
        }

        // Unblock confirmation
        function confirmUnblock(event, userName) {
            event.preventDefault();
            currentAction = 'unblock';
            currentUserName = userName;
            const form = event.target.closest('form');
            const userId = form.querySelector('input[name="block_user"]').value;
            
            document.getElementById('blockUserId').value = userId;
            document.getElementById('blockModalTitle').textContent = 'Confirm Unblock';
            document.getElementById('blockModalMessage').textContent = `Are you sure you want to unblock ${userName}? They will regain access to the system.`;
            document.getElementById('confirmBlockBtn').textContent = 'Yes, Unblock';
            document.getElementById('confirmBlockBtn').className = 'btn-modal confirm';
            document.getElementById('blockConfirmModal').style.display = 'flex';
            
            return false;
        }

        function closeBlockModal() {
            document.getElementById('blockConfirmModal').style.display = 'none';
        }

        // Submit block/unblock form
        document.getElementById('confirmBlockBtn')?.addEventListener('click', function() {
            document.getElementById('blockForm').submit();
        });

        // Event listener for search
        document.getElementById('filter_user')?.addEventListener('input', filterTable);

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