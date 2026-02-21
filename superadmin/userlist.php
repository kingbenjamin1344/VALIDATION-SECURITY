<?php
session_start();
require_once __DIR__ . '/../regform/config.php';

// Access control: only superadmin can access
if (!isset($_SESSION['username'])) {
  header('Location: ../logform/login.php');
  exit();
}
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
if ($role !== 'superadmin') {
  if ($role === 'admin') header('Location: ../admin/dashboard.php');
  elseif ($role === 'upper_management') header('Location: ../upper_management/dashboard.php');
  else header('Location: ../logform/indexes.php');
  exit();
}

// Admins
$admins = mysqli_query($conn, "SELECT id, firstname, lastname, email, username FROM users WHERE role='admin' ORDER BY id DESC");
// Users (including admins)
$users = mysqli_query($conn, "SELECT id, firstname, lastname, email, username, role FROM users WHERE role IN ('user','admin','superadmin') ORDER BY id DESC");
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>User List</title></head>
<body>
<h1>Superadmin - User Lists</h1>
<h2>Admins</h2>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th></tr>
<?php while ($r = mysqli_fetch_assoc($admins)): ?>
<tr>
  <td><?= htmlspecialchars($r['id']) ?></td>
  <td><?= htmlspecialchars($r['firstname'].' '.$r['lastname']) ?></td>
  <td><?= htmlspecialchars($r['email']) ?></td>
  <td><?= htmlspecialchars($r['username']) ?></td>
</tr>
<?php endwhile; ?>
</table>

<h2>All Users (including admins)</h2>
<table border="1" cellpadding="6" cellspacing="0">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th></tr>
<?php while ($r = mysqli_fetch_assoc($users)): ?>
<tr>
  <td><?= htmlspecialchars($r['id']) ?></td>
  <td><?= htmlspecialchars($r['firstname'].' '.$r['lastname']) ?></td>
  <td><?= htmlspecialchars($r['email']) ?></td>
  <td><?= htmlspecialchars($r['username']) ?></td>
  <td><?= htmlspecialchars($r['role']) ?></td>
</tr>
<?php endwhile; ?>
</table>
<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>
