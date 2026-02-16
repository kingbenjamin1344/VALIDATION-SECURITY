<?php
session_start();
$role = $_SESSION['role'] ?? 'superadmin';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Superadmin Dashboard</title>
    <style>
        :root{--blue:#1698ff;--nav-blue:#1f9bff;--bg:#f5f7fb;--card-bg:#fff}
        *{box-sizing:border-box}
        body{font-family:Inter,Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:#243042}
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
        .header{height:64px;background:var(--blue);color:#fff;display:flex;align-items:center;padding:0 24px;justify-content:center}
        .header .title{font-size:18px;font-weight:600}
        .container{padding:28px 34px;flex:1}
        /* cards */
        .cards{display:flex;gap:20px;align-items:flex-start}
        .card{background:var(--card-bg);padding:20px;border-radius:10px;box-shadow:0 6px 18px rgba(15,23,42,0.06);flex:1;min-width:180px;text-align:center}
        .card .num{font-size:34px;color:var(--blue);font-weight:600}
        .card .label{margin-top:6px;color:#6b7280}
        /* responsive */
        @media (max-width:900px){.cards{flex-direction:column}}
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
            <a href="logout.php">Logout</a>
        </nav>
    </aside>
    <div class="content">
        <header class="header">
            <div class="title">Leave Management</div>
        </header>
        <div class="container">
            <div class="cards">
                <div class="card">
                    <div class="num">120</div>
                    <div class="label">Total Employees</div>
                </div>
                <div class="card">
                    <div class="num">8</div>
                    <div class="label">Pending Leaves</div>
                </div>
                <div class="card">
                    <div class="num">15</div>
                    <div class="label">Approved Leaves</div>
                </div>
                <div class="card">
                    <div class="num">3</div>
                    <div class="label">Declined Requests</div>
                </div>
            </div>
            <div style="margin-top:28px;color:#6b7280"></div>
        </div>
    </div>
</div>
</body>
</html>
        
</div>
</body>
</html>
