<?php
header('Content-Type: application/json');

// התחברות למסד הנתונים
require_once '../config/database.php';

try {
    // שליפת כל תבניות המייל
    $stmt = $pdo->prepare("SELECT id, name, html, created_at FROM email_templates ORDER BY name");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // החזרת התבניות כ-JSON
    echo json_encode([
        'success' => true,
        'templates' => $templates
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 