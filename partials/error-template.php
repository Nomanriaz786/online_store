<?php
/**
 * Reusable error page template
 * Usage: Set variables before including this file:
 * - $errorCode (required): The error code (404, 500, etc.)
 * - $errorTitle (required): The error title
 * - $errorMessage (required): The error message
 * - $errorIcon (optional): Bootstrap icon class (default: bi-exclamation-triangle)
 * - $errorClass (optional): Additional CSS class for styling (default: empty)
 * - $showRetryButton (optional): Whether to show retry button (default: false)
 */

// Set defaults
$errorIcon = $errorIcon ?? 'bi-exclamation-triangle';
$errorClass = $errorClass ?? '';
$showRetryButton = $showRetryButton ?? false;
?>

<main>
    <div class="error-page <?php echo htmlspecialchars($errorClass); ?>">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-6 text-center">
                    <div class="error-content position-relative <?php echo $errorClass === 'error-500' ? 'pulse-error' : ''; ?>">
                        <!-- Floating Background Elements -->
                        <div class="floating-elements">
                            <div class="floating-element"></div>
                            <div class="floating-element"></div>
                            <div class="floating-element"></div>
                        </div>
                        
                        <!-- Error Icon -->
                        <i class="<?php echo htmlspecialchars($errorIcon); ?> error-icon"></i>
                        
                        <!-- Main Content -->
                        <div class="position-relative">
                            <h1 class="<?php echo $errorClass === 'error-500' ? 'text-gradient-error' : 'text-gradient'; ?>">
                                <?php echo htmlspecialchars($errorCode); ?>
                            </h1>
                            <h2 class="h3 mb-3"><?php echo htmlspecialchars($errorTitle); ?></h2>
                            <p class="mb-4"><?php echo htmlspecialchars($errorMessage); ?></p>
                            
                            <div class="error-actions d-flex justify-content-center">
                                <a href="<?php echo Config::BASE_URL; ?>" class="btn btn-primary">
                                    <i class="bi bi-house-door me-2"></i>
                                    Back to Home
                                </a>
                                
                                <?php if ($showRetryButton): ?>
                                    <a href="javascript:location.reload()" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-2"></i>
                                        Try Again
                                    </a>
                                <?php else: ?>
                                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        Go Back
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($additionalHelp)): ?>
                                <div class="mt-4 pt-3 border-top border-light">
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($additionalHelp); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>