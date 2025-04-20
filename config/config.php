<?php
/**
 * Main Configuration
 */

// Application settings
define('APP_NAME', 'QuickSite');
define('APP_URL', 'http://localhost:8888');
define('APP_VERSION', '1.0');
define('APP_EMAIL', 'support@QuickSite.com');

// Path definitions
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('CUSTOMIZER_PATH', ROOT_PATH . '/customizer');
define('TEMPLATES_PATH', CUSTOMIZER_PATH . '/templates');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
session_start();

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Plans configuration
define('PLAN_BASIC', [
    'name' => 'בסיסי',
    'price' => 99, 
    'max_landing_pages' => 1,
    'max_leads' => 300,
    'max_emails' => 300
]);

define('PLAN_PRO', [
    'name' => 'פרו',
    'price' => 199, 
    'max_landing_pages' => 5,
    'max_leads' => 1000,
    'max_emails' => 1000
]);

define('PLAN_ULTRA', [
    'name' => 'אולטרה',
    'price' => 399, 
    'max_landing_pages' => 15,
    'max_leads' => 5000,
    'max_emails' => 5000
]);

// Trial period (in days)
define('TRIAL_DAYS', 7);

// Include database configuration
require_once ROOT_PATH . '/config/db.php';

// Include common functions
require_once INCLUDES_PATH . '/functions.php';