<?php
/**
 * Authentication and Role-Based Access Control (RBAC)
 * Veterinary Care for Animal Medical Solution
 */

/**
 * Checks if a user is currently logged in.
 *
 * @return bool
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

/**
 * Returns current logged-in user array or null.
 *
 * @return array|null
 */
function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

/**
 * Returns current user's role.
 *
 * @return string|null
 */
function current_role(): ?string {
    return $_SESSION['user']['role'] ?? null;
}

/**
 * Ensures user is authenticated. Redirects to login if not.
 *
 * @param string|null $returnUrl
 * @return void
 */
function require_login(?string $returnUrl = null): void {
    if (!is_logged_in()) {
        $target = $returnUrl ?? $_SERVER['REQUEST_URI'] ?? '';
        if (!empty($target)) {
            $_SESSION['intended_url'] = $target;
        }
        redirect('auth/login.php', 'Please log in to access this page.', 'warning');
    }
}

/**
 * Enforces role-based authorization.
 *
 * @param string|array $allowedRoles
 * @return void
 */
function require_role($allowedRoles): void {
    require_login();

    $allowed = (array)$allowedRoles;
    $userRole = current_role();

    if (!in_array($userRole, $allowed, true)) {
        // Redirect to their own dashboard
        $destination = match ($userRole) {
            ROLE_OWNER => 'owner/dashboard.php',
            ROLE_VET   => 'veterinarian/dashboard.php',
            ROLE_ADMIN => 'admin/dashboard.php',
            default    => 'auth/login.php',
        };
        redirect($destination, 'Access denied: You do not have permission to view that resource.', 'danger');
    }
}

/**
 * Sets up session upon successful user authentication.
 *
 * @param array $user
 * @return void
 */
function login_user(array $user): void {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'        => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'phone'     => $user['phone'],
        'role'      => $user['role'],
        'status'    => $user['account_status']
    ];

    // Clear failed login attempts for this session
    unset($_SESSION['failed_logins'][$user['email']]);
}

/**
 * Destroys session and logs user out securely.
 *
 * @return void
 */
function logout_user(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Protects against brute-force login attempts with session-level throttling.
 *
 * @param string $email
 * @return bool True if allowed, False if throttled
 */
function check_login_throttle(string $email): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = strtolower(trim($email));
    if (!isset($_SESSION['failed_logins'][$key])) {
        return true;
    }

    $record = $_SESSION['failed_logins'][$key];
    $attempts = $record['attempts'] ?? 0;
    $lastAttempt = $record['time'] ?? 0;

    // 5 attempts allowed within 5 minutes
    if ($attempts >= 5 && (time() - $lastAttempt) < 300) {
        return false;
    }

    // Reset if timeout passed
    if ((time() - $lastAttempt) >= 300) {
        unset($_SESSION['failed_logins'][$key]);
    }

    return true;
}

/**
 * Records a failed login attempt.
 *
 * @param string $email
 * @return void
 */
function record_failed_login(string $email): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = strtolower(trim($email));
    if (!isset($_SESSION['failed_logins'][$key])) {
        $_SESSION['failed_logins'][$key] = [
            'attempts' => 1,
            'time' => time()
        ];
    } else {
        $_SESSION['failed_logins'][$key]['attempts']++;
        $_SESSION['failed_logins'][$key]['time'] = time();
    }
}

/**
 * IDOR Protection: Verifies if the user is authorized to view/manage an animal.
 *
 * @param PDO $pdo
 * @param int $animalId
 * @param int $userId
 * @param string $role
 * @return bool
 */
function can_access_animal(PDO $pdo, int $animalId, int $userId, string $role): bool {
    if ($role === ROLE_ADMIN) {
        return true;
    }

    if ($role === ROLE_OWNER) {
        $stmt = $pdo->prepare("SELECT id FROM animals WHERE id = ? AND owner_id = ?");
        $stmt->execute([$animalId, $userId]);
        return (bool)$stmt->fetch();
    }

    if ($role === ROLE_VET) {
        // Veterinarians can view animals if assigned to an appointment or medical record
        $stmt = $pdo->prepare("
            SELECT a.id FROM animals a
            LEFT JOIN appointments app ON app.animal_id = a.id AND app.veterinarian_id = ?
            LEFT JOIN medical_records mr ON mr.animal_id = a.id AND mr.veterinarian_id = ?
            WHERE a.id = ? AND (app.id IS NOT NULL OR mr.id IS NOT NULL)
        ");
        $stmt->execute([$userId, $userId, $animalId]);
        return (bool)$stmt->fetch();
    }

    return false;
}

/**
 * IDOR Protection: Verifies if the user is authorized to view an appointment.
 *
 * @param PDO $pdo
 * @param int $appointmentId
 * @param int $userId
 * @param string $role
 * @return bool
 */
function can_access_appointment(PDO $pdo, int $appointmentId, int $userId, string $role): bool {
    if ($role === ROLE_ADMIN) {
        return true;
    }

    $field = ($role === ROLE_OWNER) ? 'owner_id' : 'veterinarian_id';
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE id = ? AND $field = ?");
    $stmt->execute([$appointmentId, $userId]);
    return (bool)$stmt->fetch();
}
