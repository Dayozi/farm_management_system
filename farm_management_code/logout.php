<?php
// require_once here to get access to the logout function
require_once __DIR__ . '/includes/auth.php';

// Call the logout function from auth.php
logout();

// Redirect the user to the home page after logging out
header('Location: index.php');
exit;

?>
