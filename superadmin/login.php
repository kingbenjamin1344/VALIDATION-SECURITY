<?php
session_start();
require_once __DIR__ . '/credentials.php';

if (isset($_SESSION['superadmin'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === DEFAULT_SUPERADMIN_USER && $password === DEFAULT_SUPERADMIN_PASSWORD) {
        $_SESSION['superadmin'] = $username;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Superadmin Login</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f4f6f8;margin:0}
        .wrap{max-width:380px;margin:80px auto;padding:24px;background:#fff;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,.08)}
        h2{margin:0 0 12px}
        label{display:block;margin:8px 0 4px;font-size:14px}
        input[type=text],input[type=password]{width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px}
        button{margin-top:12px;padding:10px 14px;background:#0078d4;color:#fff;border:none;border-radius:4px;cursor:pointer}
        .error{color:#b00020;margin-top:10px}
    </style>
</head>
<body>
<div class="wrap">
    <h2>Superadmin Login</h2>
    <form method="post" action="">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
        <button type="submit">Sign in</button>
        <?php if ($error): ?>
            <div class="error"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
    </form>
    <p style="margin-top:12px;font-size:13px;color:#666">Default credentials: <strong>superadmin / ChangeMe123!</strong></p>
</div>
</body>
</html>
