<?php
/**
 * Common functions
 */

/**
 * Redirect to another page
 *
 * @param string $location URL to redirect to
 * @return void
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Display error message
 *
 * @param string $message Error message
 * @return string Formatted error message
 */
function displayError($message) {
    return '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">' . $message . '</span>
            </div>';
}

/**
 * Display success message
 *
 * @param string $message Success message
 * @return string Formatted success message
 */
function displaySuccess($message) {
    return '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">' . $message . '</span>
            </div>';
}

/**
 * Sanitize user input
 *
 * @param string $input User input
 * @return string Sanitized input
 */
function sanitize($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Check if user is logged in
 *
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require user to be logged in
 *
 * @return void Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'עליך להתחבר כדי לגשת לעמוד זה';
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Get current user data
 *
 * @param PDO $pdo Database connection
 * @return array|null User data or null if not logged in
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check if trial has expired
 *
 * @param string $trialEndDate Trial end date
 * @return bool True if expired, false otherwise
 */
function isTrialExpired($trialEndDate) {
    if (empty($trialEndDate)) {
        return true;
    }
    
    $now = new DateTime();
    $endDate = new DateTime($trialEndDate);
    
    return $now > $endDate;
}

/**
 * Generate a unique slug
 *
 * @param PDO $pdo Database connection
 * @param string $title Title to generate slug from
 * @param string $table Table to check uniqueness in
 * @param string $field Field to check uniqueness in
 * @return string Unique slug
 */
function generateSlug($pdo, $title, $table = 'landing_pages', $field = 'slug') {
    // Transliterate non-latin characters
    $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $title);
    $slug = mb_strtolower($slug, 'UTF-8');
    $slug = trim($slug, '-');
    
    // Check if slug exists
    $originalSlug = $slug;
    $count = 1;
    
    while (true) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $field = ?");
        $stmt->execute([$slug]);
        $exists = $stmt->fetchColumn();
        
        if (!$exists) {
            break;
        }
        
        $slug = $originalSlug . '-' . $count;
        $count++;
    }
    
    return $slug;
}

/**
 * Format date to Hebrew friendly format
 *
 * @param string $date Date to format
 * @return string Formatted date
 */
function formatHebrewDate($date) {
    if (empty($date)) {
        return '';
    }
    
    $timestamp = strtotime($date);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Check if user has reached plan limits
 *
 * @param PDO $pdo Database connection
 * @param int $userId User ID
 * @param string $limitType Type of limit to check (landing_pages, leads, emails)
 * @return bool True if limit reached, false otherwise
 */
function hasReachedPlanLimits($pdo, $userId, $limitType) {
    $user = getCurrentUser($pdo);
    
    if (!$user) {
        return true;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
    $stmt->execute([$user['plan_id']]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        return true;
    }
    
    switch ($limitType) {
        case 'landing_pages':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM landing_pages WHERE user_id = ?");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            return $count >= $plan['max_landing_pages'];
            
        case 'leads':
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE user_id = ? AND created_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            return $count >= $plan['max_leads'];
            
        case 'emails':
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM campaign_stats 
                JOIN campaigns ON campaign_stats.campaign_id = campaigns.id 
                WHERE campaigns.user_id = ? 
                AND campaign_stats.is_sent = 1 
                AND campaign_stats.sent_at >= DATE_FORMAT(NOW() ,'%Y-%m-01')
            ");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
            return $count >= $plan['max_emails'];
            
        default:
            return false;
    }
}