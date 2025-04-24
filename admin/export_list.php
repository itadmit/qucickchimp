<?php
ob_start();
require_once '../config/config.php';

// בדיקת התחברות משתמש
requireLogin();

// קבלת מזהה רשימה
$listId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'] ?? 0;

// וידוא שהרשימה קיימת ושייכת למשתמש
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $userId]);
    $list = $stmt->fetch();
    
    if (!$list) {
        $_SESSION['error'] = 'הרשימה המבוקשת לא נמצאה או שאין לך הרשאה לייצא אותה';
        redirect('contact_lists.php');
    }
    
    // חיפוש המנויים ברשימה
    $sql = "SELECT s.* 
            FROM subscribers s
            JOIN subscriber_lists sl ON s.id = sl.subscriber_id
            WHERE sl.list_id = ? AND s.user_id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$listId, $userId]);
    $subscribers = $stmt->fetchAll();
    
    // הגדרת שם הקובץ לייצוא
    $filename = 'list_' . $list['name'] . '_' . date('Y-m-d') . '.csv';
    
    // הגדרת כותרות CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // יצירת ה-CSV
    $output = fopen('php://output', 'w');
    
    // הוספת BOM לתמיכה בעברית
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // כותרות העמודות
    fputcsv($output, ['אימייל', 'שם פרטי', 'שם משפחה', 'טלפון', 'פעיל', 'תאריך הצטרפות', 'שדות נוספים']);
    
    // שורות הנתונים
    foreach ($subscribers as $subscriber) {
        $customFields = !empty($subscriber['custom_fields']) ? $subscriber['custom_fields'] : '';
        
        fputcsv($output, [
            $subscriber['email'],
            $subscriber['first_name'],
            $subscriber['last_name'],
            $subscriber['phone'],
            $subscriber['is_subscribed'] ? 'כן' : 'לא',
            formatDate($subscriber['created_at']),
            $customFields
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בתהליך הייצוא: ' . $e->getMessage();
    redirect('contact_lists.php');
}

ob_end_flush();
?> 