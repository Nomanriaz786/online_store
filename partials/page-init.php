<?php
/**
 * Common initialization for all pages
 * Include this at the top of every page to set up:
 * - Configuration
 * - Authentication
 * - Common variables
 */

// Include necessary dependencies
require_once 'config/config.php';
require_once 'classes/Auth.php';

// Initialize auth
$auth = new Auth();

// Set default page title if not already set
if (!isset($pageTitle)) {
    $pageTitle = 'My Store';
}
?>