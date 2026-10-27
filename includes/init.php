<?php
/**
 * Application Bootstrap & Initialization
 * Veterinary Care for Animal Medical Solution
 */

// Configure secure session cookies before starting session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    
    // Check if HTTPS is on
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Require Configuration and Helpers
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';

// Global Database Connection
try {
    $pdo = get_db_connection();
} catch (PDOException $e) {
    // Show friendly message if database is not reachable yet
    if (PHP_SAPI !== 'cli') {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Notice - <?= htmlspecialchars(APP_SHORT_NAME) ?></title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-4">
            <div class="card shadow border-0 rounded-4 p-4 text-center" style="max-width: 580px;">
                <div class="text-warning mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="bi bi-database-exclamation text-warning" viewBox="0 0 16 16">
                        <path d="M8 1c-1.573 0-3.022.289-4.096.777C2.823 2.26 2 2.99 2 3.864c0 .874.823 1.604 1.904 2.087C4.978 6.439 6.427 6.727 8 6.727c1.573 0 3.022-.288 4.096-.776C13.177 5.468 14 4.738 14 3.864c0-.874-.823-1.604-1.904-2.087C11.022 1.289 9.573 1 8 1"/>
                        <path d="M14 5.5v2.364c0 .874-.823 1.604-1.904 2.087C11.022 10.439 9.573 10.727 8 10.727c-1.573 0-3.022-.288-4.096-.776C2.823 9.468 2 8.738 2 7.864V5.5c0 .354.148.683.409.967.633.689 1.761 1.157 3.091 1.39A14 14 0 0 0 8 8c.854 0 1.67-.058 2.409-.166 1.33-.233 2.458-.701 3.091-1.39.26-.284.409-.613.409-.967"/>
                        <path d="M16 12.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0m-3.5-2a.5.5 0 0 0-.5.5v1.5a.5.5 0 0 0 1 0V11a.5.5 0 0 0-.5-.5m0 4a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                    </svg>
                </div>
                <h3 class="fw-bold text-dark mb-2">Database Setup Required</h3>
                <p class="text-secondary mb-4">The application cannot connect to the MySQL database. Please verify your MySQL server is running in XAMPP and that the database <code>veterinary_care</code> has been imported.</p>
                <div class="alert alert-secondary text-start small font-monospace mb-4">
                    <strong>Quick Setup Steps:</strong><br>
                    1. Start Apache & MySQL in XAMPP Control Panel<br>
                    2. Open <a href="http://localhost/phpmyadmin" target="_blank">phpMyAdmin</a><br>
                    3. Import <code>database/veterinary_care.sql</code><br>
                    4. Check <code>config/database.php</code> credentials
                </div>
                <button onclick="window.location.reload();" class="btn btn-primary rounded-pill px-4">Retry Connection</button>
            </div>
        </body>
        </html>
        <?php
        exit;
    } else {
        throw $e;
    }
}
