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
// DB and user list
$message = '';
require_once __DIR__ . '/../regform/config.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = mysqli_connect('localhost','root','','it107_security_sql');
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
    }
    header('Location: employeelist.php');
    exit;
}

$users = [];
$res = mysqli_query($conn, "SELECT id,id_no,firstname,middlename,lastname,suffix,username,email,birthdate,age,sex,purok,barangay,municipality,province,zipcode,country FROM users ORDER BY id DESC");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) { $users[] = $row; }
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
            padding: 15px 20px;
        }

        .left-sidebar ul li a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            display: block;
        }

        .left-sidebar ul li a:hover {
            background-color: rgba(255,255,255,0.2);
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
        .panel{background:#fff;border-radius:8px;padding:18px;box-shadow:0 6px 18px rgba(15,23,42,0.06)}
        table{width:100%;border-collapse:collapse;margin-top:8px}
        th,td{padding:10px;border-bottom:1px solid #eef2f7;text-align:left}
        th{background:#fafafa}
        .actions a{margin-right:8px;color:#1E90FF;text-decoration:none}
        .msg{margin:10px 0;color:#064e3b;background:#ecfdf5;padding:8px;border-radius:6px;border:1px solid #bbf7d0}
        /* Address sidebar */
        .address-sidebar{position:fixed;top:0;right:-420px;width:380px;height:100%;background:#fff;box-shadow:0 8px 30px rgba(2,6,23,0.2);transition:right .28s ease;z-index:1500;padding:20px;box-sizing:border-box}
        .address-sidebar.open{right:0}
        .address-sidebar h4{margin:0 0 12px;color:#1E90FF}
        .address-row{margin:8px 0;color:#333}
        .btn-address{background:#1E90FF;color:#fff;padding:6px 10px;border-radius:6px;border:none;cursor:pointer;text-decoration:none}

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
   <li><a href="../admin/dashboard.php">Dashboard</a></li>
              <li><a href="../admin/employeelist.php">Employee List</a></li>
              <li><a href="../admin/leaverequest.php">Leave Request</a></li>
              <li><a href="../admin/leavehistory.php">Leave History</a></li>
        </ul>
    </div>

    <!-- Top Navbar -->
    <div class="top-navbar">
       
        <div class="navbar-title">Leave Management</div>
        <div class="profile" aria-label="profile">
            <div class="profile-circle"><?=htmlspecialchars($initials)?></div>
            <div class="profile-name"><?=htmlspecialchars($displayName)?></div>
        </div>
        <span class="logout-icon" onclick="logout()">
            <i class="fa-solid fa-right-from-bracket"></i>
        </span>
    </div>

    <!-- Main Content -->
    <main>
        <div class="panel">
            <h3>Employee List</h3>
            <?php if ($message): ?><div class="msg"><?=htmlspecialchars($message)?></div><?php endif; ?>
            <table>
                <thead>
                    <tr>
                        <th>ID No</th>
                        <th>Full Name</th>
                        <th>Suffix</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Birthdate</th>
                        <th>Sex</th>
                        <th>Age</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="9" style="color:#6b7280">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?=htmlspecialchars($u['id_no'])?></td>
                                <td><?=htmlspecialchars(trim($u['firstname'].' '.$u['middlename'].' '.$u['lastname']))?></td>
                                <td><?=htmlspecialchars($u['suffix'])?></td>
                                <td><?=htmlspecialchars($u['username'])?></td>
                                <td><?=htmlspecialchars($u['email'])?></td>
                                <td><?=htmlspecialchars($u['birthdate'])?></td>
                                <td><?=htmlspecialchars($u['sex'])?></td>
                                <td><?=htmlspecialchars($u['age'])?></td>
                                <td class="actions">
                                    
                                    <button
                                        class="btn-address"
                                        type="button"
                                        onclick="showAddress(this)"
                                        data-purok="<?=htmlspecialchars($u['purok'], ENT_QUOTES)?>"
                                        data-barangay="<?=htmlspecialchars($u['barangay'], ENT_QUOTES)?>"
                                        data-municipality="<?=htmlspecialchars($u['municipality'], ENT_QUOTES)?>"
                                        data-province="<?=htmlspecialchars($u['province'], ENT_QUOTES)?>"
                                        data-zipcode="<?=htmlspecialchars($u['zipcode'], ENT_QUOTES)?>"
                                        data-country="<?=htmlspecialchars($u['country'], ENT_QUOTES)?>"
                                    >Address Details</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Right Sidebar -->
    

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

    <!-- Address Sidebar -->
    <div id="addressSidebar" class="address-sidebar" aria-hidden="true">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h4>Address Details</h4>
            <button onclick="closeAddress()" style="background:transparent;border:none;font-size:18px;cursor:pointer">&times;</button>
        </div>
        <div class="address-row"><strong>Purok:</strong> <span id="addr-purok"></span></div>
        <div class="address-row"><strong>Barangay:</strong> <span id="addr-barangay"></span></div>
        <div class="address-row"><strong>Municipality:</strong> <span id="addr-municipality"></span></div>
        <div class="address-row"><strong>Province:</strong> <span id="addr-province"></span></div>
        <div class="address-row"><strong>Zipcode:</strong> <span id="addr-zipcode"></span></div>
        <div class="address-row"><strong>Country:</strong> <span id="addr-country"></span></div>
    </div>

    <script>
        function showAddress(btn){
            var sidebar = document.getElementById('addressSidebar');
            document.getElementById('addr-purok').textContent = btn.dataset.purok || '';
            document.getElementById('addr-barangay').textContent = btn.dataset.barangay || '';
            document.getElementById('addr-municipality').textContent = btn.dataset.municipality || '';
            document.getElementById('addr-province').textContent = btn.dataset.province || '';
            document.getElementById('addr-zipcode').textContent = btn.dataset.zipcode || '';
            document.getElementById('addr-country').textContent = btn.dataset.country || '';
            sidebar.classList.add('open');
            sidebar.setAttribute('aria-hidden', 'false');
        }
        function closeAddress(){
            var sidebar = document.getElementById('addressSidebar');
            sidebar.classList.remove('open');
            sidebar.setAttribute('aria-hidden', 'true');
        }
    </script>

</body>
</html>
