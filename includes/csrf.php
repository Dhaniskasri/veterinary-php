<?php
/**
 * CSRF Protection Utilities
 * Veterinary Care for Animal Medical Solution
 */

/**
 * Returns current CSRF token from session, or generates a new one.
 *
 * @return string
 */
function csrf_token(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders a hidden CSRF token input field.
 *
 * @return string
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifies the submitted CSRF token against the session token.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validates CSRF token from POST request. If invalid, terminates with a 403 error.
 *
 * @return void
 */
function validate_csrf_or_die(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!verify_csrf_token($token)) {
            http_response_code(403);
            die('
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <title>403 - Invalid Security Token</title>
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
            </head>
            <body class="bg-light d-flex align-items-center justify-content-center vh-100">
                <div class="card shadow-sm p-4 text-center" style="max-width: 480px;">
                    <div class="text-danger mb-3"><i class="bi bi-shield-x display-3"></i></div>
                    <h3 class="fw-bold text-dark">Session Expired or Invalid Request</h3>
                    <p class="text-muted">Security token validation failed. Please refresh the page and try submitting your form again.</p>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
                </div>
            </body>
            </html>
            ');
        }
    }
}
