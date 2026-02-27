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
// Fetch approved and declined leaves for history with pagination (avoid error if responded_at column missing)
$perPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $perPage;

$colRes = $conn->query("SHOW COLUMNS FROM leave_requests LIKE 'responded_at'");

// Count total matching rows for pagination
$countRes = $conn->query("SELECT COUNT(*) as cnt FROM leave_requests WHERE status IN ('approved','declined')");
$totalCount = 0;
if ($countRes) {
    $crow = $countRes->fetch_assoc();
    $totalCount = (int)($crow['cnt'] ?? 0);
}
$totalPages = max(1, (int)ceil($totalCount / $perPage));
if ($page > $totalPages) $page = $totalPages;

if ($colRes && $colRes->num_rows > 0) {
    $hist = $conn->query("SELECT lr.id, lr.username, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, lr.responded_at,
                               u.firstname, u.middlename, u.lastname, u.suffix
                        FROM leave_requests lr
                        LEFT JOIN users u ON lr.username = u.username
                        WHERE lr.status IN ('approved','declined')
                        ORDER BY lr.created_at DESC
                        LIMIT $perPage OFFSET $offset");
} else {
    // responded_at not present yet; select NULL placeholder
    $hist = $conn->query("SELECT lr.id, lr.username, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, NULL as responded_at,
                               u.firstname, u.middlename, u.lastname, u.suffix
                        FROM leave_requests lr
                        LEFT JOIN users u ON lr.username = u.username
                        WHERE lr.status IN ('approved','declined')
                        ORDER BY lr.created_at DESC
                        LIMIT $perPage OFFSET $offset");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave History | Admin</title>
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
            background: #e8f0fe;
            color: #0b2b4f;
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

        .stat-icon.approved {
            background: #dcfce7;
            color: #166534;
        }

        .stat-icon.declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .stat-icon.pending {
            background: #fff3cd;
            color: #856404;
        }

        .stat-icon.total {
            background: #e8f0fe;
            color: #0b2b4f;
        }

        .stat-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #0b2b4f;
            margin-bottom: 4px;
        }

        .stat-info p {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.3px;
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
            font-size: 13px;
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
            font-size: 12px;
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
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #e8f0fe;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
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
            font-size: 11px;
            color: #64748b;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.cancelled {
            background: #e2e8f0;
            color: #475569;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.approved {
            background: #22c55e;
            box-shadow: 0 0 0 2px #22c55e30;
        }

        .status-dot.declined {
            background: #ef4444;
            box-shadow: 0 0 0 2px #ef444430;
        }

        .status-dot.pending {
            background: #eab308;
            box-shadow: 0 0 0 2px #eab30830;
        }

        .status-dot.cancelled {
            background: #64748b;
            box-shadow: 0 0 0 2px #64748b30;
        }

        /* Leave Type Badge */
        .leave-type {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        /* Reason Cell */
        .reason-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #475569;
        }

        /* Date Cell */
        .date-cell {
            font-size: 13px;
            color: #64748b;
        }

        .date-cell strong {
            color: #1e293b;
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

        /* Pagination */
        .pager-container { display:flex; justify-content:flex-end; margin-top:12px; }
        .pager { display:flex; gap:8px; align-items:center; }
        .pager-btn { padding:6px 10px; background:#0b2b4f; color:white; border-radius:6px; text-decoration:none; font-weight:600; }
        .pager-btn.disabled { background:#e0e0e0; color:#888; pointer-events:none; }
        .pager-current { padding:6px 10px; background:#ffffff; border-radius:6px; border:1px solid #ddd; min-width:42px; text-align:center; font-weight:600; }

        /* Responsive */
        @media (max-width: 1024px) {
            .left-sidebar {
                width: 80px;
            }
            .left-sidebar .role-area span,
            .left-sidebar ul li a span {
                display: none;
            }
            .left-sidebar ul li a i {
                margin-right: 0;
                font-size: 20px;
            }
            main {
                margin-left: 80px;
            }
            .top-navbar {
                left: 80px;
            }
        }
    </style>
</head>

<body>
    <?php
    // Sample data if not defined
    if (!isset($adm_initials)) $adm_initials = 'A';
    if (!isset($adm_first)) $adm_first = '';
    if (!isset($adm_last)) $adm_last = '';
    ?>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-large">A</div>
            <span class="role-label">Admin</span>
        </div>
        <ul>
            <li><a href="../admin/dashboard.php"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></li>
            <li><a href="../admin/userlist.php"><i class="fas fa-users"></i><span>User List</span></a></li>
            <li><a href="../admin/leave.php"><i class="fas fa-clock"></i><span>Pending Requests</span></a></li>
            <li><a href="../admin/history.php" class="active"><i class="fas fa-history"></i><span>Leave History</span></a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Leave History</div>

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
       
        <!-- Stats Cards -->


        <!-- History Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="filter_history_leaves" placeholder="Search by name, type, reason...">
                </div>
                <div class="filter-tabs">
                    <span class="filter-tab active" onclick="filterByStatus('all')">All</span>
                    <span class="filter-tab" onclick="filterByStatus('approved')">Approved</span>
                    <span class="filter-tab" onclick="filterByStatus('declined')">Declined</span>
                    <span class="filter-tab" onclick="filterByStatus('cancelled')">Cancelled</span>
                </div>
            </div>

            <table class="modern-table" id="table_history_leaves">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Requested</th>
                        <th>Responded</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$hist || $hist->num_rows === 0): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No history records found</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: while ($r = $hist->fetch_assoc()): 
                        $parts = array_filter([trim($r['firstname'] ?? ''), trim($r['middlename'] ?? ''), trim($r['lastname'] ?? ''), trim($r['suffix'] ?? '')]);
                        $fullName = $parts ? preg_replace('/\s+/', ' ', implode(' ', $parts)) : ($r['username'] ?? '');
                        $status = strtolower($r['status'] ?? 'pending');
                        $initial = strtoupper(substr($r['firstname'] ?? '', 0, 1) ?: substr($r['username'] ?? '', 0, 1) ?: 'U');
                    ?>
                        <tr data-status="<?php echo $status; ?>">
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar"><?php echo $initial; ?></div>
                                    <div class="user-details">
                                        <span class="user-name"><?php echo htmlspecialchars($fullName); ?></span>
                                        <span class="user-username">@<?php echo htmlspecialchars($r['username']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="leave-type">
                                    <i class="fas fa-tag" style="margin-right: 4px; font-size: 10px;"></i>
                                    <?php echo htmlspecialchars($r['leave_type']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <span class="date-cell"><strong>Start:</strong> <?php echo htmlspecialchars($r['start_date']); ?></span>
                                    <span class="date-cell"><strong>End:</strong> <?php echo htmlspecialchars($r['end_date']); ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="reason-cell" title="<?php echo htmlspecialchars($r['reason']); ?>">
                                    <?php echo htmlspecialchars($r['reason']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $status; ?>">
                                    <span class="status-dot <?php echo $status; ?>"></span>
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            <td class="date-cell"><?php echo htmlspecialchars($r['created_at']); ?></td>
                            <td class="date-cell"><?php echo htmlspecialchars($r['responded_at'] ?? '-'); ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
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
            var input = document.getElementById('filter_history_leaves');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('table_history_leaves');
            if (!table) return;
            
            var tbody = table.getElementsByTagName('tbody')[0];
            if (!tbody) return;
            
            var rows = tbody.getElementsByTagName('tr');
            
            for (var i = 0; i < rows.length; i++) {
                var txt = rows[i].textContent.toLowerCase();
                var statusFilter = document.querySelector('.filter-tab.active')?.textContent.toLowerCase() || 'all';
                var rowStatus = rows[i].getAttribute('data-status') || '';
                
                var matchesSearch = txt.indexOf(filter) > -1;
                var matchesStatus = statusFilter === 'all' || rowStatus === statusFilter;
                
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

        // Event listeners
        document.getElementById('filter_history_leaves')?.addEventListener('input', filterTable);

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