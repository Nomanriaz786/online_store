<?php
$pageTitle = "About — My Demo Store";

// Use the template system
include 'partials/html-head.php';
?>

  <!-- Header -->
  <?php include 'partials/header.php'; ?>

  <main class="container pt-6">
    <div class="row justify-content-center">
      <div class="col-lg-12 py-5">
        <div class="text-center mb-5">
          <h1 class="display-4 fw-bold mb-3">About This Project</h1>
          <p class="lead text-muted">ICT930 Advanced Web Development - Semester 2 2025</p>
          <p class="text-muted">A comprehensive e-commerce prototype demonstrating modern web development practices</p>
        </div>

        <section class="mb-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 fw-bold mb-4 text-primary">
                <i class="bi bi-people-fill me-2"></i>Work Distribution & Roles
              </h2>
              
              <p class="mb-3">
                <strong>Abdul Manan</strong> serves as the Backend Lead responsible for User Management & Authentication. His primary tasks include implementing secure user registration and login systems (register.php, login.php), robust session management with PHP, secure password hashing using password_hash() and password_verify(), server-side validation for all user inputs, and developing the user dashboard with dynamic content from MySQL database. Abdul also handles the integration of login/register functionality with the frontend landing page and ensures proper role differentiation between regular users and administrators.
              </p>
              
              <p class="mb-3">
                <strong>Siffat Ullah</strong> specializes in Product Management & Admin Interface and Database Design. His responsibilities include developing the complete admin.php dashboard with full CRUD operations for products, implementing secure image upload handling with server-side validation, creating the MySQL database schema with proper normalization and relationships, designing tables for Users, Products, Categories, Orders, and Order_Items with appropriate primary/foreign keys, and ensuring optimal database performance through efficient query design and indexing strategies.
              </p>
              
              <p class="mb-0">
                <strong>Saad Haider</strong> focuses on Dynamic Content & Product Display, Shopping Cart & Checkout Process, and Security Implementation. His tasks include developing dynamic product display systems for index.php and products.php, implementing functional search, filter, and sort capabilities with backend integration, creating persistent shopping cart functionality using PHP sessions or database storage, building the complete checkout process (checkout.php) with order processing, and implementing comprehensive security measures including SQL injection prevention through prepared statements, XSS protection with htmlspecialchars(), and secure access control for all sensitive operations.
              </p>
            </div>
          </div>
        </section>

        <section class="mb-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 fw-bold mb-4 text-success">
                <i class="bi bi-star-fill me-2"></i>Assessment 3 Deliverables
              </h2>
              
              <p class="mb-3">
                <strong>Backend Infrastructure Development:</strong> Our implementation focuses on transitioning from static frontend prototypes to fully functional server-side applications. The core achievement is developing robust PHP-based backend systems that integrate seamlessly with MySQL databases, providing persistent data storage for users, products, and orders. We have implemented comprehensive user management with secure authentication, session handling, and role-based access control that differentiates between regular users and administrators.
              </p>
              
              <p class="mb-3">
                <strong>Database Integration & Security:</strong> The project demonstrates advanced database design principles with a fully normalized MySQL schema supporting Users, Products, Categories, Orders, and Order_Items tables with proper primary/foreign key relationships. Security implementation includes password hashing using PHP's built-in functions, prepared statements for all database queries to prevent SQL injection, input validation and sanitization using htmlspecialchars() for XSS protection, and secure session management preventing session hijacking and unauthorized access.
              </p>
              
              <p class="mb-0">
                <strong>Dynamic Content & E-commerce Functionality:</strong> All frontend elements now connect to backend databases, enabling dynamic product display, functional search and filtering systems, persistent shopping cart functionality, and complete order processing workflows. The admin dashboard provides full CRUD operations for product management with secure image upload capabilities, while the checkout process stores comprehensive order details and billing information in the database with proper validation and error handling.
              </p>
            </div>
          </div>
        </section>

        <section class="mb-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 fw-bold mb-4 text-warning">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>Backend Development Challenges
              </h2>
              
              <p class="mb-3">
                <strong>Database Integration & Performance:</strong> The transition from static frontend to dynamic backend required careful architectural planning to ensure optimal database performance while maintaining security. Implementing proper MySQL schema design with normalized tables and efficient relationships posed challenges in balancing data integrity with query performance. We had to develop strategies for handling concurrent user sessions, preventing race conditions in cart operations, and ensuring database consistency during order processing while maintaining fast response times for product searches and filtering operations.
              </p>
              
              <p class="mb-3">
                <strong>Security Implementation:</strong> Achieving comprehensive security coverage across all user inputs and database interactions required meticulous attention to detail. The challenge was implementing robust protection against SQL injection through consistent use of prepared statements, preventing XSS attacks with proper output escaping, and ensuring secure password management with appropriate hashing algorithms. Additionally, managing session security to prevent hijacking while maintaining user experience across different pages and ensuring proper access control for administrative functions required careful balance between security and usability.
              </p>
              
              <p class="mb-0">
                <strong>System Integration & Testing:</strong> Connecting the existing frontend components to new backend functionality while maintaining the original design and user experience presented significant integration challenges. Ensuring that all AJAX calls, form submissions, and dynamic content loading worked seamlessly with PHP scripts required extensive testing across different scenarios. The complexity increased when implementing error handling, validation feedback, and ensuring graceful degradation when backend services encounter issues, all while maintaining the responsive design and cross-browser compatibility established in the frontend.
              </p>
            </div>
          </div>
        </section>

        <section class="mb-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 fw-bold mb-4 text-info">
                <i class="bi bi-image-fill me-2"></i>Technical Stack & Implementation
              </h2>
              
              <div class="row">
                <div class="col-md-6 mb-3">
                  <h5 class="fw-semibold mb-2">Backend Technologies</h5>
                  <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>PHP 8.x (OOP & Procedural)</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>MySQL Database</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>PDO/MySQLi with Prepared Statements</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>PHP Sessions for State Management</li>
                  </ul>
                </div>
                <div class="col-md-6 mb-3">
                  <h5 class="fw-semibold mb-2">Security Features</h5>
                  <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Password Hashing (password_hash())</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>SQL Injection Prevention</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>XSS Protection (htmlspecialchars())</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Secure File Upload Validation</li>
                  </ul>
                </div>
              </div>
              
              <div class="row mt-3">
                <div class="col-md-6">
                  <h5 class="fw-semibold mb-2">Database Schema</h5>
                  <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Users Table (Authentication & Profiles)</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Products Table (Inventory Management)</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Categories Table (Product Classification)</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Orders & Order_Items Tables</li>
                  </ul>
                </div>
                <div class="col-md-6">
                  <h5 class="fw-semibold mb-2">Development Environment</h5>
                  <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>XAMPP (Apache + MySQL + PHP)</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>phpMyAdmin for Database Management</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Backend Testing & Validation</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Deployment Documentation</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="mb-5">
          <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 fw-bold mb-4 text-primary">
                <i class="bi bi-mortarboard-fill me-2"></i>Development Team
              </h2>
              
              <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                  <div class="d-flex align-items-center p-3 bg-body-secondary rounded">
                    <div class="bg-primary bg-opacity-10 btn-circle p-2 me-3 d-flex align-items-center justify-content-center">
                      <i class="bi bi-person-fill text-primary"></i>
                    </div>
                    <div>
                      <h6 class="fw-semibold mb-0">Abdul Manan</h6>
                      <small class="text-muted">Backend Lead - User Management & Authentication</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-lg-4">
                  <div class="d-flex align-items-center p-3 bg-body-secondary rounded">
                    <div class="bg-success bg-opacity-10  btn-circle p-2 me-3 d-flex align-items-center justify-content-center">
                      <i class="bi bi-person-fill text-success"></i>
                    </div>
                    <div>
                      <h6 class="fw-semibold mb-0">Siffat Ullah</h6>
                      <small class="text-muted">Database Architect - Product Management & Schema Design</small>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 col-lg-4">
                  <div class="d-flex align-items-center p-3 bg-body-secondary rounded">
                    <div class="bg-warning bg-opacity-10  btn-circle p-2 me-3 d-flex align-items-center justify-content-center">
                      <i class="bi bi-person-fill text-warning"></i>
                    </div>
                    <div>
                      <h6 class="fw-semibold mb-0">Saad Haider</h6>
                      <small class="text-muted">Security Specialist - Dynamic Content & E-commerce</small>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mt-4 p-3 bg-primary bg-opacity-10 rounded border border-primary border-opacity-25">
                <p class="mb-0 text-center">
                  <strong>Course:</strong> ICT930 Advanced Web Development | 
                  <strong>Assessment:</strong> 3 - Backend Development | 
                  <strong>Semester:</strong> 2, 2025
                </p>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include 'partials/footer.php'; ?>

<?php include 'partials/html-footer.php'; ?>