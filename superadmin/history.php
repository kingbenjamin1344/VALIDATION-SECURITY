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

// Pagination (5 rows per page)
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

// total count for pagination
$countRes = $conn->query("SELECT COUNT(*) as cnt FROM leave_requests");
$totalCount = 0;
if ($countRes) {
    $crow = $countRes->fetch_assoc();
    $totalCount = (int)($crow['cnt'] ?? 0);
}
$totalPages = max(1, (int)ceil($totalCount / $perPage));
if ($page > $totalPages) $page = $totalPages;

// Build paginated query depending on available columns
if ($colRes && $colRes->num_rows > 0 && $colRes2 && $colRes2->num_rows > 0) {
    $sql = "SELECT lr.id, lr.username, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, lr.responded_at, lr.responded_by, r.firstname AS responder_firstname, r.middlename AS responder_middlename, r.lastname AS responder_lastname, r.role AS responder_role FROM leave_requests lr LEFT JOIN users r ON lr.responded_by = r.username ORDER BY lr.created_at DESC LIMIT $perPage OFFSET $offset";
} elseif ($colRes && $colRes->num_rows > 0) {
    $sql = "SELECT id, username, leave_type, start_date, end_date, reason, status, created_at, responded_at, NULL as responded_by, NULL as responder_firstname, NULL as responder_middlename, NULL as responder_lastname FROM leave_requests ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
} else {
    $sql = "SELECT id, username, leave_type, start_date, end_date, reason, status, created_at, NULL as responded_at, NULL as responded_by, NULL as responder_firstname, NULL as responder_middlename, NULL as responder_lastname FROM leave_requests ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
}
$hist = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History | Super Admin</title>
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        .stat-icon.pending {
            background: #fff3cd;
            color: #856404;
        }

        .stat-icon.approved {
            background: #d4edda;
            color: #155724;
        }

        .stat-icon.declined {
            background: #f8d7da;
            color: #721c24;
        }

        .stat-icon.total {
            background: #e8f0fe;
            color: #0b2b4f;
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
            flex-wrap: wrap;
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

        /* Status Badges - Enhanced */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.declined {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.cancelled {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.approved {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a74530;
        }

        .status-dot.declined {
            background: #dc3545;
            box-shadow: 0 0 0 2px #dc354530;
        }

        .status-dot.pending {
            background: #ffc107;
            box-shadow: 0 0 0 2px #ffc10730;
        }

        .status-dot.cancelled {
            background: #6c757d;
            box-shadow: 0 0 0 2px #6c757d30;
        }

        /* User Info in Table */
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar-small {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: #e8f0fe;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .responder-info {
            font-size: 13px;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 6px;
            display: inline-block;
        }

        /* Date formatting */
        .date-cell {
            white-space: nowrap;
        }

        .date-cell i {
            margin-right: 6px;
            color: #64748b;
            font-size: 12px;
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

        /* Export Button */
        .export-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px 18px;
            border-radius: 10px;
            color: #0b2b4f;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .export-btn:hover {
            background: #f8fafd;
            border-color: #0b2b4f;
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
            <li><a href="../superadmin/history.php" class="active"><i class="fas fa-history"></i>Leave History</a></li>
            <li><a href="../superadmin/activitylog.php"><i class="fas fa-clipboard-list"></i>Activity Logs</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Leave Management</div>

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
       

    
            
            
            

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="filter_history" placeholder="Search by username, reason, or status...">
                </div>
                <div class="filter-tabs">
                    <span class="filter-tab active" onclick="filterByStatus('all')">All</span>
                    <span class="filter-tab" onclick="filterByStatus('pending')">Pending</span>
                    <span class="filter-tab" onclick="filterByStatus('approved')">Approved</span>
                    <span class="filter-tab" onclick="filterByStatus('declined')">Declined</span>
                    <span class="filter-tab" onclick="filterByStatus('cancelled')">Cancelled</span>
                </div>
            </div>

            <table class="modern-table" id="table_history">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Responded</th>
                        <th>Responded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$hist || mysqli_num_rows($hist) === 0): ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No leave requests found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($r = mysqli_fetch_assoc($hist)): 
                            $status = strtolower($r['status'] ?? 'pending');
                        ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td>
                                    <div class="user-info-cell">
                                        <div class="user-avatar-small">
                                            <?php echo strtoupper(substr($r['username'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($r['username']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="background: #e8f0fe; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 500; color: #0b2b4f;">
                                        <?php echo htmlspecialchars($r['leave_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 4px;">
                                        <span><i class="fas fa-calendar-alt" style="color: #64748b; width: 16px;"></i> <?php echo htmlspecialchars($r['start_date']); ?></span>
                                        <span><i class="fas fa-calendar-check" style="color: #64748b; width: 16px;"></i> <?php echo htmlspecialchars($r['end_date']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($r['reason']); ?>">
                                        <?php echo htmlspecialchars($r['reason']); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status; ?>">
                                        <span class="status-dot <?php echo $status; ?>"></span>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <i class="fas fa-clock"></i>
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($r['created_at']))); ?>
                                </td>
                                <td class="date-cell">
                                    <?php if (!empty($r['responded_at'])): ?>
                                        <i class="fas fa-check-circle"></i>
                                        <?php echo htmlspecialchars(date('M d, Y', strtotime($r['responded_at']))); ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $responder_username = $r['responded_by'] ?? '';
                                        $responder_name = trim(($r['responder_firstname'] ?? '') . ' ' . ($r['responder_middlename'] ?? '') . ' ' . ($r['responder_lastname'] ?? ''));
                                        $responder_name = trim(preg_replace('/\s+/', ' ', $responder_name));
                                        
                                        if (!empty($responder_username) || !empty($responder_name)) {
                                            echo '<span class="responder-info">';
                                            if (!empty($responder_name)) {
                                                echo '<i class="fas fa-user"></i> ' . htmlspecialchars($responder_name);
                                            } else {
                                                echo '<i class="fas fa-user"></i> ' . htmlspecialchars($responder_username);
                                            }
                                            echo '</span>';
                                        } else {
                                            echo '<span style="color: #94a3b8;">—</span>';
                                        }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>

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
            var input = document.getElementById('filter_history');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('table_history');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            var activeStatus = document.querySelector('.filter-tab.active')?.textContent.toLowerCase() || 'all';
            
            for (var i = 0; i < rows.length; i++) {
                // Skip empty state row
                if (rows[i].querySelector('.empty-state')) continue;
                
                var txt = rows[i].textContent.toLowerCase();
                var matchesSearch = txt.indexOf(filter) > -1;
                
                var rowStatus = rows[i].getAttribute('data-status') || '';
                var matchesStatus = activeStatus === 'all' || rowStatus === activeStatus;
                
                rows[i].style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        // Filter by status
        function filterByStatus(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.toLowerCase() === status || (status === 'all' && tab.textContent.toLowerCase() === 'all')) {
                    tab.classList.add('active');
                }
            });
            
            filterTable();
        }

        // Export to CSV function
        function exportToCSV() {
            const rows = [];
            const table = document.getElementById('table_history');
            const headers = [];
            
            // Get headers
            table.querySelectorAll('thead th').forEach(th => {
                headers.push(th.textContent.trim());
            });
            rows.push(headers.join(','));
            
            // Get data rows
            table.querySelectorAll('tbody tr').forEach(tr => {
                // Skip empty state
                if (tr.querySelector('.empty-state')) return;
                
                const row = [];
                tr.querySelectorAll('td').forEach(td => {
                    // Clean up the text content
                    let text = td.textContent.trim().replace(/,/g, ';').replace(/\s+/g, ' ');
                    row.push(text);
                });
                rows.push(row.join(','));
            });
            
            // Create and download CSV
            const csvContent = rows.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'leave_history.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Event listeners
        document.getElementById('filter_history')?.addEventListener('input', filterTable);

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