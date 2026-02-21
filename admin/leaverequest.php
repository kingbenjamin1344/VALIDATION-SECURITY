<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

if (!isset($_SESSION['username']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../logform/login.php');
    exit();
}

// Handle approve/decline
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'declined';
    $ustmt = $conn->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    if ($ustmt) {
        $ustmt->bind_param('si', $action, $id);
        $ustmt->execute();
        $ustmt->close();
    }
}

// Fetch all requests
$requests = [];
try {
    $middlenameField = "";
    $r = $conn->query("SELECT lr.id, lr.username, lr.leave_type, lr.start_date, lr.end_date, lr.reason, lr.status, lr.created_at, u.firstname, u.middlename, u.lastname FROM leave_requests lr LEFT JOIN users u ON lr.username = u.username ORDER BY lr.created_at DESC");
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $requests[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    $fetchError = 'leave_requests table not found. Create table to enable this view.';
    $schemaSql = "CREATE TABLE IF NOT EXISTS leave_requests (\n  id INT AUTO_INCREMENT PRIMARY KEY,\n  username VARCHAR(100) NOT NULL,\n  leave_type VARCHAR(50) NOT NULL,\n  start_date DATE NOT NULL,\n  end_date DATE DEFAULT NULL,\n  reason TEXT,\n  status ENUM('pending','approved','declined') DEFAULT 'pending',\n  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin - Leave Requests</title>
    <link rel="stylesheet" href="../css/main.index.css">
    <style>
        table{width:100%;border-collapse:collapse}
        th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
        .btn-approve{background:#28a745;color:#fff;padding:6px 10px;border:none;border-radius:4px}
        .btn-decline{background:#dc3545;color:#fff;padding:6px 10px;border:none;border-radius:4px}
    </style>
</head>
<body>
    <div style="padding:20px;max-width:1100px;margin:0 auto;">
        <h1>Pending Request</h1>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Type</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="8">No requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(($req['firstname'] ? $req['firstname'] . ' ' . $req['lastname'] : $req['username'])); ?></td>
                            <td><?php echo htmlspecialchars(($req['firstname'] ? trim($req['firstname'] . ' ' . ($req['middlename'] ? $req['middlename'] . ' ' : '') . $req['lastname']) : $req['username'])); ?></td>
                            <td><?php echo htmlspecialchars($req['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($req['start_date']); ?></td>
                            <td><?php echo htmlspecialchars($req['end_date']); ?></td>
                            <td><?php echo htmlspecialchars($req['reason']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($req['status'])); ?></td>
                            <td><?php echo htmlspecialchars($req['created_at']); ?></td>
                            <td>
                                <?php if ($req['status'] === 'pending'): ?>
                                    <form method="post" style="display:inline">
                                        <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                                        <button name="action" value="approve" class="btn-approve">Approve</button>
                                    </form>
                                    <form method="post" style="display:inline;margin-left:6px;">
                                        <input type="hidden" name="id" value="<?php echo (int)$req['id']; ?>">
                                        <button name="action" value="decline" class="btn-decline">Decline</button>
                                    </form>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>