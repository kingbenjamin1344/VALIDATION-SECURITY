<?php
session_start();
require_once __DIR__ . '/../regform/config.php';
// log superadmin logout
if (isset($_SESSION['superadmin'])) {
	$user = $_SESSION['superadmin'];
	$device = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	if ($user !== '' && function_exists('insert_activity')) insert_activity($conn, $user, 'superadmin', 'logout', $device);
}
session_unset();
session_destroy();
header('Location: login.php');
exit;
