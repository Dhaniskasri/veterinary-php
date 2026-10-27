<?php
/**
 * Database Configuration & Connection Provider
 * Veterinary Care for Animal Medical Solution
 */

// Database credentials - customize if needed
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_NAME')) define('DB_NAME', 'veterinary_care');
if (!defined('DB_USER')) define('DB_USER', 'root');
// Fallback array for local XAMPP environments (some use 'root', some use '')
if (!defined('DB_PASS')) define('DB_PASS', 'root');

/**
 * Returns a shared PDO instance with prepared statement configuration.
 * Automatically handles standard XAMPP passwords ('root' or empty '').
 *
 * @return PDO
 * @throws PDOException
 */
function get_db_connection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    $passwordsToTry = [DB_PASS];
    if (DB_PASS !== '') {
        $passwordsToTry[] = '';
    }
    if (DB_PASS !== 'root') {
        $passwordsToTry[] = 'root';
    }

    $lastException = null;
    foreach ($passwordsToTry as $pwd) {
        try {
            $pdo = new PDO($dsn, DB_USER, $pwd, $options);
            return $pdo;
        } catch (PDOException $e) {
            $lastException = $e;
        }
    }

    // If connection failed with all password options, throw the exception
    throw new PDOException(
        "Database connection failed: Could not connect to MySQL server at " . DB_HOST . ":" . DB_PORT .
        " for database '" . DB_NAME . "'. Error: " . ($lastException ? $lastException->getMessage() : 'Unknown error')
    );
}
