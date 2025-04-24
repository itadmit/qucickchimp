<?php
header('Content-Type: application/json');

// ניסיון להגדיל את זמן הביצוע אם מדובר ב-HTML גדול
ini_set('max_execution_time', 120); // 120 seconds
ini_set('memory_limit', '256M');    // 256 MB

// קבצי לוג לצורך דיבאג
$logFile = __DIR__ . '/debug_load.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - טעינת תבניות החלה' . PHP_EOL, FILE_APPEND);

// בדיקה שקובץ מסד הנתונים קיים
$dbFilePath = __DIR__ . '/../config/db.php';
if (!file_exists($dbFilePath)) {
    $dbFilePath = __DIR__ . '/config/db.php';
    if (!file_exists($dbFilePath)) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה: קובץ מסד הנתונים לא נמצא' . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'קובץ הגדרות מסד הנתונים חסר'
        ]);
        exit;
    }
}

// התחברות למסד הנתונים
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - מנסה להתחבר למסד הנתונים' . PHP_EOL, FILE_APPEND);
try {
    require_once $dbFilePath;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - התחברות למסד הנתונים הצליחה' . PHP_EOL, FILE_APPEND);
} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאת התחברות: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'שגיאה בהתחברות למסד הנתונים: ' . $e->getMessage()
    ]);
    exit;
}

try {
    // קבלת ה-user_id מה-GET או השמת ברירת מחדל
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - טעינת תבניות עבור משתמש ID: ' . $user_id . PHP_EOL, FILE_APPEND);
    
    // בדיקה אם הטבלה קיימת
    try {
        $tableCheckStmt = $pdo->query("SHOW TABLES LIKE 'email_templates'");
        $tableExists = $tableCheckStmt->rowCount() > 0;
        
        if (!$tableExists) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה: טבלת email_templates לא קיימת במסד הנתונים' . PHP_EOL, FILE_APPEND);
            throw new Exception('טבלת email_templates לא קיימת במסד הנתונים');
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - בדיקת טבלה: טבלת email_templates קיימת' . PHP_EOL, FILE_APPEND);
        
    } catch (PDOException $tableCheckError) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה בבדיקת קיום טבלה: ' . $tableCheckError->getMessage() . PHP_EOL, FILE_APPEND);
        throw new Exception('שגיאה בבדיקת קיום טבלה: ' . $tableCheckError->getMessage());
    }
    
    // טעינת כל התבניות מהמסד נתונים לפי user_id
    $sql = "SELECT id, name, html, UNIX_TIMESTAMP(created_at) as created_at, UNIX_TIMESTAMP(updated_at) as updated_at 
            FROM email_templates 
            WHERE user_id = ? 
            ORDER BY name ASC";
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - SQL לטעינה: ' . $sql . PHP_EOL, FILE_APPEND);
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$user_id]);
    
    if (!$result) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה בטעינה: ' . implode(', ', $stmt->errorInfo()) . PHP_EOL, FILE_APPEND);
        throw new Exception('שגיאה בטעינת התבניות: ' . implode(', ', $stmt->errorInfo()));
    }
    
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($templates);
    
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - נטענו ' . $count . ' תבניות' . PHP_EOL, FILE_APPEND);
    
    // החזרת הנתונים כתשובת JSON
    $response = [
        'success' => true,
        'templates' => $templates,
        'count' => $count
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // במקרה של שגיאה, החזרת תשובת JSON מתאימה
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 