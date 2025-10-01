<?php
// HTML head template - common for all pages
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="All group members">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'My Store'; ?></title>
  
  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <?php
  // Allow pages to add custom head content
  if (isset($customHead)) {
    echo $customHead;
  }
  
  // Inject authentication state for JavaScript
  if (!class_exists('Auth')) {
    require_once __DIR__ . '/../classes/Auth.php';
  }
  $auth = new Auth();
  $currentUser = $auth->getCurrentUser();
  $isAuthenticated = $auth->isAuthenticated();
  ?>
  
  <script>
    // Set initial authentication state for JavaScript
    window.SERVER_AUTH_STATE = {
      isAuthenticated: <?php echo $isAuthenticated ? 'true' : 'false'; ?>,
      user: <?php echo $isAuthenticated && $currentUser ? json_encode($currentUser) : 'null'; ?>
    };
  </script>
</head>
<body>