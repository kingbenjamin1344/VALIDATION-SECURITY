<?php
session_start();
$role = $_SESSION['role'] ?? 'superadmin';
// fetch counts
require_once __DIR__ . '/../regform/config.php';

$total_users = 0;
$total_admins = 0;
$pending_leaves = 0;
$approved_leaves = 0;
$declined_leaves = 0;

// users
$res = mysqli_query($conn, "SELECT COUNT(*) FROM users");
if ($res) { $r = mysqli_fetch_row($res); $total_users = (int)$r[0]; }

// admins (may not exist)
$res = @mysqli_query($conn, "SELECT COUNT(*) FROM admins");
if ($res) { $r = mysqli_fetch_row($res); $total_admins = (int)$r[0]; }

// leave counts from requestleave table
$res = @mysqli_query($conn, "SELECT COUNT(*) FROM requestleave WHERE status='pending'");
if ($res) { $r = mysqli_fetch_row($res); $pending_leaves = (int)$r[0]; }
$res = @mysqli_query($conn, "SELECT COUNT(*) FROM requestleave WHERE status='approved'");
if ($res) { $r = mysqli_fetch_row($res); $approved_leaves = (int)$r[0]; }
$res = @mysqli_query($conn, "SELECT COUNT(*) FROM requestleave WHERE status='declined'");
if ($res) { $r = mysqli_fetch_row($res); $declined_leaves = (int)$r[0]; }

$total_employees = $total_users + $total_admins;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Superadmin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root{--blue:#1698ff;--nav-blue:#1f9bff;--bg:#f5f7fb;--card-bg:#fff}
        *{box-sizing:border-box}
        body{font-family:Inter,Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#243042;padding-bottom:64px;overflow-x:hidden}
        .app{display:flex;min-height:100vh}
        /* left nav */
        .sidebar{width:220px;background:var(--nav-blue);color:#fff;padding:28px 18px 18px}
        .role-area{ text-align:center; padding:8px 0 16px }
        .role-circle-small{ width:48px; height:48px; border-radius:50%; background:#fff; color:var(--nav-blue); display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin:0 auto 8px }
        .role-label-small{ color:#fff; font-size:13px; text-transform:capitalize }
        .brand{font-weight:600;font-size:18px;margin-bottom:28px}
        .nav{display:flex;flex-direction:column;gap:12px}
        .nav a{color:#fff;text-decoration:none;padding:10px 12px;border-radius:6px;display:block}
        .nav a:hover{background:rgba(255,255,255,0.08)}
        /* main area */
        .content{flex:1;display:flex;flex-direction:column}
        .header{height:64px;background:var(--blue);color:#fff;display:flex;align-items:center;padding:0 24px;justify-content:center;position:relative}
        .logout-icon{position:absolute;right:24px;top:50%;transform:translateY(-50%);font-size:20px;cursor:pointer}
        .header .title{font-size:18px;font-weight:600}
        .container{padding:28px 34px;flex:1}
        /* cards */
        .cards{display:flex;gap:20px;align-items:flex-start}
        .card{background:var(--card-bg);padding:20px;border-radius:10px;box-shadow:0 6px 18px rgba(15,23,42,0.06);flex:1;min-width:180px;text-align:center}
        .card .num{font-size:34px;color:var(--blue);font-weight:600}
        .card .label{margin-top:6px;color:#6b7280}
        /* responsive */
        @media (max-width:900px){.cards{flex-direction:column}}
        /* footer */
        #footer{position:fixed;bottom:0;left:0;right:0;height:48px;background-color:#1E90FF;color:white;text-align:center;padding:0;z-index:1000;display:flex;align-items:center;justify-content:center}
        .footer-wrap{box-sizing:border-box;padding:10px 0;padding-left:220px;max-width:calc(100% - 220px)}
        @media (max-width:900px){.footer-wrap{padding-left:0;max-width:100%}}
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"></div>
        <div class="role-area">
            <div class="role-circle-small"><?=htmlspecialchars(strtoupper(substr($role,0,1)))?></div>
            <div class="role-label-small"><?=htmlspecialchars($role)?></div>
        </div>
            <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="addacc.php">Add Account</a>
            <a href="userlist.php">Employee List</a>
            <a href="create.php">Create Admin</a>
            <a href="logs.php">Activity Logs</a>
            </nav>
    </aside>
    <div class="content">
        <header class="header">
            <div class="title">Leave Management</div>
            <span class="logout-icon" onclick="logout()">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
        </header>
        <div class="container">
            <div class="cards">
                <div class="card">
                    <div class="num"><?=htmlspecialchars($total_employees)?></div>
                    <div class="label">Total Employees</div>
                </div>
                <div class="card">
                    <div class="num"><?=htmlspecialchars($pending_leaves)?></div>
                    <div class="label">Pending Leaves</div>
                </div>
                <div class="card">
                    <div class="num"><?=htmlspecialchars($approved_leaves)?></div>
                    <div class="label">Approved Leaves</div>
                </div>
                <div class="card">
                    <div class="num"><?=htmlspecialchars($declined_leaves)?></div>
                    <div class="label">Declined Requests</div>
                </div>
                <div class="card">
                    <div class="num"><?=htmlspecialchars($total_admins)?></div>
                    <div class="label">Admins</div>
                </div>
                <div class="card">
                    <div class="num"><?=htmlspecialchars($total_users)?></div>
                    <div class="label">Users</div>
                </div>
            </div>
            <div style="margin-top:28px;color:#6b7280"></div>
        </div>
    </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" style="display:none;position:fixed;inset:0;align-items:center;justify-content:center;background:rgba(0,0,0,0.4);z-index:2000">
        <div style="background:#fff;padding:20px;border-radius:8px;width:320px;text-align:center;box-shadow:0 8px 24px rgba(0,0,0,0.2)">
            <h3 style="margin:0;color:var(--blue)">Confirm Logout</h3>
            <p style="color:#555">Are you sure you want to logout?</p>
            <div style="display:flex;gap:10px">
                <button style="flex:1;background:#dc3545;color:#fff;border:none;padding:10px;border-radius:6px;cursor:pointer" onclick="closeLogoutModal()">Cancel</button>
                <button style="flex:1;background:#28a745;color:#fff;border:none;padding:10px;border-radius:6px;cursor:pointer" onclick="confirmLogout()">Yes</button>
            </div>
        </div>
    </div>

    <script>
        function logout(){ document.getElementById('logoutModal').style.display='flex'; }
        function closeLogoutModal(){ document.getElementById('logoutModal').style.display='none'; }
        function confirmLogout(){ window.location.href='logout.php'; }
    </script>

    <div id="footer">
        <div class="footer-wrap"><p>@South Loan & Finance Company Inc. 2024</p></div>
    </div>

</body>
</html>
