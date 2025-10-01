<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

$auth = new Auth();

// Perform logout
$result = $auth->logout();

// Set success message
session_start();
$_SESSION['success_message'] = $result['message'];

// Redirect to homepage
header('Location: index.php');
exit();
?>