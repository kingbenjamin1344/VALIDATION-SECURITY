<?php
session_start();
require_once __DIR__ . '/credentials.php';
require_once __DIR__ . '/../regform/config.php';

// If already logged in as superadmin, go to dashboard
if (isset($_SESSION['superadmin'])) {
    header('Location: dashboard.php');
    exit;
}

// Otherwise, use the centralized login form
header('Location: ../logform/login.php');
exit;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Superadmin Login</title>
    <style>
        :root{--bg:#0b63d6;--card:#ffffff;--muted:#6c757d;--accent:#0056b3}
        html,body{height:100%;margin:0;font-family:Inter,system-ui,Arial,Helvetica,sans-serif;background:linear-gradient(180deg,var(--bg),#0a53b4);-webkit-font-smoothing:antialiased;overflow:hidden;overflow-x:hidden}
        .overlay{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:40px}
        .wrap{width:100%;max-width:420px;padding:28px;background:var(--card);border-radius:12px;box-shadow:0 10px 30px rgba(2,6,23,0.35);border:1px solid rgba(255,255,255,0.06);box-sizing:border-box;overflow:hidden}
        h2{margin:0 0 12px;font-size:20px;color:#0b3b71}
        label{display:block;margin:8px 0 6px;font-size:13px;color:#333}
        input[type=text],input[type=password]{width:100%;padding:12px 14px;border:1px solid #e6eef9;border-radius:10px;background:#fbfdff;box-shadow:none;outline:none;transition:box-shadow .12s ease,border-color .12s ease;margin-bottom:10px;box-sizing:border-box}
        input[type=text]::placeholder,input[type=password]::placeholder{color:#9fb0cd}
        input[type=text]:focus,input[type=password]:focus{border-color:rgba(11,99,214,0.95);box-shadow:0 6px 20px rgba(11,99,214,0.12)}
        button{margin-top:6px;padding:12px 14px;background:var(--bg);color:#fff;border:none;border-radius:10px;cursor:pointer;font-weight:600;width:100%;display:inline-block}
        button:hover{background:var(--accent)}
        button:focus{outline:none;box-shadow:0 6px 20px rgba(11,99,214,0.16)}
        .secondary{display:inline-block;margin-top:12px;padding:10px 12px;background:#f1f7ff;color:var(--bg);border-radius:10px;text-align:center;text-decoration:none;border:1px solid rgba(11,99,214,0.12);width:100%;box-sizing:border-box}
        .secondary:hover{background:#e8f2ff}
        .error{color:#b00020;margin-top:10px}
        .helper{margin-top:12px;font-size:13px;color:var(--muted)}
        #footer{position:fixed;bottom:0;left:0;right:0;height:48px;background-color:#1E90FF;color:white;text-align:center;padding:0;z-index:1000;display:flex;align-items:center;justify-content:center}
        .footer-wrap{box-sizing:border-box;padding:10px 0;padding-left:220px;max-width:calc(100% - 220px)}
        @media (max-width:900px){.footer-wrap{padding-left:0;max-width:100%}}
    </style>
</head>
<body>
<div class="overlay">
    <div class="wrap" role="dialog" aria-labelledby="login-title">
    <h2>Superadmin Login</h2>
    <form method="post" action="">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required autofocus placeholder="Enter username">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required placeholder="Enter password">
        <button type="submit">Sign in</button>
        <a href="../logform/login.php" class="secondary" aria-label="Back to user login">Back to user login</a>
        <?php if ($error): ?>
            <div class="error"><?=htmlspecialchars($error)?></div>
        <?php endif; ?>
    </form>
    </div>
</div>
    <div id="footer">
        <div class="footer-wrap"><p>@South Loan & Finance Company Inc. 2024</p></div>
    </div>
</body>
</html>
