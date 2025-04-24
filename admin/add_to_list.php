<?php
ob_start();
require_once '../config/config.php';

// Set page title
$pageTitle = 'הוספת איש קשר לרשימה';
$pageDescription = 'הוספת איש קשר ידנית לרשימת אנשי קשר';

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
        $_SESSION['error'] = 'הרשימה המבוקשת לא נמצאה או שאין לך הרשאה להוסיף אליה';
        redirect('contact_lists.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת פרטי הרשימה';
    redirect('contact_lists.php');
}

// ערכים התחלתיים
$email = '';
$firstName = '';
$lastName = '';
$phone = '';
$error = '';
$success = '';

// טיפול בשליחת הטופס
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // קבלת נתוני הטופס
    $email = sanitize($_POST['email'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    // וידוא תקינות האימייל
    if (empty($email)) {
        $error = 'אימייל הוא שדה חובה';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'אנא הזן כתובת אימייל תקינה';
    } else {
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
                    $error = 'איש הקשר כבר קיים ברשימה זו';
                } else {
                    // אם לא קיים ברשימה, נוסיף אותו
                    $stmt = $pdo->prepare("INSERT INTO subscriber_lists (subscriber_id, list_id, created_at) VALUES (?, ?, NOW())");
                    if ($stmt->execute([$subscriberId, $listId])) {
                        // עדכון פרטי המנוי אם הם שונים
                        $stmt = $pdo->prepare("
                            UPDATE subscribers 
                            SET first_name = CASE WHEN ? != '' THEN ? ELSE first_name END,
                                last_name = CASE WHEN ? != '' THEN ? ELSE last_name END,
                                phone = CASE WHEN ? != '' THEN ? ELSE phone END,
                                updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $firstName, $firstName,
                            $lastName, $lastName,
                            $phone, $phone,
                            $subscriberId
                        ]);
                        
                        $success = 'איש הקשר נוסף בהצלחה לרשימה';
                        
                        // איפוס הטופס
                        $email = '';
                        $firstName = '';
                        $lastName = '';
                        $phone = '';
                    } else {
                        $error = 'אירעה שגיאה בהוספת איש הקשר לרשימה';
                    }
                }
            } 
            // אם המנוי לא קיים, נוסיף אותו
            else {
                // הוספת המנוי
                $stmt = $pdo->prepare("
                    INSERT INTO subscribers 
                    (user_id, email, first_name, last_name, phone, is_subscribed, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
                ");
                
                if ($stmt->execute([
                    $userId,
                    $email,
                    $firstName,
                    $lastName,
                    $phone
                ])) {
                    $subscriberId = $pdo->lastInsertId();
                    
                    // הוספת המנוי לרשימה
                    $stmt = $pdo->prepare("INSERT INTO subscriber_lists (subscriber_id, list_id, created_at) VALUES (?, ?, NOW())");
                    if ($stmt->execute([$subscriberId, $listId])) {
                        $success = 'איש הקשר נוסף בהצלחה לרשימה';
                        
                        // איפוס הטופס
                        $email = '';
                        $firstName = '';
                        $lastName = '';
                        $phone = '';
                    } else {
                        $error = 'אירעה שגיאה בהוספת איש הקשר לרשימה';
                    }
                } else {
                    $error = 'אירעה שגיאה ביצירת איש הקשר החדש';
                }
            }
        } catch (PDOException $e) {
            $error = 'אירעה שגיאה בעת הוספת איש הקשר: ' . $e->getMessage();
        }
    }
}

// Include header
include_once 'template/header.php';
?>

<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            הוספת איש קשר לרשימת: <?php echo htmlspecialchars($list['name']); ?>
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            הוסף איש קשר ידנית לרשימה זו
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
    
    <?php if (!empty($success)): ?>
    <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-4 mx-6 mt-6 rounded-md">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="ri-checkbox-circle-line text-green-500 text-xl"></i>
            </div>
            <div class="mr-3">
                <p class="text-sm"><?php echo $success; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="px-4 py-5 sm:p-6">
        <form action="add_to_list.php?id=<?php echo $listId; ?>" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">אימייל <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>"
                           class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           required>
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">טלפון</label>
                    <input type="text" name="phone" id="phone" value="<?php echo htmlspecialchars($phone); ?>"
                           class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="first_name" class="block text-sm font-medium text-gray-700">שם פרטי</label>
                    <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($firstName); ?>"
                           class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
                
                <div>
                    <label for="last_name" class="block text-sm font-medium text-gray-700">שם משפחה</label>
                    <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($lastName); ?>"
                           class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>
            </div>
            
            <div class="mt-6 flex items-center justify-between">
                <p class="text-sm text-gray-500">
                    <i class="ri-information-line ml-1 text-blue-500"></i>
                    אם האימייל כבר קיים במערכת, המידע החדש יעדכן את הפרטים הקיימים
                </p>
                
                <div class="flex space-x-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        <i class="ri-add-line ml-1"></i>
                        הוסף לרשימה
                    </button>
                    <a href="contact_lists.php" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                        חזרה לרשימות
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
        <div class="flex justify-between items-center">
            <h4 class="text-sm font-medium text-gray-500">
                פעולות נוספות
            </h4>
            <div>
                <a href="import_to_list.php?id=<?php echo $listId; ?>" class="inline-flex items-center text-sm text-purple-600 hover:text-purple-900">
                    <i class="ri-upload-line ml-1"></i>
                    ייבוא מרובה מקובץ CSV
                </a>
                <span class="mx-2 text-gray-300">|</span>
                <a href="export_list.php?id=<?php echo $listId; ?>" class="inline-flex items-center text-sm text-green-600 hover:text-green-900">
                    <i class="ri-download-line ml-1"></i>
                    ייצוא הרשימה לקובץ CSV
                </a>
            </div>
        </div>
    </div>
</div>

<?php 
include_once 'template/footer.php'; 
ob_end_flush();
?> 