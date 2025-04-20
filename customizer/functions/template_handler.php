<?php
require_once '../../config/config.php';
require_once '../functions/template_loader.php';

// Check if user is logged in
requireLogin();

// Handle template change request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_template') {
    $templateId = (int)($_POST['template_id'] ?? 0);
    $landingPageId = (int)($_POST['landing_page_id'] ?? 0);
    $userId = $_SESSION['user_id'] ?? 0;
    
    $response = ['success' => false];
    
    if ($templateId && $landingPageId && $userId) {
        if (changeTemplate($landingPageId, $templateId, $userId)) {
            $response['success'] = true;
        } else {
            $response['message'] = 'שגיאה בהחלפת התבנית';
        }
    } else {
        $response['message'] = 'נתונים חסרים';
    }
    
    // Send response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Handle other template-related actions