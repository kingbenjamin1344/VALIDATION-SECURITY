<?php
session_start();
$displayName = '';
$role = $_SESSION['role'] ?? 'user';
$current = basename($_SERVER['PHP_SELF']);
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
// connect and handle form actions
require_once __DIR__ . '/../regform/config.php';

$username = $_SESSION['username'] ?? $_SESSION['user']['username'] ?? '';
$user_id = $_SESSION['user']['id'] ?? null;
$email = $_SESSION['user']['email'] ?? '';
$fullname = $_SESSION['user']['fullname'] ?? $displayName;

// format fullname to: FirstName M. LastName (remove duplicate segments)
function format_display_name($name) {
    $parts = preg_split('/\s+/', trim((string)$name));
    $uniq = [];
    foreach ($parts as $p) {
        if ($p === '') continue;
        $lower = mb_strtolower($p);
        $found = false;
        foreach ($uniq as $u) { if (mb_strtolower($u) === $lower) { $found = true; break; } }
        if (! $found) $uniq[] = $p;
    }
    if (count($uniq) === 0) return '';
    if (count($uniq) === 1) return $uniq[0];
    $first = $uniq[0];
    $last = $uniq[count($uniq)-1];
    $mi = '';
    if (count($uniq) >= 2) {
        $mi = strtoupper(mb_substr($uniq[1], 0, 1));
    }
    if ($mi !== '') return $first . ' ' . $mi . '. ' . $last;
    return $first . ' ' . $last;
}

$show_submit_modal = false;

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $leave_type = mysqli_real_escape_string($conn, $_POST['leave_type'] ?? 'vacation');
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
    $reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');

    if ($username && $start_date && $end_date) {
        $formatted_fullname = format_display_name($fullname);
        $stmt = mysqli_prepare($conn, "INSERT INTO requestleave (user_id, username, fullname, email, leave_type, start_date, end_date, reason, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        mysqli_stmt_bind_param($stmt, 'isssssss', $user_id, $username, $formatted_fullname, $email, $leave_type, $start_date, $end_date, $reason);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $show_submit_modal = true;
    }
}

// Handle cancel action
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id'])) {
    $rid = (int)$_GET['id'];
    if ($rid > 0 && $username) {
        // ensure ownership - always close the statement after fetching
        $q = mysqli_prepare($conn, "SELECT username, status FROM requestleave WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($q, 'i', $rid);
        mysqli_stmt_execute($q);
        mysqli_stmt_bind_result($q, $r_username, $r_status);
        $fetched = mysqli_stmt_fetch($q);
        mysqli_stmt_close($q);
        if ($fetched) {
            if ($r_username === $username && $r_status === 'pending') {
                $u = mysqli_prepare($conn, "UPDATE requestleave SET status = 'declined', updated_at = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($u, 'i', $rid);
                mysqli_stmt_execute($u);
                mysqli_stmt_close($u);
            }
        }
    }
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
        <div class="role-area">
            <div class="role-circle-small"><?=
                htmlspecialchars(strtoupper(substr($role,0,1)))
            ?></div>
            <div class="role-label-small"><?=htmlspecialchars($role)?></div>
        </div>
        <ul>
            <li><a href="../logform/indexes.php" <?= ($current === 'indexes.php') ? 'class="active"' : '' ?>>Dashboard</a></li>
            <li><a href="../logform/requestleave.php" <?= ($current === 'requestleave.php') ? 'class="active"' : '' ?>>Request Leave</a></li>
            <li><a href="../logform/leave.php" <?= ($current === 'leave.php') ? 'class="active"' : '' ?>>Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
        <span class="settings-icon" onclick="toggleSidebar()">&#9881;</span>
        <div class="navbar-title">Leave Management</div>
        <div class="profile" aria-label="profile">
            <?php
                $profile_full = $displayName;
                if (!empty($_SESSION['user'])) {
                    $u = $_SESSION['user'];
                    $name_parts = array_filter([
                        trim((string)($u['firstname'] ?? '')),
                        trim((string)($u['middlename'] ?? '')),
                        trim((string)($u['lastname'] ?? '')),
                        trim((string)($u['suffix'] ?? '')),
                    ]);
                    if (count($name_parts) > 0) {
                        $profile_full = implode(' ', $name_parts);
                    }
                }
                $display_initials = '';
                if (!empty($initials)) {
                    $display_initials = $initials;
                } else {
                    $parts = preg_split('/\s+/', trim($profile_full));
                    $display_initials = strtoupper((isset($parts[0]) ? substr($parts[0],0,1) : '') . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
                }
            ?>
            <div class="profile-circle"><?=htmlspecialchars($display_initials)?></div>
            <?php
                $profile_short = $displayName;
                if (!empty($_SESSION['user'])) {
                    $u = $_SESSION['user'];
                    $first = trim((string)($u['firstname'] ?? ''));
                    $last = trim((string)($u['lastname'] ?? ''));
                    if ($first !== '' || $last !== '') {
                        $profile_short = trim($first . ' ' . $last);
                    }
                }
            ?>
            <div class="profile-name"><?=htmlspecialchars($profile_short)?></div>
        </div>
        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main Content -->
    <main>
        <div style="display:flex;gap:20px;align-items:flex-start;">
            <div style="flex:0 0 420px;background:white;padding:18px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
                <h3>Request Leave</h3>
                <form method="post">
                    <div style="margin-bottom:10px;">
                        <label>Type of leave</label>
                        <select name="leave_type" required style="width:100%;padding:8px;margin-top:6px;">
                            <option value="vacation">Vacation</option>
                            <option value="sick">Sick</option>
                            <option value="emergency">Emergency</option>
                            <option value="maternity">Maternity</option>
                        </select>
                    </div>
                    <div style="display:flex;gap:10px;margin-bottom:10px;">
                        <div style="flex:1;">
                            <label>Start date</label>
                            <input type="date" name="start_date" required style="width:100%;padding:8px;margin-top:6px;" />
                        </div>
                        <div style="flex:1;">
                            <label>End date</label>
                            <input type="date" name="end_date" required style="width:100%;padding:8px;margin-top:6px;" />
                        </div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label>Reason</label>
                        <textarea name="reason" rows="4" style="width:100%;padding:8px;margin-top:6px;"></textarea>
                    </div>
                    <div style="text-align:right;">
                        <button type="submit" name="submit_leave" style="background:#1E90FF;color:white;border:none;padding:10px 14px;border-radius:6px;cursor:pointer;">Submit Request</button>
                    </div>
                </form>
            </div>

            <div style="flex:1;background:white;padding:12px;border-radius:10px;box-shadow:0 6px 18px rgba(0,0,0,0.06);">
                <h3>Your Leave Requests</h3>
                <?php
                    $safe_user = mysqli_real_escape_string($conn, (string)$username);
                    // show only pending requests here so responded requests move to leave.php
                    $q = mysqli_query($conn, "SELECT id, leave_type, start_date, end_date, reason, status, created_at FROM requestleave WHERE username='{$safe_user}' AND status='pending' ORDER BY created_at DESC");
                ?>
                <table style="width:100%;border-collapse:collapse;margin-top:8px;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid #eee;">
                            <th style="padding:8px">Type</th>
                            <th style="padding:8px">Start</th>
                            <th style="padding:8px">End</th>
                            <th style="padding:8px">Reason</th>
                            <th style="padding:8px">Status</th>
                            <th style="padding:8px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($q && mysqli_num_rows($q) > 0): while($row = mysqli_fetch_assoc($q)): ?>
                            <tr style="border-bottom:1px solid #f3f3f3;">
                                <td style="padding:8px;vertical-align:top;"><?=htmlspecialchars($row['leave_type'])?></td>
                                <td style="padding:8px;vertical-align:top;"><?=htmlspecialchars($row['start_date'])?></td>
                                <td style="padding:8px;vertical-align:top;"><?=htmlspecialchars($row['end_date'])?></td>
                                <td style="padding:8px;vertical-align:top;max-width:280px;word-wrap:break-word;">
                                    <?=nl2br(htmlspecialchars($row['reason']))?>
                                </td>
                                <td style="padding:8px;vertical-align:top;">
                                    <?php
                                        $st = $row['status'] ?? 'pending';
                                        $color = '#6c757d'; // default gray for pending
                                        if ($st === 'approved') $color = '#28a745';
                                        if ($st === 'declined') $color = '#dc3545';
                                    ?>
                                    <span style="color:<?=htmlspecialchars($color)?>;font-weight:600;"><?=htmlspecialchars(ucfirst($st))?></span>
                                </td>
                                <td style="padding:8px;vertical-align:top;">
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <a href="?action=cancel&id=<?=intval($row['id'])?>" style="color:#dc3545;text-decoration:none;">Cancel</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="6" style="padding:12px;color:#666;">No requests found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Submission modal -->
        <div id="submitModal" class="modal-overlay" style="display: <?= $show_submit_modal ? 'flex' : 'none' ?>;">
            <div class="modal-box">
                <h3>Request Submitted</h3>
                <p>Your Request is Submitted</p>
                <div class="modal-actions">
                    <button class="btn-confirm" onclick="document.getElementById('submitModal').style.display='none';window.location.href=window.location.pathname;">OK</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Right Sidebar -->
    <div class="sidebar" id="sidebar">
        <span class="close-btn" onclick="toggleSidebar()">&times;</span>
        <ul>
            <li><a href="#">Change Password</a></li>
            <li><a href="../security/security_question.php">Set Security</a></li>
        </ul>
    </div>

    <!-- Footer -->
    <div id="footer">
        <p></p>
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
