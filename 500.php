<?php
// Page configuration
$pageTitle = "Server Error — My Store";
$errorCode = "500";
$errorTitle = "Server Having a Bad Day";
$errorMessage = "Our servers are experiencing some technical difficulties. Our engineering team has been notified and is working to resolve this issue.";
$errorIcon = "bi bi-server";
$errorClass = "error-500";
$showRetryButton = true;
$additionalHelp = "If this problem persists, please contact our support team.";

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