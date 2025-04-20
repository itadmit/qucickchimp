<?php
/**
 * User Logout
 * 
 * This script handles the logout process - destroys the session
 * and redirects the user back to the login page
 */

// Include configuration file
require_once 'config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Set logout success message
    $_SESSION['success'] = 'התנתקת בהצלחה';
    
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

// Redirect to login page
redirect(APP_URL . '/login.php');