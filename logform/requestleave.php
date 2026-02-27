
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

// Handle form submission for new leave request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $leave_type = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $reason = $_POST['reason'] ?? null;

    if ($leave_type && $start_date) {
        $ist = $conn->prepare("INSERT INTO leave_requests (username, leave_type, start_date, end_date, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        if ($ist) {
            $ist->bind_param('sssss', $username, $leave_type, $start_date, $end_date, $reason);
            $ist->execute();
            $ist->close();
            header('Location: requestleave.php?submitted=1');
            exit();
        }
    }
}

// Handle cancel action (set status to cancelled)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_cancel'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $cstmt = $conn->prepare("UPDATE leave_requests SET status = 'cancelled' WHERE id = ? AND username = ? AND status = 'pending'");
    if ($cstmt) {
        $cstmt->bind_param('is', $id, $username);
        $cstmt->execute();
        $cstmt->close();
        header('Location: requestleave.php');
        exit();
    }
}

// Fetch user's pending requests for listing (only pending should be visible)
$userRequests = [];
try {
    $ur = $conn->prepare("SELECT id, leave_type, start_date, end_date, reason, status, created_at FROM leave_requests WHERE username = ? AND status = 'pending' ORDER BY created_at DESC");
    if ($ur) {
        $ur->bind_param('s', $username);
        $ur->execute();
        $resu = $ur->get_result();
        while ($row = $resu->fetch_assoc()) {
            $userRequests[] = $row;
        }
        $ur->close();
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
    <title>Request Leave | User Dashboard</title>
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

        /* Leave Stats */
        .leave-stats {
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

        .stat-icon.rejected {
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

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
            margin-bottom: 30px;
        }

        .form-title {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }

        .form-title i {
            font-size: 24px;
            color: #0b2b4f;
            background: #e8f0fe;
            padding: 12px;
            border-radius: 12px;
        }

        .form-title h2 {
            font-size: 20px;
            font-weight: 600;
            color: #0b2b4f;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 8px;
        }

        .form-label i {
            margin-right: 6px;
            color: #0b2b4f;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #0b2b4f;
            box-shadow: 0 0 0 3px rgba(11, 43, 79, 0.1);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .btn-submit {
            background: #0b2b4f;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .btn-submit:hover {
            background: #1a3a5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 43, 79, 0.2);
        }

        .btn-submit i {
            font-size: 18px;
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
        }

        .table-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* Status Badge */
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

        .status-badge.rejected {
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

        .status-dot.rejected {
            background: #dc3545;
            box-shadow: 0 0 0 2px #dc354530;
        }

        /* Action Button */
        .btn-cancel {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-cancel:hover {
            background: #fecaca;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
        }

        .btn-cancel i {
            font-size: 14px;
        }

        .btn-disabled {
            background: #f1f5f9;
            color: #94a3b8;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: not-allowed;
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
    <?php
    // This would typically be in your PHP block at the top
    // For demonstration, setting sample values if not defined
    if (!isset($initials)) $initials = 'U';
    if (!isset($firstname)) $firstname = '';
    if (!isset($lastname)) $lastname = '';
    if (!isset($userRequests)) $userRequests = [];
    
    // Sample stats for demonstration
    $pendingCount = 2;
    $approvedCount = 3;
    $rejectedCount = 1;
    $totalRequests = 6;
    ?>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-large">U</div>
            <span class="role-label">User</span>
        </div>
        <ul>
            <li><a href="../logform/indexes.php"><i class="fas fa-chart-pie"></i>Dashboard</a></li>
            <li><a href="../logform/requestleave.php" class="active"><i class="fas fa-calendar-plus"></i>Request Leave</a></li>
            <li><a href="../logform/history.php"><i class="fas fa-history"></i>Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Request Leave</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($initials ?: 'U'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($firstname . ' ' . $lastname) ?: $_SESSION['username'] ?? 'User'); ?></div>
        </div>

        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main Content -->
    <main>
      

            
             

        <!-- Leave Request Form -->
        <div class="form-container">
            <div class="form-title">
                <i class="fas fa-pen-alt"></i>
                <h2>Submit New Leave Request</h2>
            </div>

            <form method="post">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-tag"></i>Leave Type
                        </label>
                        <select name="leave_type" class="form-control" required>
                            <option value="">Select leave type</option>
                            <option value="Vacation"> Vacation Leave</option>
                            <option value="Emergency"> Emergency Leave</option>
                            <option value="Sick Leave"> Sick Leave</option>
                            <option value="Maternity"> Maternity Leave</option>
                            <option value="Paternity"> Paternity Leave</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-day"></i>Start Date
                        </label>
                        <input type="date" name="start_date" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-week"></i>End Date
                        </label>
                        <input type="date" name="end_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">
                            <i class="fas fa-comment"></i>Reason for Leave
                        </label>
                        <textarea name="reason" class="form-control" placeholder="Please provide details about your leave request..." rows="4"></textarea>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" name="submit_leave" class="btn-submit">
                        <i class="fas fa-paper-plane"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>

        <!-- Your Requests -->
        <div class="table-container">
            <div class="table-header">
                <h3>
                    <i class="fas fa-list-ul" style="color: #0b2b4f;"></i>
                    Your Leave Requests
                </h3>
               
            </div>

            <table class="modern-table" id="requestsTable">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Reason</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($userRequests)): ?>
                        <tr>
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No leave requests found</p>
                                    <p style="font-size: 14px; margin-top: 8px;">Submit your first leave request using the form above</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($userRequests as $r): ?>
                            <tr data-status="<?php echo $r['status']; ?>">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <?php
                                        $icon = 'fas fa-calendar';
                                        if ($r['leave_type'] == 'Vacation') $icon = 'fas fa-umbrella-beach';
                                        else if ($r['leave_type'] == 'Emergency') $icon = 'fas fa-ambulance';
                                        else if ($r['leave_type'] == 'Sick Leave') $icon = 'fas fa-thermometer-half';
                                        ?>
                                        <i class="<?php echo $icon; ?>" style="color: #0b2b4f;"></i>
                                        <span><?php echo htmlspecialchars($r['leave_type']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($r['start_date'])); ?></td>
                                <td><?php echo $r['end_date'] ? date('M d, Y', strtotime($r['end_date'])) : '-'; ?></td>
                                <td>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                         title="<?php echo htmlspecialchars($r['reason']); ?>">
                                        <?php echo htmlspecialchars($r['reason'] ?: 'No reason provided'); ?>
                                    </div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($r['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $r['status']; ?>">
                                        <span class="status-dot <?php echo $r['status']; ?>"></span>
                                        <?php echo ucfirst($r['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <form method="post" style="display:inline" onsubmit="return confirmCancel(event, '<?php echo htmlspecialchars($r['leave_type']); ?>')">
                                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" name="action_cancel" class="btn-cancel">
                                                <i class="fas fa-times"></i>
                                                Cancel
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="btn-disabled">
                                            <i class="fas fa-ban"></i>
                                            No Action
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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

    <!-- Cancel Confirmation Modal -->
    <div id="cancelConfirmModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon warning">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3>Confirm Cancellation</h3>
            <p id="cancelModalMessage">Are you sure you want to cancel this leave request?</p>
            <div class="modal-actions">
                <button class="btn-modal cancel" onclick="closeCancelModal()">No, Keep It</button>
                <button class="btn-modal confirm" id="confirmCancelBtn">Yes, Cancel</button>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal-overlay" aria-hidden="true">
        <div class="modal-box">
            <div class="modal-icon" style="background: #d1fae5; color: #065f46;">
                <i class="fas fa-check"></i>
            </div>
            <h3>Success</h3>
            <p>Successfully submitted leave request.</p>
            <div class="modal-actions">
                <button class="btn-modal confirm" onclick="closeSuccessModal()">OK</button>
            </div>
        </div>
    </div>

    <form id="cancelForm" method="post" style="display:none">
        <input type="hidden" name="id" id="cancelRequestId" value="">
        <input type="hidden" name="action_cancel" value="1">
    </form>

    <script>
        let currentCancelForm = null;

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
            window.location.href = 'logout.php';
        }

        // Filter requests by status
        function filterRequests(status) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.toLowerCase().includes(status)) {
                    tab.classList.add('active');
                }
            });

            var table = document.getElementById('requestsTable');
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

        // Cancel confirmation
        function confirmCancel(event, leaveType) {
            event.preventDefault();
            const form = event.target;
            const requestId = form.querySelector('input[name="id"]').value;
            
            document.getElementById('cancelRequestId').value = requestId;
            document.getElementById('cancelModalMessage').textContent = `Are you sure you want to cancel your ${leaveType} request? This action cannot be undone.`;
            document.getElementById('cancelConfirmModal').style.display = 'flex';
            
            return false;
        }

        function closeCancelModal() {
            document.getElementById('cancelConfirmModal').style.display = 'none';
        }

        // Submit cancel form
        document.getElementById('confirmCancelBtn')?.addEventListener('click', function() {
            document.getElementById('cancelForm').submit();
        });

        // Validate dates
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const startDate = new Date(document.querySelector('input[name="start_date"]').value);
            const endDate = document.querySelector('input[name="end_date"]').value ? 
                           new Date(document.querySelector('input[name="end_date"]').value) : null;
            
            if (endDate && endDate < startDate) {
                e.preventDefault();
                alert('End date cannot be before start date');
            }
        });

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

        // Set minimum date for date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.querySelectorAll('input[type="date"]').forEach(input => {
                input.setAttribute('min', today);
            });
        });
    </script>
    <script>
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            if (!modal) return;
            modal.style.display = 'flex';
            // auto close after 3 seconds
            setTimeout(() => {
                modal.style.display = 'none';
            }, 3000);
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            if (!modal) return;
            modal.style.display = 'none';
        }

        (function() {
            try {
                const params = new URLSearchParams(window.location.search);
                if (params.get('submitted') === '1') {
                    // show modal after DOM ready
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', showSuccessModal);
                    } else {
                        showSuccessModal();
                    }
                    // remove query param to avoid showing again on refresh
                    params.delete('submitted');
                    const newUrl = window.location.pathname + (params.toString() ? ('?' + params.toString()) : '');
                    window.history.replaceState({}, document.title, newUrl);
                }
            } catch (e) {
                // ignore
            }
        })();
    </script>
</body>
</html>