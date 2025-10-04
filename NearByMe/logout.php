<?php
/**
 * Near By Me - Logout
 */

require_once 'config/auth.php';

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit();
?>
