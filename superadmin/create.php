<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Create Admin</title></head><body>
<h2>Create Admin (empty)</h2>
<p>Placeholder page — implement later.</p>
</body></html>
