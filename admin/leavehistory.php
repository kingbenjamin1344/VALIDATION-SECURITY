<?php
session_start();
$displayName = '';
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'superadmin') {
        $displayName = $_SESSION['superadmin'] ?? '';
    } elseif ($_SESSION['role'] === 'admin') {
        $displayName = $_SESSION['admin']['fullname'] ?? $_SESSION['admin']['username'] ?? '';
    } else {
        $displayName = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';
    }
}
$initials = '';
if ($displayName !== '') {
    $parts = preg_split('/\s+/', trim($displayName));
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
$role = $_SESSION['role'] ?? 'user';
$current = basename($_SERVER['PHP_SELF']);
?>
<?php
require_once __DIR__ . '/../regform/config.php';

$filter_user = trim((string)($_GET['user'] ?? ''));
if ($filter_user !== '') {
    $safe_user = mysqli_real_escape_string($conn, $filter_user);
    $res = mysqli_query($conn, "SELECT r.id, r.user_id, r.username, r.email, r.leave_type, r.start_date, r.end_date, r.reason, r.status, r.created_at, u.firstname, u.middlename, u.lastname, lr.admin_id, a.fullname AS admin_name, lr.action AS action_taken, lr.note, lr.acted_at FROM requestleave r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN leaverequest lr ON lr.request_id = r.id LEFT JOIN admins a ON lr.admin_id = a.id WHERE r.status IN ('approved','declined') AND r.username='".$safe_user."' ORDER BY lr.acted_at DESC");
} else {
    $res = mysqli_query($conn, "SELECT r.id, r.user_id, r.username, r.email, r.leave_type, r.start_date, r.end_date, r.reason, r.status, r.created_at, u.firstname, u.middlename, u.lastname, lr.admin_id, a.fullname AS admin_name, lr.action AS action_taken, lr.note, lr.acted_at FROM requestleave r LEFT JOIN users u ON r.user_id = u.id LEFT JOIN leaverequest lr ON lr.request_id = r.id LEFT JOIN admins a ON lr.admin_id = a.id WHERE r.status IN ('approved','declined') ORDER BY lr.acted_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }

        /* Left Sidebar */
        .left-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 220px;
            height: 100%;
            background-color: #1E90FF;
            color: white;
            padding-top: 60px;
            box-sizing: border-box;
        }

        .left-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .role-area {
            text-align: center;
            padding: 18px 12px;
        }

        .role-circle-small {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ffffff;
            color: #1E90FF;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.12);
            margin-bottom: 6px;
        }

        .role-label-small {
            color: #ffffff;
            font-size: 13px;
            text-transform: capitalize;
            display: block;
        }
        .left-sidebar ul li {
            padding: 0;
        }

        .left-sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
            padding: 15px 20px;
            box-sizing: border-box;
            width: 100%;
        }

        .left-sidebar ul li a:hover {
            background-color: rgba(255,255,255,0.2);
            border-radius: 5px;
        }

        .left-sidebar ul li a.active {
            background-color: #063970; /* dark blue highlight */
            border-radius: 5px;
        }

        /* Top Navbar */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 220px;
            right: 0;
            height: 60px;
            background-color: #1E90FF;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding: 0 20px;
            box-sizing: border-box;
            z-index: 999;
            gap: 18px;
        }

        .navbar-title {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .settings-icon,
        .logout-icon {
            font-size: 22px;
            cursor: pointer;
            color: white;
            transition: 0.2s ease;
        }

        .logout-icon:hover {
            color: #ff4d4d;
            transform: scale(1.1);
        }

        /* Profile circle */
        .profile {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            font-weight: 600;
        }

        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffffff;
            color: #1E90FF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .settings-icon:hover {
            transform: rotate(90deg);
        }

        /* Main */
        main {
            margin-left: 220px;
            padding: 90px 20px 80px 20px;
            width: 100%;
            background: #f5f7fb;
            min-height: 100vh;
        }

        /* Dashboard Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 12px rgba(0,0,0,0.1);
            text-align: center;
            transition: 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .card h2 {
            margin: 0;
            font-size: 34px;
            color: #1E90FF;
        }

        .card p {
            margin: 5px 0 0;
            color: #555;
            font-size: 16px;
        }

        /* Right Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            right: -300px;
            width: 300px;
            height: 100%;
            background-color: #5a6268;
            color: white;
            transition: right 0.3s;
            z-index: 1000;
            padding: 20px;
            box-sizing: border-box;
        }

        .sidebar.open {
            right: 0;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 20px 0;
        }

        .sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
        }

        .sidebar .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Footer */
        #footer {
            position: fixed;
            bottom: 0;
            left: 220px;
            width: calc(100% - 220px);
            background-color: #1E90FF;
            color: white;
            text-align: center;
            padding: 10px 0;
        }

        /* ================= MODAL ================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(6px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-box {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            animation: scaleIn 0.25s ease;
        }

        .modal-box h3 {
            margin: 0;
            color: #1E90FF;
        }

        .modal-box p {
            margin: 15px 0 25px;
            color: #555;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-confirm {
            flex: 1;
            background: #28a745;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-confirm:hover {
            background: #218838;
        }

        .btn-cancel {
            flex: 1;
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>

<body>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <ul>
              <div class="role-area">
                 <div class="role-circle-small"><?=htmlspecialchars(strtoupper(substr($role,0,1)))?></div>
                 <div class="role-label-small"><?=htmlspecialchars($role)?></div>
              </div>
              <li><a href="../admin/dashboard.php" <?= ($current === 'dashboard.php') ? 'class="active"' : '' ?>>Dashboard</a></li>
              <li><a href="../admin/employeelist.php" <?= ($current === 'employeelist.php') ? 'class="active"' : '' ?>>Employee List</a></li>
              <li><a href="../admin/leaverequest.php" <?= ($current === 'leaverequest.php') ? 'class="active"' : '' ?>>Leave Request</a></li>
              <li><a href="../admin/leavehistory.php" <?= ($current === 'leavehistory.php') ? 'class="active"' : '' ?>>Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
       
        <div class="navbar-title">Leave Management</div>
        <div class="profile" aria-label="profile">
            <div class="profile-circle"><?=htmlspecialchars($initials)?></div>
            <div class="profile-name"><?=htmlspecialchars($displayName)?></div>
        </div>
        <span class="settings-icon" onclick="toggleSidebar()" title="Settings">
            <i class="fa-solid fa-gear"></i>
        </span>
        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main Content -->
    <main>
        <div style="background:white;padding:16px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
            <h3>Leave History</h3>
            <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center;">
                <form method="get" style="display:flex;gap:8px;align-items:center;">
                    <label for="user">Username</label>
                    <input id="user" name="user" value="<?=htmlspecialchars($_GET['user'] ?? '')?>" placeholder="username" style="padding:6px;border:1px solid #ddd;border-radius:6px;" />
                    <button type="submit" style="background:#1E90FF;color:white;border:none;padding:6px 10px;border-radius:6px;">Filter</button>
                    <a href="leavehistory.php" style="margin-left:8px;color:#666;text-decoration:none;">Reset</a>
                </form>
            </div>

            <table style="width:100%;border-collapse:collapse;margin-top:10px;">
                <thead>
                    <tr style="text-align:left;border-bottom:1px solid #eee;">
                        <th style="padding:8px">First Name</th>
                        <th style="padding:8px">Middle Initial</th>
                        <th style="padding:8px">Last Name</th>
                        <th style="padding:8px">Username</th>
                        
                        <th style="padding:8px">Type</th>
                        <th style="padding:8px">Start</th>
                        <th style="padding:8px">End</th>
                        <th style="padding:8px">Reason</th>
                        <th style="padding:8px">Status</th>
                        <th style="padding:8px">Actioned By</th>
                        
                        <th style="padding:8px">Acted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && mysqli_num_rows($res) > 0): while($r = mysqli_fetch_assoc($res)): ?>
                        <tr style="border-bottom:1px solid #f3f3f3;vertical-align:top;">
                            <td style="padding:8px"><?=htmlspecialchars($r['firstname'] ?? '')?></td>
                            <td style="padding:8px"><?=htmlspecialchars($r['middlename'] ? strtoupper(mb_substr($r['middlename'],0,1)) . '.' : '')?></td>
                            <td style="padding:8px"><?=htmlspecialchars($r['lastname'] ?? '')?></td>
                            <td style="padding:8px"><?=htmlspecialchars($r['username'])?></td>

                            <td style="padding:8px"><?=htmlspecialchars($r['leave_type'])?></td>
                            <td style="padding:8px"><?=htmlspecialchars($r['start_date'])?></td>
                            <td style="padding:8px"><?=htmlspecialchars($r['end_date'])?></td>
                            <td style="padding:8px;max-width:240px;word-wrap:break-word;"><?=nl2br(htmlspecialchars($r['reason']))?></td>
                            <td style="padding:8px"><?=htmlspecialchars(ucfirst($r['status']))?></td>
                            <td style="padding:8px"><?=htmlspecialchars($r['admin_name'] ?? $r['admin_id'] ?? '')?></td>

                            <td style="padding:8px"><?=htmlspecialchars($r['acted_at'] ?? '')?></td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="13" style="padding:12px;color:#666;">No history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Right Sidebar -->
    <div id="sidebar" class="sidebar" aria-hidden="true">
        <div class="close-btn" onclick="toggleSidebar()">&times;</div>
        <h3 style="color:#fff;margin-top:6px">Settings</h3>
        <ul>
            <li><a href="../security/security_question.php">Set Security Questions</a></li>
            <li><a href="../admin/employeelist.php">Manage Employees</a></li>
        </ul>
    </div>

    <!-- Footer -->
    <div id="footer">
        <p>@South Loan & Finance Company Inc. 2024</p>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="modal-overlay">
        <div class="modal-box">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmLogout()">Yes</button>
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
            window.location.href = "logout.php";
        }
    </script>

</body>
</html>
