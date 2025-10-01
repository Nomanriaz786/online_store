<?php
// Page configuration
$pageTitle = "Page Not Found — My Store";
$errorCode = "404";
$errorTitle = "Oops! Page Not Found";
$errorMessage = "The page you're looking for seems to have wandered off into the digital void. Don't worry, it happens to the best of us!";
$errorIcon = "bi bi-exclamation-triangle";

// Initialize page
require_once 'partials/page-init.php';

// Include HTML head
include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <!-- Error Content -->
  <?php include 'partials/error-template.php'; ?>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>