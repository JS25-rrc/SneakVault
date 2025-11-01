<?php
    /**
     * SneakVault CMS - Logout Handler
     * 
     * Logs out the current user and destroys the session.
     * 
     * Requirements Met:
     * - 7.4: Login/logout functionality (5%)
     */

    session_start();

    // Clear all session variables
    $_SESSION = [];

    // Destroy the session cookie if it exists
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to homepage
    header("Location: index.php");
    exit;
?>