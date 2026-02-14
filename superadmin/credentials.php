<?php
// Default credentials used only to seed the database when there are
// no superadmin accounts yet. Change the plain password immediately.
// These values are NOT used as runtime authentication after seeding.
define('DEFAULT_SUPERADMIN_USER', 'superadmin');
define('DEFAULT_SUPERADMIN_PASSWORD', 'ChangeMe123!');

// For production, replace this seeding mechanism with secure onboarding
// and remove or override these values via environment variables.
?>
