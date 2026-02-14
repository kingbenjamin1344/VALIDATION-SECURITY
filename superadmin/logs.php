<?php
session_start();
if (!isset($_SESSION['superadmin'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Activity Logs</title></head><body>
<h2>Activity Logs (empty)</h2>
<p>Placeholder page — implement later.</p>
</body></html>
