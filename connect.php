<?php
// Database configuration constants
define('DB_DSN', 'mysql:host=localhost;dbname=serverside;charset=utf8');
define('DB_USER', 'serveruser');
define('DB_PASS', 'gorgonzola7!');

try {
    // Create new PDO connection to MySQL
    // PDO::ATTR_ERRMODE set to EXCEPTION for better error handling
    $db = new PDO(DB_DSN, DB_USER, DB_PASS);
    
    // Set error mode to exceptions
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepared statements for better security
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log("Database Connection Error: " . $e->getMessage());
    
    // In production, you would show a generic error page
    // For development, showing the error helps with debugging
    die("Database connection failed. Please check your configuration.");
}

?>