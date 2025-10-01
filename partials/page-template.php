<?php
/**
 * Universal page layout template
 * 
 * This template provides a complete page structure that any page can use.
 * Just set the required variables before including this file:
 * 
 * Required variables:
 * - $pageTitle: The page title
 * - $pageContent: The main content (HTML string or file path to include)
 * 
 * Optional variables:
 * - $customHead: Additional head content (CSS, meta tags, etc.)
 * - $customScripts: Additional JavaScript
 * - $bodyClass: CSS class for the body tag
 * - $showHeader: Whether to show header (default: true)
 * - $showFooter: Whether to show footer (default: true)
 */

// Set defaults
$showHeader = $showHeader ?? true;
$showFooter = $showFooter ?? true;
$bodyClass = $bodyClass ?? '';

// Initialize page
require_once 'partials/page-init.php';

// Include HTML head
include 'partials/html-head.php';
?>

<?php if ($bodyClass): ?>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
<?php endif; ?>

<?php if ($showHeader): ?>
  <!-- Header -->
  <?php include 'partials/header.php'; ?>
<?php endif; ?>

  <!-- Main Content -->
  <?php 
  if (isset($pageContent)) {
    if (is_file($pageContent)) {
      // If $pageContent is a file path, include it
      include $pageContent;
    } else {
      // If $pageContent is a string, echo it
      echo $pageContent;
    }
  }
  ?>

<?php if ($showFooter): ?>
  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>
<?php endif; ?>

<?php include 'partials/html-footer.php'; ?>