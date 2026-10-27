<?php
/**
 * Application Settings & Constants
 * Veterinary Care for Animal Medical Solution
 */

// Application Information
define('APP_NAME', 'Veterinary Care for Animal Medical Solution');
define('APP_SHORT_NAME', 'VetCare Solutions');
define('APP_TAGLINE', 'Compassionate Care for Every Animal');
define('APP_VERSION', '1.0.0');

// Contact Information
define('CLINIC_PHONE', '+1 (555) 234-8387');
define('CLINIC_EMAIL', 'support@veterinarycare.local');
define('CLINIC_ADDRESS', '104 Healthcare Boulevard, Animal Medical Center');
define('EMERGENCY_PHONE', '+1 (555) 911-PETS');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Base Paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'animals');


// Dynamic Base URL detection
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $subDir = '';

    if (!empty($_SERVER['SCRIPT_NAME']) && !empty($_SERVER['SCRIPT_FILENAME'])) {
        // Normalize slashes
        $scriptName     = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);      // e.g. /veterinary/auth/login.php
        $scriptFilename = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);  // e.g. D:/veterinary/auth/login.php
        $rootPath       = str_replace('\\', '/', ROOT_PATH);                   // e.g. D:/veterinary

        // Strip the PHP file's relative path from SCRIPT_NAME to get the web sub-directory
        $relativeFile = str_replace($rootPath, '', $scriptFilename); // e.g. /auth/login.php
        if ($relativeFile !== '' && substr($scriptName, -strlen($relativeFile)) === $relativeFile) {
            $subDir = substr($scriptName, 0, strlen($scriptName) - strlen($relativeFile));
            $subDir = rtrim($subDir, '/');
        }
    }

    // Final fallback: try filesystem DOCUMENT_ROOT approach
    if ($subDir === '' && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot    = str_replace(['\\', '/'], '/', $_SERVER['DOCUMENT_ROOT']);
        $currentDir = str_replace(['\\', '/'], '/', ROOT_PATH);
        if (!empty($docRoot) && strpos($currentDir, $docRoot) === 0) {
            $subDir = rtrim(substr($currentDir, strlen($docRoot)), '/');
        }
    }

    define('BASE_URL', rtrim($protocol . $host . $subDir, '/'));
}


// Upload URL
define('UPLOAD_URL', BASE_URL . '/uploads/animals');

// Upload Restrictions
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);

// Supported Animal Species
const ANIMAL_SPECIES = [
    'Dog' => 'Dog (Canine)',
    'Cat' => 'Cat (Feline)',
    'Cow' => 'Cow (Bovine)',
    'Goat' => 'Goat (Caprine)',
    'Sheep' => 'Sheep (Ovine)',
    'Buffalo' => 'Buffalo (Bubaline)',
    'Horse' => 'Horse (Equine)',
    'Poultry' => 'Poultry (Avian)',
    'Other' => 'Other Animal'
];

// Appointment Statuses
const APPOINTMENT_STATUSES = [
    'pending' => 'Pending Confirmation',
    'scheduled' => 'Scheduled & Confirmed',
    'completed' => 'Consultation Completed',
    'cancelled' => 'Cancelled',
    'rejected' => 'Rejected'
];

// Roles
const ROLE_OWNER = 'owner';
const ROLE_VET = 'veterinarian';
const ROLE_ADMIN = 'admin';
