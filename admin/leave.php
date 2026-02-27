<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only admin can access
if (!isset($_SESSION['username'])) {
    header('Location: ../logform/login.php');
    exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'admin') {
    if ($role === 'superadmin') header('Location: ../superadmin/dashboard.php');
    elseif ($role === 'upper_management') header('Location: ../upper_management/dashboard.php');
    else header('Location: ../logform/indexes.php');
    exit();
}
// Fetch admin user info for display
$adm_first = '';
$adm_last = '';
$adm_initials = '';
if (isset($_SESSION['username'])) {
    $uname = $_SESSION['username'];
    $pst = $conn->prepare('SELECT firstname, lastname FROM users WHERE username = ? LIMIT 1');
    if ($pst) {
        $pst->bind_param('s', $uname);
        $pst->execute();
        $gres = $pst->get_result();
        if ($gres && $gres->num_rows) {
            $rw = $gres->fetch_assoc();
            $adm_first = $rw['firstname'] ?? '';
            $adm_last = $rw['lastname'] ?? '';
            $adm_initials = strtoupper(substr(($adm_first ?: 'A'),0,1) . substr(($adm_last ?: ' '),0,1));
        }
        $pst->close();
    }
}

// Fetch counts: total users (only role = 'user') and leave status totals
$totalUsers = 0;
$pendingLeaves = 0;
$approvedLeaves = 0;
$declinedLeaves = 0;
try {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role='user'");
    if ($r) {
        $row = $r->fetch_assoc();
        $totalUsers = (int)($row['cnt'] ?? 0);
    }
    $l = $conn->query("SELECT SUM(status = 'pending') as pending, SUM(status = 'approved') as approved, SUM(status = 'declined') as declined FROM leave_requests");
    if ($l) {
        $lr = $l->fetch_assoc();
        $pendingLeaves = (int)($lr['pending'] ?? 0);
        $approvedLeaves = (int)($lr['approved'] ?? 0);
        $declinedLeaves = (int)($lr['declined'] ?? 0);
    }
} catch (mysqli_sql_exception $e) {
    // keep defaults on error
}
// Handle approve/decline actions (support AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $id = (int)$_POST['id'];
    if (in_array($action, ['approve','decline'])) {
        $newStatus = $action === 'approve' ? 'approved' : 'declined';

        // ensure responded_at and responded_by columns exist
        $colRes = $conn->query("SHOW COLUMNS FROM leave_requests LIKE 'responded_at'");
        if ($colRes && $colRes->num_rows === 0) {
            @$conn->query("ALTER TABLE leave_requests ADD COLUMN responded_at DATETIME NULL");
        }
        $colRes2 = $conn->query("SHOW COLUMNS FROM leave_requests LIKE 'responded_by'");
        if ($colRes2 && $colRes2->num_rows === 0) {
            @$conn->query("ALTER TABLE leave_requests ADD COLUMN responded_by VARCHAR(100) DEFAULT NULL");
        }

        $adminUser = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin';
        $up = $conn->prepare("UPDATE leave_requests SET status = ?, responded_at = NOW(), responded_by = ? WHERE id = ?");
        if ($up) {
            $up->bind_param('ssi', $newStatus, $adminUser, $id);
            $up->execute();
            $up->close();
        }
    }

    // If AJAX request, return JSON and do not redirect
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => $newStatus, 'id' => $id]);
        exit();
    }

    header('Location: leave.php');
    exit();
}

// Fetch leave requests (include user's name fields)
$leaves = $conn->query("SELECT lr.id, lr.username, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at,
                               u.firstname, u.middlename, u.lastname, u.suffix
                        FROM leave_requests lr
                        LEFT JOIN users u ON lr.username = u.username
                        WHERE lr.status = 'pending'
                        ORDER BY lr.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests | Admin</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pending-count {
            background: #ffd70020;
            color: #856404;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #ffd70040;
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

        .filter-badge {
            display: flex;
            gap: 8px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 10px;
        }

        .filter-badge span {
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            color: #64748b;
        }

        .filter-badge span.active {
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
            vertical-align: middle;
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
            margin-bottom: 2px;
        }

        .user-username {
            font-size: 12px;
            color: #64748b;
        }

        /* Leave Type Badge */
        .leave-type {
            background: #e8f0fe;
            color: #0b2b4f;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        /* Date Info */
        .date-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #475569;
        }

        .date-item i {
            width: 16px;
            color: #0b2b4f;
            font-size: 12px;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.declined {
            background: #f8d7da;
            color: #721c24;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.pending {
            background: #ffc107;
            box-shadow: 0 0 0 2px #ffc10730;
        }

        .status-dot.approved {
            background: #28a745;
            box-shadow: 0 0 0 2px #28a74530;
        }

        .status-dot.declined {
            background: #dc3545;
            box-shadow: 0 0 0 2px #dc354530;
        }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 8px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-approve i {
            font-size: 14px;
        }

        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }

        .btn-decline {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-decline i {
            font-size: 14px;
        }

        .btn-decline:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
        }

        .btn-disabled {
            background: #e9ecef;
            color: #6c757d;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: not-allowed;
            opacity: 0.6;
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

        .modal-icon.approve {
            background: #d4edda;
            color: #155724;
        }

        .modal-icon.decline {
            background: #f8d7da;
            color: #721c24;
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
            <div class="role-circle-large">A</div>
            <span class="role-label">Admin</span>
        </div>
        <ul>
            <li><a href="../admin/dashboard.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
            <li><a href="../admin/userlist.php"><i class="fas fa-users"></i>User List</a></li>
            <li><a href="../admin/leave.php" class="active"><i class="fas fa-clock"></i>Pending Request</a></li>
            <li><a href="../admin/history.php"><i class="fas fa-history"></i>Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Pending Request</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($adm_initials ?: 'A'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($adm_first . ' ' . $adm_last) ?: ($_SESSION['username'] ?? 'Admin')); ?></div>
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
                    <input type="search" id="filter_pending_leaves" placeholder="Search by name, type, or reason...">
                </div>
                
            </div>

            <div style="overflow-x: auto;">
                <table class="modern-table" id="table_pending_leaves">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Leave Type</th>
                            <th>Duration</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$leaves || $leaves->num_rows === 0): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>No leave requests found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: while ($r = $leaves->fetch_assoc()): 
                        $parts = array_filter([trim($r['firstname'] ?? ''), trim($r['middlename'] ?? ''), trim($r['lastname'] ?? ''), trim($r['suffix'] ?? '')]);
                        $fullName = $parts ? preg_replace('/\s+/', ' ', implode(' ', $parts)) : ($r['username'] ?? '');
                        $status = $r['status'] ?? 'pending';
                    ?>
                        <tr data-status="<?php echo $status; ?>">
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($r['firstname'] ?? 'U', 0, 1)); ?>
                                    </div>
                                    <div class="user-details">
                                        <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
                                        <span class="user-username">@<?php echo htmlspecialchars($r['username']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="leave-type">
                                    <?php echo htmlspecialchars($r['leave_type']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="date-info">
                                    <div class="date-item">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Start: <?php echo htmlspecialchars($r['start_date']); ?></span>
                                    </div>
                                    <div class="date-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span>End: <?php echo htmlspecialchars($r['end_date']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span style="max-width: 200px; display: block;" title="<?php echo htmlspecialchars($r['reason']); ?>">
                                    <?php echo htmlspecialchars(substr($r['reason'], 0, 50)) . (strlen($r['reason']) > 50 ? '...' : ''); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $status; ?>">
                                    <span class="status-dot <?php echo $status; ?>"></span>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td>
                                <div class="date-item">
                                    <i class="fas fa-clock"></i>
                                    <span><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($status === 'pending'): ?>
                                    <div class="action-group">
                                        <button class="btn-approve action-btn" data-id="<?php echo (int)$r['id']; ?>" data-action="approve">
                                            <i class="fas fa-check"></i>
                                            Approve
                                        </button>
                                        <button class="btn-decline action-btn" data-id="<?php echo (int)$r['id']; ?>" data-action="decline">
                                            <i class="fas fa-times"></i>
                                            Decline
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="btn-disabled">
                                        <i class="fas fa-lock"></i>
                                        Processed
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table>
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
            <div class="modal-icon warning" style="background: #fee2e2; color: #991b1b;">
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

    <!-- Action Modal -->
    <div id="actionModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon" id="actionModalIcon">
                <i class="fas fa-question"></i>
            </div>
            <h3 id="actionTitle">Confirm Action</h3>
            <p id="actionMessage">Are you sure you want to proceed?</p>
            <div class="modal-actions">
                <button class="btn-modal cancel" onclick="closeActionModal()">Cancel</button>
                <button class="btn-modal confirm" id="actionConfirm">Confirm</button>
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

        // Action modal handling
        function openActionModal(title, message, id, action) {
            const modal = document.getElementById('actionModal');
            const icon = document.getElementById('actionModalIcon');
            
            document.getElementById('actionTitle').textContent = title;
            document.getElementById('actionMessage').textContent = message;
            
            // Set icon based on action
            if (action === 'approve') {
                icon.className = 'modal-icon approve';
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            } else if (action === 'decline') {
                icon.className = 'modal-icon decline';
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
            }
            
            modal.style.display = 'flex';
            modal.dataset.targetId = id;
            modal.dataset.action = action;
        }
        
        function closeActionModal() {
            document.getElementById('actionModal').style.display = 'none';
        }

        // Handle action button clicks
        document.addEventListener('click', function(e){
            const btn = e.target.closest('.action-btn');
            if (!btn) return;
            
            const id = btn.getAttribute('data-id');
            const action = btn.getAttribute('data-action');
            
            if (action === 'approve') {
                openActionModal('Approve Leave Request', 'Are you sure you want to approve this leave request? This action can be reversed.', id, action);
            } else if (action === 'decline') {
                openActionModal('Decline Leave Request', 'Are you sure you want to decline this leave request? This action can be reversed.', id, action);
            }
        });

        // Handle confirm action
        document.getElementById('actionConfirm').addEventListener('click', function(){
            const modal = document.getElementById('actionModal');
            const id = modal.dataset.targetId;
            const action = modal.dataset.action;
            
            if (!id || !action) { 
                closeActionModal(); 
                return; 
            }

            const form = new FormData();
            form.append('id', id);
            form.append('action', action);

            fetch('leave.php', { 
                method: 'POST', 
                body: form, 
                headers: { 'X-Requested-With': 'XMLHttpRequest' } 
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    // Update the row status
                    const btn = document.querySelector('.action-btn[data-id="'+id+'"]');
                    if (btn) {
                        const tr = btn.closest('tr');
                        if (tr) {
                            // Update status display
                            const statusCell = tr.querySelector('.status-badge');
                            if (statusCell) {
                                statusCell.className = `status-badge ${action}d`;
                                statusCell.innerHTML = `<span class="status-dot ${action}d"></span>${action.charAt(0).toUpperCase() + action.slice(1)}d`;
                            }
                            
                            // Replace action buttons with processed indicator
                            const actionCell = tr.querySelector('td:last-child');
                            if (actionCell) {
                                actionCell.innerHTML = `<span class="btn-disabled"><i class="fas fa-lock"></i> Processed</span>`;
                            }
                        }
                    }
                    
                    // Update stats counts (optional)
                    location.reload(); // Simple reload to update stats
                }
                closeActionModal();
            }).catch(err => {
                closeActionModal();
            });
        });

        // Filter table function
        function filterTable() {
            var input = document.getElementById('filter_pending_leaves');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('table_pending_leaves');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            var activeFilter = document.querySelector('.filter-badge .active')?.textContent.toLowerCase() || 'all';
            
            for (var i = 0; i < rows.length; i++) {
                // Skip empty state row
                if (rows[i].querySelector('.empty-state')) continue;
                
                var txt = rows[i].textContent.toLowerCase();
                var matchesSearch = txt.indexOf(filter) > -1;
                
                var rowStatus = rows[i].getAttribute('data-status') || '';
                var matchesStatus = activeFilter === 'all' || rowStatus === activeFilter;
                
                rows[i].style.display = matchesSearch && matchesStatus ? '' : 'none';
            }
        }

        // Filter by status
        function filterStatus(status) {
            document.querySelectorAll('.filter-badge span').forEach(badge => {
                badge.classList.remove('active');
                if (badge.textContent.toLowerCase() === status) {
                    badge.classList.add('active');
                }
            });
            filterTable();
        }

        // Event listener for search
        document.getElementById('filter_pending_leaves')?.addEventListener('input', filterTable);

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