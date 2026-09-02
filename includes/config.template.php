<?php
// Copy this file to config.php and update the values according to your environment

// Check if configuration has already been loaded
if (!defined('DB_CONFIG_LOADED')) {
    define('DB_CONFIG_LOADED', true);

    // Detect environment based on server name
    $isLocalEnvironment = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);

    // Database configuration
    if ($isLocalEnvironment) {
        // Local development settings
        define('DB_HOST', 'localhost');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'portfolio_cms');
        define('ENVIRONMENT', 'development');
    } else {
        // Production server settings - UPDATE THESE VALUES
        define('DB_HOST', 'YOUR_SERVER_DB_HOST');        // Example: db.yourserver.com
        define('DB_USER', 'YOUR_SERVER_DB_USER');        // Example: portfolio_user
        define('DB_PASS', 'YOUR_SERVER_DB_PASSWORD');    // Example: strong_password_here
        define('DB_NAME', 'YOUR_SERVER_DB_NAME');        // Example: portfolio_db
        define('ENVIRONMENT', 'production');
    }

    // Error reporting based on environment
    if (ENVIRONMENT === 'development') {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    // Only create a new connection if one doesn't exist
    global $conn;
    if (!isset($conn) || !($conn instanceof mysqli)) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }

            // Create database if it doesn't exist (only in development)
            if (ENVIRONMENT === 'development') {
                $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
                $conn->query($sql);
            }
            
            // Select the database
            $conn->select_db(DB_NAME);

            // Set charset to ensure proper encoding
            $conn->set_charset("utf8mb4");

        } catch (Exception $e) {
            if (ENVIRONMENT === 'development') {
                die("Database Error: " . $e->getMessage());
            } else {
                die("A database error occurred. Please try again later.");
            }
        }
    }
}
?>
