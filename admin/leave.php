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

// Fetch leave requests
$leaves = $conn->query("SELECT id, username, leave_type, start_date, end_date, reason, status, created_at FROM leave_requests WHERE status = 'pending' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard UI</title>
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

        .left-sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
            padding: 15px 20px;
        }

        .left-sidebar ul li a:hover,
        .left-sidebar ul li a.active {
            background-color: #063970;
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
            gap: 18px;
            z-index: 999;
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
        }

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
        }

        main {
            margin-left: 220px;
            padding: 90px 20px;
            width: 100%;
            background: #f5f7fb;
            min-height: 100vh;
        }

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
        }

        .card h2 {
            margin: 0;
            font-size: 34px;
            color: #1E90FF;
        }

        .card p {
            margin: 5px 0 0;
            color: #555;
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
        }

        .sidebar.open {
            right: 0;
        }

        .sidebar .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-box {
            background: white;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-confirm { background:#28a745; color:white; border:none; padding:10px; flex:1; }
        .btn-cancel  { background:#dc3545; color:white; border:none; padding:10px; flex:1; }
        .btn-view-address { background:#0b61d0; color:white; border:none; padding:6px 10px; border-radius:4px; cursor:pointer }
        .btn-approve { background:#28a745; color:white; border:none; padding:6px 8px; border-radius:4px; cursor:pointer }
        .btn-decline { background:#dc3545; color:white; border:none; padding:6px 8px; border-radius:4px; cursor:pointer }
        .status-approved { color: #ff0000; font-weight:600 }
        .status-declined { color: #008000; font-weight:600 }
        .status-pending { color: #6c757d; font-weight:600 }
    </style>
</head>

<body>

    <!-- Left Sidebar -->
    <div class="left-sidebar">
        <div class="role-area">
            <div class="role-circle-small">A</div>
            <div class="role-label-small">Admin</div>
        </div>
        <ul>
            <li><a href="../admin/dashboard.php" >Dashboard</a></li>
            <li><a href="../admin/userlist.php">User List</a></li>
            <li><a href="../admin/leave.php" class="active">Pending Request</a></li>
            <li><a href="../admin/history.php">Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()">&#9881;</span>
        <div class="navbar-title">Leave Management</div>

        <div class="profile">
            <div class="profile-circle"><?php echo htmlspecialchars($adm_initials ?: 'A'); ?></div>
            <div class="profile-name"><?php echo htmlspecialchars(trim($adm_first . ' ' . $adm_last) ?: ($_SESSION['username'] ?? 'Admin')); ?></div>
        </div>

        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main -->
    <main>
        <h2 style="margin-bottom:18px">Pending Request</h2>
        <div style="overflow:auto; background:white; padding:12px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.06)">
            <table style="width:100%; border-collapse:collapse">
                <thead>
                    <tr style="background:#f1f5f9; text-align:left">
                        
                        <th style="padding:8px">Username</th>
                        <th style="padding:8px">Type</th>
                        <th style="padding:8px">Start</th>
                        <th style="padding:8px">End</th>
                        <th style="padding:8px">Reason</th>
                        <th style="padding:8px">Status</th>
                        <th style="padding:8px">Requested</th>
                        <th style="padding:8px">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$leaves || $leaves->num_rows === 0): ?>
                    <tr><td colspan="8" style="padding:12px">No leave requests found.</td></tr>
                <?php else: $i=1; while ($r = $leaves->fetch_assoc()): ?>
                    <tr>
                      
                        <td style="padding:8px; vertical-align:top"><?php echo htmlspecialchars($r['username']); ?></td>
                        <td style="padding:8px; vertical-align:top"><?php echo htmlspecialchars($r['leave_type']); ?></td>
                        <td style="padding:8px; vertical-align:top"><?php echo htmlspecialchars($r['start_date']); ?></td>
                        <td style="padding:8px; vertical-align:top"><?php echo htmlspecialchars($r['end_date']); ?></td>
                        <td style="padding:8px; vertical-align:top"><?php echo htmlspecialchars($r['reason']); ?></td>
                        <td style="padding:8px; vertical-align:top">
                            <?php $s = $r['status']; $sc = ($s==='approved'?'status-approved':($s==='declined'?'status-declined':'status-pending')); echo '<span class="'.$sc.'">'.htmlspecialchars(ucfirst($s)).'</span>'; ?>
                        </td>
                        <td style="padding:8px; vertical-align:top"><?php echo htmlspecialchars($r['created_at']); ?></td>
                        <td style="padding:8px; vertical-align:top">
                            <?php if ($r['status'] === 'pending'): ?>
                                <button class="btn-approve action-btn" data-id="<?php echo (int)$r['id']; ?>" data-action="approve" style="margin-right:6px">Approve</button>
                                <button class="btn-decline action-btn" data-id="<?php echo (int)$r['id']; ?>" data-action="decline">Decline</button>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Right Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()">&times;</span>
        <ul>
            <li><a href="../security/input_security_question.php">Set Security</a></li>
        </ul>
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

    <!-- Action Modal -->
    <div id="actionModal" class="modal-overlay">
        <div class="modal-box">
            <h3 id="actionTitle">Confirm</h3>
            <p id="actionMessage">Are you sure?</p>
            <div class="modal-actions">
                <button class="btn-cancel" onclick="closeActionModal()">Cancel</button>
                <button class="btn-confirm" id="actionConfirm">Ok</button>
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
        // Redirect to server logout which destroys session and redirects to login
        window.location.href = '../logform/logout.php';
    }

    // Action modal handling
    function openActionModal(title, message, id, action) {
        const modal = document.getElementById('actionModal');
        document.getElementById('actionTitle').textContent = title;
        document.getElementById('actionMessage').textContent = message;
        modal.style.display = 'flex';
        modal.dataset.targetId = id;
        modal.dataset.action = action;
    }
    function closeActionModal() {
        const modal = document.getElementById('actionModal');
        modal.style.display = 'none';
        delete modal.dataset.targetId;
        delete modal.dataset.action;
    }

    document.addEventListener('click', function(e){
        const btn = e.target.closest('.action-btn');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');
        const title = action === 'approve' ? 'Approve Request Leave' : 'Decline Request Leave';
        const message = action === 'approve' ? 'Approve this leave request?' : 'Decline this leave request?';
        openActionModal(title, message, id, action);
    });

    document.getElementById('actionConfirm').addEventListener('click', function(){
        const modal = document.getElementById('actionModal');
        const id = modal.dataset.targetId;
        const action = modal.dataset.action;
        if (!id || !action) { closeActionModal(); return; }

        const form = new FormData();
        form.append('id', id);
        form.append('action', action);

        fetch('leave.php', { method: 'POST', body: form, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    // remove row from table
                    const btn = document.querySelector('.action-btn[data-id="'+id+'"]');
                    if (btn) {
                        const tr = btn.closest('tr');
                        if (tr) tr.remove();
                    }
                }
                closeActionModal();
            }).catch(err => {
                closeActionModal();
            });
    });
</script>

</body>
</html>