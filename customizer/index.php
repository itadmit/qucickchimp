<?php
require_once '../config/config.php';

// Get landing page ID
$landingPageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'] ?? 0;

// Check if user is logged in
requireLogin();

// Verify the landing page exists and belongs to this user
$landingPage = null;
try {
    $stmt = $pdo->prepare("
        SELECT l.*, t.name as template_name 
        FROM landing_pages l
        LEFT JOIN templates t ON l.template_id = t.id
        WHERE l.id = ? AND l.user_id = ?
    ");
    $stmt->execute([$landingPageId, $userId]);
    $landingPage = $stmt->fetch();
} catch (PDOException $e) {
    // Table might not exist
    $_SESSION['error'] = 'אירעה שגיאה בעת טעינת פרטי דף הנחיתה';
    redirect('../admin/landing_pages.php');
}

if (!$landingPage) {
    $_SESSION['error'] = 'דף הנחיתה המבוקש לא נמצא או שאין לך הרשאה לערוך אותו';
    redirect('../admin/landing_pages.php');
}

// Handle save content
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_content'])) {
    $content = $_POST['content'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE landing_pages SET content = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$content, $landingPageId, $userId])) {
            $_SESSION['success'] = 'דף הנחיתה נשמר בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת שמירת דף הנחיתה';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'אירעה שגיאה בעת שמירת דף הנחיתה';
    }
    
    // Redirect to prevent resubmission
    redirect('index.php?id=' . $landingPageId);
}

// Include data and helpers
require_once 'data/sections.php';
require_once 'data/colors.php';
require_once 'functions/template_loader.php';

// Get current landing page content
$pageContent = $landingPage['content'] ?? '';

// Initialize default content if empty
if (empty($pageContent)) {
    $templateId = $landingPage['template_id'] ?? 1;
    $pageContent = loadTemplateFromFile($templateId);
}

include 'views/customizer.php';