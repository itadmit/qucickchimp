<?php
ob_start();
require_once '../config/config.php';

// Set page title
$pageTitle = 'ייבוא אנשי קשר';
$pageDescription = 'ייבוא אנשי קשר מקובץ CSV לרשימה';

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
        $_SESSION['error'] = 'הרשימה המבוקשת לא נמצאה או שאין לך הרשאה לייבא אליה';
        redirect('contact_lists.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת פרטי הרשימה';
    redirect('contact_lists.php');
}

// משתני תוצאה ושגיאה
$error = '';
$importResults = [
    'total' => 0,
    'success' => 0,
    'duplicates' => 0,
    'invalid' => 0
];

// טיפול בטעינת קובץ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    
    // בדיקת שגיאות העלאה
    if ($file['error'] != 0) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error = 'הקובץ שהועלה גדול מהגודל המקסימלי המותר';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'הקובץ שהועלה גדול מהגודל המקסימלי המותר בטופס';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'הקובץ הועלה באופן חלקי בלבד';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'לא הועלה קובץ';
                break;
            default:
                $error = 'אירעה שגיאה בהעלאת הקובץ';
        }
    } 
    else {
        // בדיקת סוג הקובץ
        $fileInfo = pathinfo($file['name']);
        if (strtolower($fileInfo['extension']) !== 'csv') {
            $error = 'יש להעלות קובץ CSV בלבד';
        } 
        else {
            // פתיחת הקובץ לקריאה
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
                // קריאת הקובץ
                $row = 0;
                $headers = [];
                $emailIndex = -1;
                
                // עיבוד הקובץ שורה אחר שורה
                while (($data = fgetcsv($handle)) !== FALSE) {
                    $row++;
                    
                    // עיבוד שורת הכותרות
                    if ($row === 1) {
                        $headers = $data;
                        
                        // חיפוש עמודת האימייל
                        foreach ($headers as $index => $header) {
                            if (mb_strtolower(trim($header)) === 'אימייל' || 
                                mb_strtolower(trim($header)) === 'email' || 
                                mb_strtolower(trim($header)) === 'mail') {
                                $emailIndex = $index;
                                break;
                            }
                        }
                        
                        if ($emailIndex === -1) {
                            $error = 'לא נמצאה עמודת אימייל בקובץ CSV. יש לודא שיש עמודה בשם "אימייל" או "email"';
                            break;
                        }
                        
                        continue;
                    }
                    
                    $importResults['total']++;
                    
                    // קבלת נתוני השורה
                    $email = isset($data[$emailIndex]) ? trim($data[$emailIndex]) : '';
                    
                    // אימות אימייל
                    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $importResults['invalid']++;
                        continue;
                    }
                    
                    // חיפוש שדות נוספים
                    $firstName = '';
                    $lastName = '';
                    $phone = '';
                    
                    foreach ($headers as $index => $header) {
                        $headerLower = mb_strtolower(trim($header));
                        $value = isset($data[$index]) ? trim($data[$index]) : '';
                        
                        if (in_array($headerLower, ['שם פרטי', 'שם', 'first name', 'firstname', 'first'])) {
                            $firstName = $value;
                        } 
                        else if (in_array($headerLower, ['שם משפחה', 'משפחה', 'last name', 'lastname', 'last'])) {
                            $lastName = $value;
                        }
                        else if (in_array($headerLower, ['טלפון', 'נייד', 'phone', 'mobile'])) {
                            $phone = $value;
                        }
                    }
                    
                    try {
                        // בדיקה אם המנוי כבר קיים
                        $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ? AND user_id = ?");
                        $stmt->execute([$email, $userId]);
                        $subscriberId = $stmt->fetchColumn();
                        
                        // אם המנוי קיים
                        if ($subscriberId) {
                            // בדיקה אם המנוי כבר ברשימה
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscriber_lists WHERE subscriber_id = ? AND list_id = ?");
                            $stmt->execute([$subscriberId, $listId]);
                            $exists = $stmt->fetchColumn();
                            
                            if ($exists) {
                                $importResults['duplicates']++;
                            } else {
                                // הוספה לרשימה אם טרם נמצא בה
                                $stmt = $pdo->prepare("INSERT INTO subscriber_lists (subscriber_id, list_id, created_at) VALUES (?, ?, NOW())");
                                $stmt->execute([$subscriberId, $listId]);
                                $importResults['success']++;
                            }
                        } 
                        // יצירת מנוי חדש
                        else {
                            // הוספת המנוי
                            $stmt = $pdo->prepare("
                                INSERT INTO subscribers 
                                (user_id, email, first_name, last_name, phone, is_subscribed, created_at, updated_at) 
                                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                            ");
                            
                            $stmt->execute([
                                $userId,
                                $email,
                                $firstName,
                                $lastName,
                                $phone
                            ]);
                            
                            $subscriberId = $pdo->lastInsertId();
                            
                            // הוספת המנוי לרשימה
                            $stmt = $pdo->prepare("INSERT INTO subscriber_lists (subscriber_id, list_id, created_at) VALUES (?, ?, NOW())");
                            $stmt->execute([$subscriberId, $listId]);
                            
                            $importResults['success']++;
                        }
                    } catch (PDOException $e) {
                        $importResults['invalid']++;
                    }
                }
                
                fclose($handle);
                
                // אם היו יותר מדי שגיאות
                if ($importResults['total'] > 0 && $importResults['invalid'] == $importResults['total']) {
                    $error = 'לא ניתן היה לייבא אף רשומה. בדוק את מבנה הקובץ ונסה שנית';
                } 
            } else {
                $error = 'לא ניתן היה לקרוא את הקובץ';
            }
        }
    }
}

// Include header
include_once 'template/header.php';
?>

<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            ייבוא אנשי קשר לרשימת: <?php echo htmlspecialchars($list['name']); ?>
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            העלה קובץ CSV כדי לייבא אנשי קשר לרשימה זו
        </p>
    </div>
    
    <?php if (!empty($error)): ?>
    <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-4 mx-6 mt-6 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="ri-error-warning-line text-red-500 text-xl"></i>
            </div>
            <div class="mr-3">
                <p class="text-sm"><?php echo $error; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_FILES['csv_file']) && empty($error) && $importResults['total'] > 0): ?>
    <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-4 mx-6 mt-6 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="ri-checkbox-circle-line text-green-500 text-xl"></i>
            </div>
            <div class="mr-3">
                <p class="text-sm font-medium">הייבוא הושלם בהצלחה!</p>
                <ul class="mt-2 text-sm">
                    <li>סה"כ רשומות שנמצאו: <?php echo $importResults['total']; ?></li>
                    <li>רשומות שיובאו בהצלחה: <?php echo $importResults['success']; ?></li>
                    <li>רשומות כפולות שלא יובאו: <?php echo $importResults['duplicates']; ?></li>
                    <li>רשומות שגויות שלא יובאו: <?php echo $importResults['invalid']; ?></li>
                </ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="px-4 py-5 sm:p-6">
        <form action="import_to_list.php?id=<?php echo $listId; ?>" method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="csv_file" class="block text-sm font-medium text-gray-700">קובץ CSV <span class="text-red-500">*</span></label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <i class="ri-upload-2-line text-gray-400 text-3xl mb-2"></i>
                            <div class="flex text-sm text-gray-600">
                                <label for="csv_file" class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500">
                                    <span>העלה קובץ</span>
                                    <input id="csv_file" name="csv_file" type="file" class="sr-only" accept=".csv" required>
                                </label>
                                <p class="pr-1">או גרור ושחרר לכאן</p>
                            </div>
                            <p class="text-xs text-gray-500">CSV בלבד</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-md">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">הוראות:</h4>
                    <ul class="list-disc list-inside text-sm text-gray-500 space-y-1">
                        <li>יש לכלול שורת כותרות בקובץ ה-CSV</li>
                        <li>עמודת אימייל חובה (עם הכותרת "אימייל" או "email")</li>
                        <li>כדאי לכלול עמודות: שם פרטי, שם משפחה, טלפון</li>
                        <li>אימיילים כפולים לא יתווספו</li>
                        <li>CSV צריך להיות בקידוד UTF-8</li>
                    </ul>
                </div>
                
                <div class="flex justify-between items-center">
                    <div>
                        <a href="export_list_template.php" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                            <i class="ri-download-line ml-1"></i>
                            הורד תבנית CSV
                        </a>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            <i class="ri-upload-line ml-1"></i>
                            התחל ייבוא
                        </button>
                        <a href="contact_lists.php" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                            חזרה לרשימות
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php 
include_once 'template/footer.php'; 
ob_end_flush();
?> 