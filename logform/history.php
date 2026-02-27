
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
$totalRequests = $pending = $approved = $declined = $cancelled = 0;

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
        SUM(status = 'declined') as declined,
        SUM(status = 'cancelled') as cancelled
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
            $cancelled = $c['cancelled'] ?? 0;
        }
        $countStmt->close();
    }
} catch (mysqli_sql_exception $e) {
    $countsError = 'leave_requests table not found or missing columns.';
    $schemaSql = "CREATE TABLE IF NOT EXISTS leave_requests (\n  id INT AUTO_INCREMENT PRIMARY KEY,\n  username VARCHAR(100) NOT NULL,\n  leave_type VARCHAR(50) NOT NULL,\n  start_date DATE NOT NULL,\n  end_date DATE DEFAULT NULL,\n  reason TEXT,\n  status ENUM('pending','approved','declined','cancelled') DEFAULT 'pending',\n  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
}

// Fetch user's approved/declined requests
$historyRequests = [];
try {
    $hr = $conn->prepare("SELECT id, leave_type, start_date, end_date, reason, status, created_at FROM leave_requests WHERE username = ? AND status IN ('approved','declined','cancelled') ORDER BY created_at DESC");
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
    <title>Leave History | User</title>
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

        .page-header h1 i {
            color: #0b2b4f;
            background: #e8f0fe;
            padding: 12px;
            border-radius: 12px;
            font-size: 24px;
        }

        .header-stats {
            display: flex;
            gap: 12px;
        }

        .stat-badge {
            background: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .stat-badge i {
            color: #0b2b4f;
        }

        /* Summary Cards */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
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

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }

        .summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .summary-icon.approved {
            background: #dcfce7;
            color: #166534;
        }

        .summary-icon.declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .summary-icon.pending {
            background: #fff3cd;
            color: #856404;
        }

        .summary-icon.total {
            background: #e8f0fe;
            color: #0b2b4f;
        }

        .summary-info h3 {
            font-size: 28px;
            font-weight: 700;
            color: #0b2b4f;
            margin-bottom: 4px;
        }

        .summary-info p {
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
            width: 300px;
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
            padding: 12px 12px;
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

        /* Leave Type Badge */
        .leave-type {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
        }

        .leave-type i {
            font-size: 12px;
            color: #0b2b4f;
        }

        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
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

        /* Date Cell */
        .date-cell {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-main {
            font-weight: 500;
            color: #1e293b;
        }

        .date-sub {
            font-size: 11px;
            color: #64748b;
        }

        /* Reason Cell */
        .reason-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #475569;
        }

        /* Action Button */
        .view-details-btn {
            background: none;
            border: none;
            color: #0b2b4f;
            cursor: pointer;
            font-size: 16px;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .view-details-btn:hover {
            background: #e8f0fe;
            transform: scale(1.1);
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
            margin-bottom: 20px;
        }

        .empty-state .btn-primary {
            background: #0b2b4f;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .empty-state .btn-primary:hover {
            background: #1a3a5f;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(11, 43, 79, 0.2);
        }

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
    if (!isset($initials)) $initials = 'U';
    if (!isset($firstname)) $firstname = 'John';
    if (!isset($lastname)) $lastname = 'Doe';
    
    // Sample history data for demonstration
    $historyRequests = $historyRequests ?? [
        [
            'leave_type' => 'Vacation Leave',
            'start_date' => '2024-03-15',
            'end_date' => '2024-03-20',
            'reason' => 'Family vacation',
            'created_at' => '2024-03-10 09:30 AM',
            'status' => 'approved'
        ],
        [
            'leave_type' => 'Sick Leave',
            'start_date' => '2024-03-05',
            'end_date' => '2024-03-07',
            'reason' => 'Flu symptoms',
            'created_at' => '2024-03-04 02:15 PM',
            'status' => 'approved'
        ],
        [
            'leave_type' => 'Emergency Leave',
            'start_date' => '2024-02-28',
            'end_date' => '2024-02-29',
            'reason' => 'Family emergency',
            'created_at' => '2024-02-27 11:45 AM',
            'status' => 'declined'
        ],
        [
            'leave_type' => 'Personal Leave',
            'start_date' => '2024-02-20',
            'end_date' => '2024-02-22',
            'reason' => 'Personal matters',
            'created_at' => '2024-02-18 10:00 AM',
            'status' => 'approved'
        ],
        [
            'leave_type' => 'Vacation Leave',
            'start_date' => '2024-02-10',
            'end_date' => '2024-02-15',
            'reason' => 'Out of town trip',
            'created_at' => '2024-02-05 01:30 PM',
            'status' => 'cancelled'
        ]
    ];

    // Calculate stats
    $totalRequests = count($historyRequests);
    $approvedCount = count(array_filter($historyRequests, fn($r) => $r['status'] === 'approved'));
    $declinedCount = count(array_filter($historyRequests, fn($r) => $r['status'] === 'declined'));
    $pendingCount = count(array_filter($historyRequests, fn($r) => $r['status'] === 'pending'));
    ?>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-large">U</div>
            <span class="role-label">User</span>
        </div>
        <ul>
            <li><a href="../logform/indexes.php"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></li>
            <li><a href="../logform/requestleave.php"><i class="fas fa-calendar-plus"></i><span>Request Leave</span></a></li>
            <li><a href="../logform/history.php" class="active"><i class="fas fa-history"></i><span>Leave History</span></a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">Leave History</div>

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
       
          

        <!-- History Table -->
        <div class="table-container">
            <div class="table-header">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="search" id="filter_history" placeholder="Search by type, reason, status...">
                </div>
                <div class="filter-tabs">
                    <span class="filter-tab active" onclick="filterByStatus('all')">All</span>
                    <span class="filter-tab" onclick="filterByStatus('approved')">Approved</span>
                    <span class="filter-tab" onclick="filterByStatus('declined')">Declined</span>
                   
                    <span class="filter-tab" onclick="filterByStatus('cancelled')">Cancelled</span>
                </div>
            </div>

            <table class="modern-table" id="history_table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($historyRequests)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No leave history found</p>
                                    
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($historyRequests as $r): 
                            $status = strtolower($r['status']);
                        ?>
                            <tr data-status="<?php echo $status; ?>">
                                <td>
                                    <span class="leave-type">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($r['leave_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <span class="date-main"><?php echo htmlspecialchars($r['start_date']); ?> - <?php echo htmlspecialchars($r['end_date']); ?></span>
                                        <?php
                                        // Calculate duration in days
                                        $start = new DateTime($r['start_date']);
                                        $end = new DateTime($r['end_date']);
                                        $days = $start->diff($end)->days + 1;
                                        ?>
                                        <span class="date-sub"><?php echo $days; ?> day(s)</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="reason-cell" title="<?php echo htmlspecialchars($r['reason']); ?>">
                                        <?php echo htmlspecialchars($r['reason']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-cell">
                                        <span class="date-main"><?php echo htmlspecialchars($r['created_at']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $status; ?>">
                                        <span class="status-dot <?php echo $status; ?>"></span>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="view-details-btn" onclick="viewDetails(<?php echo htmlspecialchars(json_encode($r)); ?>)" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
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

    <!-- Details Modal -->
    <div id="detailsModal" class="modal-overlay">
        <div class="modal-box" style="width: 500px; max-width: 90%;">
            <div class="modal-icon approved" id="detailIcon">
                <i class="fas fa-info-circle"></i>
            </div>
            <h3 id="detailTitle">Leave Request Details</h3>
            <div style="text-align: left; margin: 20px 0; padding: 16px; background: #f8fafd; border-radius: 12px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Leave Type</p>
                        <p style="font-weight: 600;" id="detailType"></p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Status</p>
                        <p id="detailStatus"></p>
                    </div>
                </div>
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Duration</p>
                    <p style="font-weight: 500;" id="detailDuration"></p>
                </div>
                <div style="margin-bottom: 16px;">
                    <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Reason</p>
                    <p style="background: white; padding: 12px; border-radius: 8px;" id="detailReason"></p>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Submitted</p>
                        <p style="font-weight: 500;" id="detailSubmitted"></p>
                    </div>
                    <div>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Responded</p>
                        <p style="font-weight: 500;" id="detailResponded">-</p>
                    </div>
                </div>
            </div>
            <div class="modal-actions">
                <button class="btn-modal confirm" onclick="closeDetailsModal()">Close</button>
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
            window.location.href = 'logout.php';
        }

        // Filter table function
        function filterTable() {
            var input = document.getElementById('filter_history');
            if (!input) return;
            
            var filter = input.value.toLowerCase();
            var table = document.getElementById('history_table');
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
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.textContent.toLowerCase() === status || (status === 'all' && tab.textContent.toLowerCase() === 'all')) {
                    tab.classList.add('active');
                }
            });
            filterTable();
        }

        // View details
        function viewDetails(request) {
            document.getElementById('detailType').textContent = request.leave_type;
            document.getElementById('detailDuration').textContent = request.start_date + ' to ' + request.end_date;
            document.getElementById('detailReason').textContent = request.reason;
            document.getElementById('detailSubmitted').textContent = request.created_at;
            
            let statusSpan = document.getElementById('detailStatus');
            statusSpan.innerHTML = '<span class="status-badge ' + request.status + '">' + 
                                   '<span class="status-dot ' + request.status + '"></span>' + 
                                   ucfirst(request.status) + '</span>';
            
            let icon = document.getElementById('detailIcon');
            icon.className = 'modal-icon ' + request.status;
            
            if (request.status === 'approved') {
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
            } else if (request.status === 'declined') {
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
            } else if (request.status === 'pending') {
                icon.innerHTML = '<i class="fas fa-hourglass-half"></i>';
            } else {
                icon.innerHTML = '<i class="fas fa-info-circle"></i>';
            }
            
            document.getElementById('detailsModal').style.display = 'flex';
        }

        function ucfirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
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