<?php
session_start();
require_once __DIR__ . '/../regform/config.php';
// log logout if admin session exists
if (isset($_SESSION['admin']) && is_array($_SESSION['admin'])) {
    $user = $_SESSION['admin']['username'] ?? '';
    $device = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    if ($user !== '' && function_exists('insert_activity')) insert_activity($conn, $user, 'admin', 'logout', $device);
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();
header('Location: login.php');
exit;
