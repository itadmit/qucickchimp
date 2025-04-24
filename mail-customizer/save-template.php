<?php
header('Content-Type: application/json');

// בדיקת הרשאות והתחברות למסד הנתונים
require_once '../config/database.php';

try {
    // קבלת הנתונים מה-POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['html']) || !isset($data['name'])) {
        throw new Exception('חסרים פרטים נדרשים');
    }
    
    $html = $data['html'];
    $name = $data['name'];
    
    // הכנת השאילתה
    $stmt = $pdo->prepare("INSERT INTO email_templates (name, html, created_at) VALUES (?, ?, NOW())");
    
    // ביצוע השאילתה
    $stmt->execute([$name, $html]);
    
    // החזרת תשובה
    echo json_encode([
        'success' => true,
        'message' => 'התבנית נשמרה בהצלחה',
        'id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 