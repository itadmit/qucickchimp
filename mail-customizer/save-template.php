<?php
header('Content-Type: application/json');

// ניסיון להגדיל את זמן הביצוע אם מדובר ב-HTML גדול
ini_set('max_execution_time', 120); // 120 seconds
ini_set('memory_limit', '256M');    // 256 MB

// קבצי לוג לצורך דיבאג
$logFile = __DIR__ . '/debug_save.log';
file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שמירת תבנית החלה' . PHP_EOL, FILE_APPEND);

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

// בדיקת הרשאות והתחברות למסד הנתונים
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
    // קבלת הנתונים מה-POST
    $input = file_get_contents('php://input');
    
    // רישום לוג של המידע שהתקבל
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - מידע שהתקבל: ' . substr($input, 0, 500) . '...' . PHP_EOL, FILE_APPEND);
    
    $data = json_decode($input, true);
    
    // בדיקה אם קיבלנו JSON תקין
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON לא תקין: ' . json_last_error_msg());
    }
    
    if (!isset($data['html']) || !isset($data['name'])) {
        throw new Exception('חסרים פרטים נדרשים (שם או HTML)');
    }
    
    $html = $data['html'];
    $name = trim($data['name']);
    
    // קביעת ערך ברירת מחדל ל-user_id אם לא התקבל
    $user_id = isset($data['user_id']) ? (int)$data['user_id'] : 1;
    
    // קביעת ערך ל-campaign_id אם התקבל
    $campaign_id = isset($data['campaign_id']) ? (int)$data['campaign_id'] : null;
    
    // בדיקה שהשדות לא ריקים
    if (empty($html)) {
        throw new Exception('תוכן ה-HTML לא יכול להיות ריק');
    }
    
    if (empty($name)) {
        throw new Exception('שם התבנית לא יכול להיות ריק');
    }
    
    // חיבור למסד הנתונים
    try {
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
        
        // בדיקה אם התבנית כבר קיימת למשתמש זה
        $checkStmt = $pdo->prepare("SELECT id FROM email_templates WHERE name = ? AND user_id = ?");
        $checkStmt->execute([$name, $user_id]);
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - בדיקת קיום תבנית: ' . $name . ' עבור משתמש ' . $user_id . PHP_EOL, FILE_APPEND);
        
        $existingTemplate = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingTemplate) {
            // עדכון תבנית קיימת
            $sql = "UPDATE email_templates SET html = ?, campaign_id = ?, updated_at = NOW() WHERE id = ?";
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' - SQL לעדכון: ' . $sql . PHP_EOL, FILE_APPEND);
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$html, $campaign_id, $existingTemplate['id']]);
            
            if (!$result) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה בעדכון: ' . implode(', ', $stmt->errorInfo()) . PHP_EOL, FILE_APPEND);
                throw new Exception('שגיאה בעדכון התבנית: ' . implode(', ', $stmt->errorInfo()));
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' - עדכון תבנית קיימת: ' . $existingTemplate['id'] . PHP_EOL, FILE_APPEND);
            
            $message = 'התבנית עודכנה בהצלחה';
            $id = $existingTemplate['id'];
        } else {
            // הכנת השאילתה להוספת תבנית חדשה
            $sql = "INSERT INTO email_templates (user_id, campaign_id, name, html, created_at) VALUES (?, ?, ?, ?, NOW())";
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' - SQL להוספה: ' . $sql . PHP_EOL, FILE_APPEND);
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([$user_id, $campaign_id, $name, $html]);
            
            if (!$result) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה בהוספה: ' . implode(', ', $stmt->errorInfo()) . PHP_EOL, FILE_APPEND);
                throw new Exception('שגיאה בהוספת התבנית: ' . implode(', ', $stmt->errorInfo()));
            }
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' - הוספת תבנית חדשה' . PHP_EOL, FILE_APPEND);
            
            $message = 'התבנית נשמרה בהצלחה';
            $id = $pdo->lastInsertId();
        }
        
        // החזרת תשובה
        $response = [
            'success' => true,
            'message' => $message,
            'id' => $id
        ];
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - תשובה: ' . json_encode($response) . PHP_EOL, FILE_APPEND);
        
        echo json_encode($response);
        
    } catch (PDOException $e) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאת PDO: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        throw new Exception('שגיאת מסד נתונים: ' . $e->getMessage());
    }
    
} catch (Exception $e) {
    // רישום השגיאה ללוג
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - שגיאה: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // כל שגיאה תחזיר תשובה בפורמט JSON
    http_response_code(400);
    
    $errorResponse = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    
    // בדיקה שהתשובה יכולה להיות מקודדת ל-JSON
    if (json_encode($errorResponse) === false) {
        // אם יש בעיה בקידוד, שלח הודעת שגיאה פשוטה
        echo json_encode([
            'success' => false,
            'message' => 'שגיאה לא מזוהה'
        ]);
    } else {
        echo json_encode($errorResponse);
    }
} 