
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Leave Management</title>
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

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #0b2b4f 0%, #1a3a5f 100%);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(11, 43, 79, 0.2);
        }

        .welcome-text h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .welcome-text p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .quick-action-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .quick-action-btn:hover {
            background: white;
            color: #0b2b4f;
            transform: translateY(-2px);
        }

        .welcome-stats {
            display: flex;
            gap: 24px;
        }

        .welcome-stat {
            text-align: center;
        }

        .welcome-stat .value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .welcome-stat .label {
            font-size: 13px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            display: flex;
            align-items: center;
            gap: 16px;
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
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .card-icon.total {
            background: #e8f0fe;
            color: #0b2b4f;
        }

        .card-icon.pending {
            background: #fff3cd;
            color: #856404;
        }

        .card-icon.approved {
            background: #dcfce7;
            color: #166534;
        }

        .card-icon.declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .card-content {
            flex: 1;
        }

        .card-content h2 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            color: #0b2b4f;
            line-height: 1.2;
        }

        .card-content p {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }

        /* Recent Activity Section */
        .recent-activity {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: #0b2b4f;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-all-link {
            color: #0b2b4f;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.2s;
        }

        .view-all-link:hover {
            color: #1a3a5f;
            gap: 8px;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f8fafd;
            border-radius: 12px;
            transition: all 0.2s;
        }

        .activity-item:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .activity-icon.pending {
            background: #fff3cd;
            color: #856404;
        }

        .activity-icon.approved {
            background: #dcfce7;
            color: #166534;
        }

        .activity-icon.declined {
            background: #fee2e2;
            color: #991b1b;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .activity-meta {
            display: flex;
            gap: 16px;
            font-size: 12px;
            color: #64748b;
        }

        .activity-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .status-badge-small {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-badge-small.approved {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge-small.pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge-small.declined {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Quick Tips */
        .tips-card {
            background: linear-gradient(135deg, #f8fafd 0%, #f1f5f9 100%);
            border-radius: 16px;
            padding: 20px;
            margin-top: 24px;
            border: 1px dashed #cbd5e1;
        }

        .tips-card h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #0b2b4f;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .tips-list {
            list-style: none;
        }

        .tips-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            color: #475569;
            font-size: 14px;
        }

        .tips-list li i {
            color: #0b2b4f;
            font-size: 12px;
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
            .welcome-section {
                flex-direction: column;
                text-align: center;
                gap: 20px;
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
    if (!isset($totalRequests)) $totalRequests = 12;
    if (!isset($pending)) $pending = 3;
    if (!isset($approved)) $approved = 7;
    if (!isset($declined)) $declined = 2;
    ?>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-large">U</div>
            <span class="role-label">User</span>
        </div>
        <ul>
            <li><a href="../logform/indexes.php" class="active"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></li>
            <li><a href="../logform/requestleave.php"><i class="fas fa-calendar-plus"></i><span>Request Leave</span></a></li>
            <li><a href="../logform/history.php"><i class="fas fa-history"></i><span>Leave History</span></a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()"><i class="fas fa-cog"></i></span>
        <div class="navbar-title">My Dashboard</div>

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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo htmlspecialchars($firstname ?: 'User'); ?>! 👋</h1>
                <p>Manage your leave requests and track their status here.</p>
                <a href="../logform/requestleave.php" class="quick-action-btn">
                    <i class="fas fa-plus-circle"></i>
                    New Leave Request
                </a>
            </div>
            <div class="welcome-stats">
                <div class="welcome-stat">
                    <div class="value"><?php echo (int)$pending; ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="welcome-stat">
                    <div class="value"><?php echo (int)$approved; ?></div>
                    <div class="label">Approved</div>
                </div>
                <div class="welcome-stat">
                    <div class="value"><?php echo (int)$declined; ?></div>
                    <div class="label">Declined</div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="cards">
            <div class="card">
                <div class="card-icon total">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="card-content">
                    <h2><?php echo (int)$totalRequests; ?></h2>
                    <p>Total Requests</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon pending">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="card-content">
                    <h2><?php echo (int)$pending; ?></h2>
                    <p>Pending Leaves</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon approved">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <h2><?php echo (int)$approved; ?></h2>
                    <p>Approved Leaves</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon declined">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="card-content">
                    <h2><?php echo (int)$declined; ?></h2>
                    <p>Declined Requests</p>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity">
            <div class="section-header">
                <h3>
                    <i class="fas fa-clock" style="color: #0b2b4f;"></i>
                    Recent Activity
                </h3>
                <a href="../logform/history.php" class="view-all-link">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <div class="activity-list">
                <!-- Sample Activity Items - Replace with dynamic data -->
                <div class="activity-item">
                    <div class="activity-icon pending">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="activity-details">
                        <div class="activity-title">Leave Request Submitted</div>
                        <div class="activity-meta">
                            <span><i class="fas fa-calendar"></i> Mar 15, 2024</span>
                            <span><i class="fas fa-tag"></i> Vacation Leave</span>
                        </div>
                    </div>
                    <span class="status-badge-small pending">
                        <span class="status-dot"></span>
                        Pending
                    </span>
                </div>

                <div class="activity-item">
                    <div class="activity-icon approved">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="activity-details">
                        <div class="activity-title">Leave Request Approved</div>
                        <div class="activity-meta">
                            <span><i class="fas fa-calendar"></i> Mar 10, 2024</span>
                            <span><i class="fas fa-tag"></i> Sick Leave</span>
                        </div>
                    </div>
                    <span class="status-badge-small approved">
                        <span class="status-dot"></span>
                        Approved
                    </span>
                </div>

                <div class="activity-item">
                    <div class="activity-icon declined">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="activity-details">
                        <div class="activity-title">Leave Request Declined</div>
                        <div class="activity-meta">
                            <span><i class="fas fa-calendar"></i> Mar 5, 2024</span>
                            <span><i class="fas fa-tag"></i> Emergency Leave</span>
                        </div>
                    </div>
                    <span class="status-badge-small declined">
                        <span class="status-dot"></span>
                        Declined
                    </span>
                </div>
            </div>

            <!-- Quick Tips Card -->
           
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
            window.location.href = 'logout.php';
        }

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