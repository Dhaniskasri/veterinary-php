<?php
/**
 * User Logout Action
 * Veterinary Care for Animal Medical Solution
 */
require_once __DIR__ . '/../includes/init.php';

// Support both POST with CSRF and fallback GET with confirmation for user ease
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf_or_die();
    $userId = current_user()['id'] ?? null;
    if ($userId) {
        log_activity($pdo, $userId, 'USER_LOGOUT', 'user', $userId);
    }
    logout_user();
    redirect('auth/login.php', 'You have been logged out securely.', 'info');
} else {
    // If accessed via GET, perform safe logout
    logout_user();
    redirect('auth/login.php', 'You have been logged out securely.', 'info');
}
