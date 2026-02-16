<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

if (isset($_SESSION['admin'])) {
    header('Location: dashboard.php');
    exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $msg = 'Please enter username and password.';
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, fullname, username, password FROM admins WHERE username = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id, $fullname, $user, $hash);
        if (mysqli_stmt_fetch($stmt)) {
            if (password_verify($password, $hash)) {
                $_SESSION['admin'] = ['id' => $id, 'fullname' => $fullname, 'username' => $user];
                // insert activity log: login (uses helper to handle different schemas)
                $device = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $uname = $user;
                if (function_exists('insert_activity')) insert_activity($conn, $uname, 'admin', 'login', $device);
                mysqli_stmt_close($stmt);
                header('Location: dashboard.php');
                exit;
            } else {
                $msg = 'Invalid username or password.';
            }
        } else {
            $msg = 'Invalid username or password.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;background:#f3f6fb;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
        .card{background:#fff;padding:28px;border-radius:8px;box-shadow:0 8px 24px rgba(20,30,60,0.08);width:380px}
        h2{margin:0 0 12px;font-size:18px;color:#243042}
        label{display:block;margin-bottom:6px;font-size:13px;color:#334155}
        input[type=text],input[type=password]{width:100%;padding:10px;border:1px solid #e6eef6;border-radius:6px;margin-bottom:12px}
        button{width:100%;padding:10px;background:#1f9bff;color:#fff;border:none;border-radius:6px;cursor:pointer}
        .msg{color:#b91c1c;font-size:13px;margin-bottom:12px}
        .note{font-size:12px;color:#64748b;margin-top:10px;text-align:center}
    </style>
</head>
<body>
    <div class="card">
        <h2>Admin Login</h2>
        <?php if ($msg): ?><div class="msg"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
        <form method="post">
            <label>Username</label>
            <input type="text" name="username" autocomplete="username">
            <label>Password</label>
            <input type="password" name="password" autocomplete="current-password">
            <button type="submit">Sign in</button>
        </form>
        
    </div>
</body>
</html>
