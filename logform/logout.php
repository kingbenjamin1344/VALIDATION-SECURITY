<?php
session_start();
require_once __DIR__ . '/../regform/config.php';
// log user logout
if (isset($_SESSION['username'])) {
	$user = $_SESSION['username'];
	$device = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	if ($user !== '' && function_exists('insert_activity')) insert_activity($conn, $user, 'user', 'logout', $device);
}
session_destroy();
header('Location: login.php');
exit();
?>
?>