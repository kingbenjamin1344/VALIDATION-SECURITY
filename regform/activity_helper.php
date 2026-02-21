<?php
// Helper to record user activity (login/logout)
function record_activity($conn, $username, $action) {
    if (empty($username) || empty($action)) return false;

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

    $ins = $conn->prepare("INSERT INTO activity_log (username, action, device_name, ip, created_at) VALUES (?, ?, ?, ?, NOW())");
    if ($ins) {
        $ins->bind_param('ssss', $username, $action, $device, $ip);
        $ins->execute();
        $ins->close();
        return true;
    }

    return false;
}

?>
