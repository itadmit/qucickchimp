<?php

/**
 * לטעון את כל התבניות הזמינות מהתיקייה למסד הנתונים
 */
function importTemplatesToDatabase() {
    global $pdo;
    
    $templatesDir = __DIR__ . '/templates';
    $templateTypes = ['basic', 'business', 'sales'];
    
    foreach ($templateTypes as $type) {
        $templatePath = $templatesDir . '/' . $type . '/template.html';
        $thumbnailPath = $templatesDir . '/' . $type . '/thumbnail.svg';
        
        if (file_exists($templatePath)) {
            $templateContent = file_get_contents($templatePath);
            $thumbnailContent = file_exists($thumbnailPath) ? file_get_contents($thumbnailPath) : '';
            
            // שמות התבניות בעברית
            $templateNames = [
                'basic' => 'תבנית בסיסית',
                'business' => 'תבנית עסקית',
                'sales' => 'תבנית מכירות'
            ];
            
            $name = $templateNames[$type] ?? $type;
            
            // בדיקה אם התבנית כבר קיימת
            $stmt = $pdo->prepare("SELECT id FROM landing_templates WHERE slug = ?");
            $stmt->execute([$type]);
            $existingTemplate = $stmt->fetch();
            
            if ($existingTemplate) {
                // עדכון תבנית קיימת
                $stmt = $pdo->prepare("UPDATE landing_templates SET 
                    name = ?, content = ?, thumbnail = ?, updated_at = NOW()
                    WHERE id = ?");
                $stmt->execute([$name, $templateContent, $thumbnailContent, $existingTemplate['id']]);
            } else {
                // יצירת תבנית חדשה
                $stmt = $pdo->prepare("INSERT INTO landing_templates 
                    (name, slug, content, thumbnail, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$name, $type, $templateContent, $thumbnailContent]);
            }
        }
    }
    
    return true;
}

// הרצת הפונקציה
// importTemplatesToDatabase();