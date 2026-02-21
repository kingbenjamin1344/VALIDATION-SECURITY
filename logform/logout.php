<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Record logout activity when possible
$username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
if ($username) {
	@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS activity_log (
		id INT AUTO_INCREMENT PRIMARY KEY,
		username VARCHAR(100) NOT NULL,
		action VARCHAR(20) NOT NULL,
		device_name TEXT,
		ip VARCHAR(45),
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	$device = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
	$ins = $conn->prepare("INSERT INTO activity_log (username, action, device_name, ip, created_at) VALUES (?, 'logout', ?, ?, NOW())");
	if ($ins) {
		$ins->bind_param('sss', $username, $device, $ip);
		$ins->execute();
		$ins->close();
	}
}

session_destroy();
header('Location: login.php');
exit();
?>