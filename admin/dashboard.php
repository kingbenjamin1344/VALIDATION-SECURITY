<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

$admin = $_SESSION['admin'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Dashboard</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#f5f7fb;color:#243042}
        .top{background:#1f9bff;color:#fff;padding:16px 20px;display:flex;justify-content:space-between;align-items:center}
        .container{padding:20px}
        .card{background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(15,23,42,0.06)}
        a.btn{background:#1698ff;color:#fff;padding:8px 10px;border-radius:6px;text-decoration:none}
    </style>
</head>
<body>
    <div class="top">
        <div>Admin Dashboard</div>
        <div>Welcome, <?php echo htmlspecialchars($admin['fullname']); ?> — <a class="btn" href="logout.php">Logout</a></div>
    </div>
    <div class="container">
        <div class="card">
            <h3>Overview</h3>
            <p>You're signed in as <strong><?php echo htmlspecialchars($admin['username']); ?></strong>.</p>
            <p>Use the superadmin panel to manage other admins or navigate the application.</p>
        </div>
    </div>
</body>
</html>
