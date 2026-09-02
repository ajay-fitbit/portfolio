<?php
// Check if configuration has already been loaded
if (!defined('DB_CONFIG_LOADED')) {
    define('DB_CONFIG_LOADED', true);

    // Detect environment based on server name
    //$isLocalEnvironment = in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1']);
    $isLocalEnvironment = true; // Force local environment for testing purposes
    
    //die("Server Name: " . $_SERVER['SERVER_NAME'] . "<br>Is Local Environment: " . ($isLocalEnvironment ? 'Yes' : 'No') . "<br>");

    // Database configuration
    if ($isLocalEnvironment) {
        // Local development settings
        define('DB_HOST', 'localhost');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_NAME', 'portfolio_cms');
        define('ENVIRONMENT', 'development');
    } else {
        // Production server settings - UPDATE THESE VALUES ON YOUR SERVER
        define('DB_HOST', 'localhost');
        define('DB_USER', 'i5486702_wp2');
        define('DB_PASS', 'H.CahOXC5VaWA7E0NF595');
        define('DB_NAME', 'i5486702_wp2');
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
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Create database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        $conn->query($sql);
        
        // Select the database
        $conn->select_db(DB_NAME);
        if ($conn->query($sql) === FALSE) {
            die("Error creating database: " . $conn->error);
        }

        $conn->select_db(DB_NAME);

        // Create content_sections table
        $sql = "CREATE TABLE IF NOT EXISTS content_sections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            section_name VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";

        if ($conn->query($sql) === FALSE) {
            die("Error creating content_sections table: " . $conn->error);
        }

        // Create skills table
        $sql = "CREATE TABLE IF NOT EXISTS skills (
            id INT AUTO_INCREMENT PRIMARY KEY,
            skill_name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            display_order INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if ($conn->query($sql) === FALSE) {
            die("Error creating skills table: " . $conn->error);
        }

        // Create projects table
        $sql = "CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            technologies VARCHAR(255),
            date_completed DATE,
            display_order INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        if ($conn->query($sql) === FALSE) {
            die("Error creating projects table: " . $conn->error);
        }
    } // End of database connection check
} // End of DB_CONFIG_LOADED check
?>
