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

// Handle role update (allow superadmin to change roles to admin/user)
$showRoleModal = false;
$modalFullName = '';
$modalNewRole = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $uid = (int)$_POST['user_id'];
    $newrole = mysqli_real_escape_string($conn, $_POST['role']);

    mysqli_query($conn, "UPDATE users SET role='". $newrole ."' WHERE id=". $uid);

    // remove from role-specific tables
    mysqli_query($conn, "DELETE FROM upper_management WHERE user_id=". $uid);
    mysqli_query($conn, "DELETE FROM superadmin WHERE user_id=". $uid);
    mysqli_query($conn, "DELETE FROM admin WHERE user_id=". $uid);

    if ($newrole === 'upper_management') {
        mysqli_query($conn, "INSERT INTO upper_management (user_id) VALUES (". $uid .")");
    } elseif ($newrole === 'superadmin') {
        mysqli_query($conn, "INSERT INTO superadmin (user_id) VALUES (". $uid .")");
    } elseif ($newrole === 'admin') {
        mysqli_query($conn, "INSERT INTO admin (user_id) VALUES (". $uid .")");
    }

    // Fetch the user's full name for modal display
    $uRes = mysqli_query($conn, "SELECT firstname, middlename, lastname, suffix FROM users WHERE id=". $uid ." LIMIT 1");
    if ($uRes && mysqli_num_rows($uRes) > 0) {
        $u = mysqli_fetch_assoc($uRes);
        $parts = array_filter([trim($u['firstname'] ?? ''), trim($u['middlename'] ?? ''), trim($u['lastname'] ?? ''), trim($u['suffix'] ?? '')]);
        $modalFullName = implode(' ', $parts);
    }
    $modalNewRole = $newrole;
    $showRoleModal = true;

    // don't redirect; show a confirmation modal instead
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $delId = (int)$_POST['delete_user'];
    mysqli_query($conn, "DELETE FROM upper_management WHERE user_id=". $delId);
    mysqli_query($conn, "DELETE FROM superadmin WHERE user_id=". $delId);
    mysqli_query($conn, "DELETE FROM admin WHERE user_id=". $delId);
    mysqli_query($conn, "DELETE FROM users WHERE id=". $delId);
    header('Location: manage.php?deleted=1');
    exit;
}

// Fetch admin, user and superadmin rows for listing
$users = mysqli_query($conn, "SELECT id, firstname, middlename, lastname, suffix, age, birthdate, email, username, role, IFNULL(is_blocked,0) AS is_blocked, purok, barangay, municipality, country, zipcode FROM users WHERE role IN ('admin','user','superadmin') ORDER BY id DESC");
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

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn-primary {
            background: #0b2b4f;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #1a3a5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 43, 79, 0.2);
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
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            border-color: #0b2b4f20;
        }

        .stat-icon {
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

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0b2b4f;
            margin-bottom: 4px;
        }

        .stat-label {
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
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #f1f5f9;
            border-radius: 10px;
            padding: 0 16px;
            width: 300px;
        }

        .search-box i {
            color: #64748b;
            font-size: 14px;
        }

        .search-box input {
            border: none;
            background: transparent;
            padding: 12px 12px;
            width: 100%;
            outline: none;
            font-size: 14px;
        }

        .filter-badge {
            display: flex;
            gap: 8px;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
        }

        .badge.active {
            background: #0b2b4f;
            color: white;
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

        /* User Role Badges */
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

        /* Status Indicators */
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 6px;
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

        .status-dot.deactive {
            background: #ef4444;
            box-shadow: 0 0 0 2px #ef444430;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }

        .action-btn.role {
            background: #e8f0fe;
            color: #0b2b4f;
        }

        .action-btn.role:hover {
            background: #0b2b4f;
            color: white;
        }

        .action-btn.view {
            background: #f0f9ff;
            color: #0284c7;
        }

        .action-btn.view:hover {
            background: #0284c7;
            color: white;
        }

        .action-btn.delete {
            background: #fef2f2;
            color: #dc2626;
        }

        .action-btn.delete:hover {
            background: #dc2626;
            color: white;
        }

        /* Role Select */
        .role-select {
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            outline: none;
            background: white;
            cursor: pointer;
        }

        .role-select:focus {
            border-color: #0b2b4f;
            box-shadow: 0 0 0 3px #0b2b4f20;
        }

        /* Modal Styles */
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
            width: 400px;
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

        .modal-icon.success {
            background: #dcfce7;
            color: #166534;
        }

        .modal-icon.warning {
            background: #fee2e2;
            color: #991b1b;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-modal {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-modal.confirm {
            background: #0b2b4f;
            color: white;
        }

        .btn-modal.confirm:hover {
            background: #1a3a5f;
        }

        .btn-modal.cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-modal.cancel:hover {
            background: #e2e8f0;
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
        }

        .close-btn:hover {
            background: #e2e8f0;
            transform: rotate(90deg);
        }

        /* Alert Messages */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
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
            <li><a href="../superadmin/manage.php" class="active"><i class="fas fa-users-cog"></i>Manage Users</a></li>
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
        <div class="page-header">
            <h1>Manage User List</h1>
            <div class="header-actions">
                <button class="btn-primary" onclick="window.location.href='../regform/register.php'">
                    <i class="fas fa-user-plus"></i> Add New User
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="filter_manage" placeholder="Search users...">
                </div>
                <div class="filter-badge">
                    <span class="badge active" onclick="filterRole('all')">All</span>
                    <span class="badge" onclick="filterRole('admin')">Admin</span>
                    <span class="badge" onclick="filterRole('user')">User</span>
                    <span class="badge" onclick="filterRole('superadmin')">Super Admin</span>
                </div>
            </div>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i>
                    User has been successfully deleted.
                </div>
            <?php endif; ?>

            <table class="modern-table" id="table_manage">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Age</th>
                        <th>Birthdate</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users || mysqli_num_rows($users) === 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px;">
                                <i class="fas fa-users-slash" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                                <p style="color: #64748b;">No users found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($users)): ?>
                            <tr data-role="<?php echo $row['role']; ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; color: #475569;">
                                            <?php 
                                                $initials = strtoupper(substr($row['firstname'] ?? '', 0, 1) . substr($row['lastname'] ?? '', 0, 1));
                                                echo $initials ?: 'U';
                                            ?>
                                        </div>
                                        <span style="font-weight: 500;"><?php
                                            $nameParts = array_filter([
                                                trim($row['firstname'] ?? ''),
                                                trim($row['middlename'] ?? ''),
                                                trim($row['lastname'] ?? ''),
                                                trim($row['suffix'] ?? '')
                                            ]);
                                            echo htmlspecialchars(implode(' ', $nameParts));
                                        ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['age']); ?></td>
                                <td><?php echo htmlspecialchars($row['birthdate']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 6px;"><?php echo htmlspecialchars($row['username']); ?></code></td>
                                <td>
                                    <span class="role-badge <?php echo $row['role']; ?>">
                                        <?php echo htmlspecialchars($row['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="status-indicator">
                                        <span class="status-dot <?php echo empty($row['is_blocked']) ? 'active' : 'deactive'; ?>"></span>
                                        <span><?php echo empty($row['is_blocked']) ? 'Active' : 'Deactivated'; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <form method="post" style="display: flex; gap: 6px; align-items: center;">
                                            <input type="hidden" name="user_id" value="<?php echo (int)$row['id']; ?>">
                                            <select name="role" class="role-select">
                                                <option value="user" <?php echo ($row['role']==='user')?'selected':''; ?>>User</option>
                                                <option value="admin" <?php echo ($row['role']==='admin')?'selected':''; ?>>Admin</option>
                                                <option value="superadmin" <?php echo ($row['role']==='superadmin')?'selected':''; ?>>Super Admin</option>
                                            </select>
                                            <button type="submit" class="action-btn role" title="Update Role">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>

                                        <button class="action-btn view" 
                                            onclick="openAddressSidebar(this)"
                                            data-purok="<?php echo htmlspecialchars($row['purok']); ?>" 
                                            data-barangay="<?php echo htmlspecialchars($row['barangay']); ?>" 
                                            data-municipality="<?php echo htmlspecialchars($row['municipality']); ?>" 
                                            data-country="<?php echo htmlspecialchars($row['country']); ?>" 
                                            data-zipcode="<?php echo htmlspecialchars($row['zipcode']); ?>"
                                            title="View Address">
                                            <i class="fa-solid fa-address-card"></i>
                                        </button>

                                        <button class="action-btn delete" 
                                            onclick="openDeleteModal(this)"
                                            data-user-id="<?php echo (int)$row['id']; ?>"
                                            title="Delete User">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Address Sidebar -->
    <div class="sidebar" id="addressSidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: #0b2b4f;"></i> Address Details</h3>
            <div class="close-btn" onclick="closeAddressSidebar()">&times;</div>
        </div>
        <div style="padding: 8px 0;">
            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Purok</label>
                <p style="font-size: 16px; font-weight: 500; margin-top: 4px;" id="addrPurok">-</p>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Barangay</label>
                <p style="font-size: 16px; font-weight: 500; margin-top: 4px;" id="addrBarangay">-</p>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Municipality</label>
                <p style="font-size: 16px; font-weight: 500; margin-top: 4px;" id="addrMunicipality">-</p>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Country</label>
                <p style="font-size: 16px; font-weight: 500; margin-top: 4px;" id="addrCountry">-</p>
            </div>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Zip Code</label>
                <p style="font-size: 16px; font-weight: 500; margin-top: 4px;" id="addrZipcode">-</p>
            </div>
        </div>
    </div>

    <!-- Settings Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cog" style="margin-right: 8px;"></i> Settings</h3>
            <div class="close-btn" onclick="toggleSidebar()">&times;</div>
        </div>
        <ul style="list-style: none;">
            <li style="margin-bottom: 12px;">
                <a href="../security/input_security_question.php" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; text-decoration: none; color: #1e293b; transition: all 0.2s; background: #f8fafd;">
                    <i class="fas fa-shield-alt" style="width: 24px; color: #0b2b4f;"></i>
                    <span style="font-weight: 500;">Security Questions</span>
                </a>
                <a href="../security/manage_security.php" style="display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 10px; text-decoration: none; color: #1e293b; transition: all 0.2s; background: #f8fafd;">
                    <i class="fas fa-lock" style="width:20px;color:#0b2b4f"></i>
                    Security Answer Stored
                </a>
            </li>
        </ul>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 style="font-size: 20px; margin-bottom: 8px; text-align: center;">Confirm Delete</h3>
            <p style="color: #64748b; text-align: center; margin-bottom: 24px;">Are you sure you want to delete this user? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="btn-modal cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-modal confirm" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon warning">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 style="font-size: 20px; margin-bottom: 8px; text-align: center;">Confirm Logout</h3>
            <p style="color: #64748b; text-align: center; margin-bottom: 24px;">Are you sure you want to logout?</p>
            <div class="modal-actions">
                <button class="btn-modal cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-modal confirm" onclick="confirmLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>

    <!-- Role Update Success Modal -->
    <div id="roleSetModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon success">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3 style="font-size: 20px; margin-bottom: 8px; text-align: center;">Role Updated</h3>
            <p id="roleSetMessage" style="color: #64748b; text-align: center; margin-bottom: 24px;"></p>
            <div class="modal-actions">
                <button class="btn-modal confirm" onclick="closeRoleModal()">OK</button>
            </div>
        </div>
    </div>

    <form id="deleteForm" method="post" style="display:none">
        <input type="hidden" name="delete_user" id="deleteUserId" value="">
    </form>

    <script>
        // Sidebar Toggle
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        // Logout Functions
        function logout() {
            document.getElementById('logoutModal').style.display = "flex";
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = "none";
        }

        function confirmLogout() {
            window.location.href = '../logform/logout.php';
        }

        // Address Sidebar Functions
        function openAddressSidebar(btn) {
            document.getElementById('addrPurok').textContent = btn.getAttribute('data-purok') || '-';
            document.getElementById('addrBarangay').textContent = btn.getAttribute('data-barangay') || '-';
            document.getElementById('addrMunicipality').textContent = btn.getAttribute('data-municipality') || '-';
            document.getElementById('addrCountry').textContent = btn.getAttribute('data-country') || '-';
            document.getElementById('addrZipcode').textContent = btn.getAttribute('data-zipcode') || '-';
            document.getElementById('addressSidebar').classList.add('open');
        }

        function closeAddressSidebar() {
            document.getElementById('addressSidebar').classList.remove('open');
        }

        // Delete Modal Functions
        function openDeleteModal(btn) {
            var id = btn.getAttribute('data-user-id');
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('confirmDeleteBtn').onclick = function() { 
                document.getElementById('deleteForm').submit(); 
            };
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Role Modal Functions
        function closeRoleModal() {
            document.getElementById('roleSetModal').style.display = 'none';
            window.location.href = window.location.pathname;
        }

        // Filter Function
        function filterTable() {
            var input = document.getElementById('filter_manage');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('table_manage');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var txt = rows[i].textContent.toLowerCase();
                var roleFilter = rows[i].getAttribute('data-role') || '';
                var currentRoleFilter = document.querySelector('.badge.active')?.textContent.toLowerCase() || 'all';
                
                var matchesSearch = txt.indexOf(filter) > -1;
                var matchesRole = currentRoleFilter === 'all' || roleFilter === currentRoleFilter;
                
                rows[i].style.display = matchesSearch && matchesRole ? '' : 'none';
            }
        }

        // Role Filter Function
        function filterRole(role) {
            // Update active badge
            document.querySelectorAll('.badge').forEach(badge => {
                badge.classList.remove('active');
                if (badge.textContent.toLowerCase() === role || (role === 'all' && badge.textContent.toLowerCase() === 'all')) {
                    badge.classList.add('active');
                }
            });
            
            filterTable();
        }

        // Event Listeners
        document.getElementById('filter_manage')?.addEventListener('input', filterTable);

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>

    <?php if (!empty($showRoleModal)): ?>
    <script>
        document.getElementById('roleSetMessage').textContent = 'Role for <?php echo htmlspecialchars($modalFullName ?: 'User'); ?> has been updated to <?php echo htmlspecialchars(ucfirst($modalNewRole)); ?>';
        document.getElementById('roleSetModal').style.display = 'flex';
    </script>
    <?php endif; ?>
</body>
</html>