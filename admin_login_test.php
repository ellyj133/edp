<?php
/**
 * Simple admin login for testing
 */
require_once 'includes/init.php';

// Login as admin user 
Session::start();
Session::set('user_id', 1);
Session::set('username', 'admin');
Session::set('role', 'admin');
Session::set('logged_in', true);

echo "Logged in as admin (User ID: 1)\n";
echo "Session data:\n";
print_r($_SESSION);
?>