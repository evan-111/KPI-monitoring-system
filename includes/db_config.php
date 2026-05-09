<?php
/**
 * SAKMS - Database Configuration
 * XAMPP MySQL Connection Setup
 */

// ==========================================
// XAMPP Database Credentials
// ==========================================
define('DB_HOST', 'localhost');      // XAMPP default host
define('DB_USER', 'root');           // XAMPP default user
define('DB_PASS', '');               // XAMPP default (empty password)
define('DB_NAME', 'sakms_db');       // Database name

// ==========================================
// Create Connection
// ==========================================
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Database Connection Failed: " . $conn->connect_error);
    }
    
    // Set charset to UTF-8
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Connection Error: " . $e->getMessage());
}

// ==========================================
// Enable error reporting for development
// ==========================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
