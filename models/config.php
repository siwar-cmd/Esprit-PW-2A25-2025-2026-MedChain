<?php
/**
 * Configuration file
 * Contains database connection parameters and other settings
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '2a25');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application settings
define('APP_NAME', 'MedChain');
define('APP_ENV', 'development'); // Change to 'production' in production

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
