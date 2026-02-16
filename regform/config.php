<?php
$conn = mysqli_connect('localhost','root','','it107_security_sql');

// Helper to insert activity logs safely. Tries to insert with `usertype` column
// and falls back to a schema without that column if necessary.
function insert_activity($conn, $username, $usertype, $action, $device = '') {
	$username = mysqli_real_escape_string($conn, (string)$username);
	$usertype = mysqli_real_escape_string($conn, (string)$usertype);
	$action = mysqli_real_escape_string($conn, (string)$action);
	$device = mysqli_real_escape_string($conn, (string)$device);

	// detect if `usertype` column exists (cached)
	static $has_usertype = null;
	if ($has_usertype === null) {
		$has_usertype = false;
		try {
			$res = mysqli_query($conn, "SHOW COLUMNS FROM activity_logs LIKE 'usertype'");
			if ($res && mysqli_num_rows($res) > 0) $has_usertype = true;
		} catch (Exception $e) {
			// table or column might not exist; treat as no-usertype
			$has_usertype = false;
		}
	}

	if ($has_usertype) {
		$q = "INSERT INTO activity_logs (username, usertype, action, device) VALUES ('{$username}','{$usertype}','{$action}','{$device}')";
	} else {
		$q = "INSERT INTO activity_logs (username, action, device) VALUES ('{$username}','{$action}','{$device}')";
	}

	try {
		return (bool) mysqli_query($conn, $q);
	} catch (Exception $e) {
		return false;
	}
}

?>