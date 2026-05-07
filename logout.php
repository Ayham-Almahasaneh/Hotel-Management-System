<?php
declare(strict_types=1);

// Start session to access session variables
session_start();

// Remove all session variables
session_unset();

// Destroy the session completely
session_destroy();

// Redirect user to login page after logout
header('Location: login.php');
exit;