<?php
session_start();
include __DIR__ . '/credentials.php';
include __DIR__ . '/../regform/config.php';

// Ensure superadmins and activity_logs exist
if (isset($conn)) {
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS superadmins (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(191) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, email VARCHAR(255), created_at DATETIME)");
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NULL, username VARCHAR(255), email VARCHAR(255), action VARCHAR(255), device VARCHAR(512), created_at DATETIME)");

    // Seed default superadmin if table is empty
    $r = mysqli_query($conn, "SELECT COUNT(*) as c FROM superadmins");
    $c = ($r && $row = mysqli_fetch_assoc($r)) ? intval($row['c']) : 0;
    if ($c === 0 && defined('DEFAULT_SUPERADMIN_USER') && defined('DEFAULT_SUPERADMIN_PASSWORD')) {
        $user = mysqli_real_escape_string($conn, DEFAULT_SUPERADMIN_USER);
        $hash = password_hash(DEFAULT_SUPERADMIN_PASSWORD, PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO superadmins (username,password_hash,created_at) VALUES ('$user','".mysqli_real_escape_string($conn,$hash)."',NOW())");
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = isset($_POST['username']) ? trim($_POST['username']) : '';
    $p = isset($_POST['password']) ? $_POST['password'] : '';

    $authenticated = false;
    if (isset($conn) && $u !== '') {
        // Determine which password column exists. Prefer password_hash.
        $pwcol = 'password_hash';
        $has_pwcol = false;
        $res = @mysqli_query($conn, "SHOW COLUMNS FROM superadmins LIKE 'password_hash'");
        if ($res && mysqli_num_rows($res) > 0) {
            $has_pwcol = true;
        } else {
            // try common alternative names
            $alt = null;
            $res2 = @mysqli_query($conn, "SHOW COLUMNS FROM superadmins LIKE 'password'");
            if ($res2 && mysqli_num_rows($res2) > 0) $alt = 'password';
            else {
                $res3 = @mysqli_query($conn, "SHOW COLUMNS FROM superadmins LIKE 'pass'");
                if ($res3 && mysqli_num_rows($res3) > 0) $alt = 'pass';
            }
            if ($alt !== null) {
                $pwcol = $alt;
                $has_pwcol = true;
            } else {
                // Add password_hash column if it doesn't exist
                @mysqli_query($conn, "ALTER TABLE superadmins ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '");
                // If ALTER failed or added, continue with password_hash
                $pwcol = 'password_hash';
                $has_pwcol = true;
            }
        }

        // Prepare statement using the discovered column name
        $sql = "SELECT id, `" . $pwcol . "` FROM superadmins WHERE username = ? LIMIT 1";
        $stmt = @mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $u);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $id, $hash);
            if (mysqli_stmt_fetch($stmt)) {
                if ($hash !== null && $hash !== '' && password_verify($p, $hash)) {
                    $authenticated = true;
                    $_SESSION['superadmin'] = $u;
                }
            }
            mysqli_stmt_close($stmt);
        }
    }

    if ($authenticated) {
        // log activity
        if (isset($conn)) {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $safe_user = mysqli_real_escape_string($conn, $u);
            $insert = "INSERT INTO activity_logs (username, action, device, created_at) VALUES ('$safe_user','superadmin_login','" . mysqli_real_escape_string($conn, $ua) . "', NOW())";
            @mysqli_query($conn, $insert);
        }
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Superadmin Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="center-box">
    <h2>Superadmin Login</h2>
    <?php if($error): ?><div class="err" style="color:#b00"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post" action="">
        <div class="form-row">
            <label>Username</label>
            <input name="username" required>
        </div>
        <div class="form-row">
            <label>Password</label>
            <input name="password" type="password" required>
        </div>
        <div class="form-row"><button class="btn" type="submit">Login</button></div>
    </form>
</div>
</body>
</html>
