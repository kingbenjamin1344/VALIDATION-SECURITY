<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only upper_management can access
if (!isset($_SESSION['username'])) {
  header('Location: ../logform/login.php');
  exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'upper_management') {
  if ($role === 'superadmin') header('Location: ../superadmin/dashboard.php');
  elseif ($role === 'admin') header('Location: ../admin/dashboard.php');
  else header('Location: ../logform/indexes.php');
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['role'])) {
    $uid = (int)$_POST['user_id'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Update users.role
    mysqli_query($conn, "UPDATE users SET role='{$role}' WHERE id={$uid}");

    // Remove user from role tables, then insert into the chosen role table
    mysqli_query($conn, "DELETE FROM upper_management WHERE user_id={$uid}");
    mysqli_query($conn, "DELETE FROM superadmin WHERE user_id={$uid}");
    mysqli_query($conn, "DELETE FROM admin WHERE user_id={$uid}");

    if ($role === 'upper_management') {
        mysqli_query($conn, "INSERT INTO upper_management (user_id) VALUES ({$uid})");
    } elseif ($role === 'superadmin') {
        mysqli_query($conn, "INSERT INTO superadmin (user_id) VALUES ({$uid})");
    } elseif ($role === 'admin') {
        mysqli_query($conn, "INSERT INTO admin (user_id) VALUES ({$uid})");
    }

    header('Location: addrole.php?updated=1');
    exit;
}

$users = mysqli_query($conn, "SELECT id, firstname, lastname, email, username, role FROM users ORDER BY id DESC");
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Manage Roles</title></head>
<body>
<h1>Manage User Roles</h1>
<?php if (isset($_GET['updated'])): ?>
<p style="color:green">Role updated.</p>
<?php endif; ?>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Action</th></tr>
<?php while ($row = mysqli_fetch_assoc($users)): ?>
<tr>
  <td><?= htmlspecialchars($row['id']) ?></td>
  <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
  <td><?= htmlspecialchars($row['email']) ?></td>
  <td><?= htmlspecialchars($row['username']) ?></td>
  <td><?= htmlspecialchars($row['role']) ?></td>
  <td>
    <form method="post" style="display:inline">
      <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
      <select name="role">
        <option value="user" <?= $row['role']==='user'?'selected':'' ?>>user</option>
        <option value="admin" <?= $row['role']==='admin'?'selected':'' ?>>admin</option>
        <option value="superadmin" <?= $row['role']==='superadmin'?'selected':'' ?>>superadmin</option>
        <option value="upper_management" <?= $row['role']==='upper_management'?'selected':'' ?>>upper_management</option>
      </select>
      <button type="submit">Set Role</button>
    </form>
  </td>
</tr>
<?php endwhile; ?>
</table>
<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
