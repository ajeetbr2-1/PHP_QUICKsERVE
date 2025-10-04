<?php
/**
 * QuickServe - Logout
 */

require_once 'config/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit();
?>
