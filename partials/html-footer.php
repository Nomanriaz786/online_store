<?php
// HTML footer scripts - common for all pages
?>
  
  <!-- JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="assets/js/app-refactored.js"></script>
  
  <?php
  // Allow pages to add custom JavaScript (for meta tags, etc.)
  if (isset($customScripts)) {
    echo $customScripts;
  }
  ?>
</body>
</html>