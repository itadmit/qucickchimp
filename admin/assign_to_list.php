<?php
ob_start();
require_once '../config/config.php';

// Set page title
$pageTitle = 'שיוך ליד לרשימה';
$pageDescription = 'הוספת ליד קיים לרשימות אנשי קשר';

// בדיקת התחברות משתמש
requireLogin();

// קבלת מזהה ליד
$subscriberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'] ?? 0;

// וידוא שהליד קיים ושייך למשתמש
try {
    $stmt = $pdo->prepare("SELECT * FROM subscribers WHERE id = ? AND user_id = ?");
    $stmt->execute([$subscriberId, $userId]);
    $subscriber = $stmt->fetch();
    
    if (!$subscriber) {
        $_SESSION['error'] = 'הליד המבוקש לא נמצא או שאין לך הרשאה לערוך אותו';
        redirect('subscribers.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת פרטי הליד';
    redirect('subscribers.php');
}

// קבלת כל הרשימות של המשתמש
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_lists WHERE user_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    $lists = $stmt->fetchAll();
} catch (PDOException $e) {
    $lists = [];
}

// קבלת הרשימות שהליד כבר משויך אליהן
try {
    $stmt = $pdo->prepare("
        SELECT list_id 
        FROM subscriber_lists 
        WHERE subscriber_id = ?
    ");
    $stmt->execute([$subscriberId]);
    $subscribed_lists = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $subscribed_lists = [];
}

$error = '';
$success = '';

// טיפול בשליחת הטופס
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // קבלת רשימות שנבחרו
    $selectedLists = $_POST['lists'] ?? [];
    
    try {
        // מחיקת כל השיוכים הקיימים אם נבחרה האפשרות להחליף
        if (isset($_POST['replace_existing']) && $_POST['replace_existing'] == '1') {
            $stmt = $pdo->prepare("DELETE FROM subscriber_lists WHERE subscriber_id = ?");
            $stmt->execute([$subscriberId]);
            $subscribed_lists = []; // איפוס הרשימה של השיוכים הקיימים
        }
        
        // הוספה לכל רשימה שנבחרה
        $added = 0;
        foreach ($selectedLists as $listId) {
            $listId = (int)$listId;
            
            // בדיקה שהרשימה שייכת למשתמש
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_lists WHERE id = ? AND user_id = ?");
            $stmt->execute([$listId, $userId]);
            $validList = $stmt->fetchColumn();
            
            if ($validList) {
                // בדיקה אם כבר קיים שיוך לרשימה זו
                if (!in_array($listId, $subscribed_lists)) {
                    $stmt = $pdo->prepare("INSERT INTO subscriber_lists (subscriber_id, list_id, created_at) VALUES (?, ?, NOW())");
                    if ($stmt->execute([$subscriberId, $listId])) {
                        $added++;
                        // הוספה לרשימת השיוכים הקיימים
                        $subscribed_lists[] = $listId;
                    }
                }
            }
        }
        
        if ($added > 0) {
            $success = "הליד שויך בהצלחה ל-$added רשימות";
        } else {
            $error = 'לא בוצעו שינויים. ייתכן שהליד כבר משויך לרשימות שבחרת';
        }
    } catch (PDOException $e) {
        $error = 'אירעה שגיאה בעת שיוך הליד לרשימות: ' . $e->getMessage();
    }
}

// Include header
include_once 'template/header.php';
?>

<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            שיוך ליד לרשימות אנשי קשר
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            הוסף את הליד הזה לרשימה אחת או יותר
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
    
    <!-- פרטי הליד -->
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-4">
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">אימייל</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($subscriber['email']); ?></dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">שם מלא</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php 
                    $fullName = trim($subscriber['first_name'] . ' ' . $subscriber['last_name']);
                    echo !empty($fullName) ? htmlspecialchars($fullName) : '---'; 
                    ?>
                </dd>
            </div>
            <div class="sm:col-span-1">
                <dt class="text-sm font-medium text-gray-500">טלפון</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php echo !empty($subscriber['phone']) ? htmlspecialchars($subscriber['phone']) : '---'; ?>
                </dd>
            </div>
        </dl>
    </div>
    
    <div class="px-4 py-5 sm:p-6">
        <form action="assign_to_list.php?id=<?php echo $subscriberId; ?>" method="POST">
            <?php if (empty($lists)): ?>
                <div class="bg-yellow-100 rounded-md p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="ri-information-line text-yellow-500 text-xl"></i>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-yellow-700">אין רשימות זמינות. <a href="contact_list_create.php" class="underline">צור רשימה חדשה</a> לפני שתמשיך.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <fieldset>
                    <legend class="text-base font-medium text-gray-900">בחר רשימות לשיוך הליד</legend>
                    
                    <div class="mt-4 border-b pb-4 mb-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="replace_existing" name="replace_existing" value="1"
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="replace_existing" class="mr-2 block text-sm text-gray-700">
                                החלף את השיוכים הקיימים (הסר מכל הרשימות הקיימות)
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-4 space-y-2">
                        <?php foreach ($lists as $list): ?>
                            <div class="relative flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" id="list_<?php echo $list['id']; ?>" name="lists[]" value="<?php echo $list['id']; ?>"
                                           <?php echo in_array($list['id'], $subscribed_lists) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </div>
                                <div class="mr-3 text-sm">
                                    <label for="list_<?php echo $list['id']; ?>" class="font-medium text-gray-700">
                                        <?php echo htmlspecialchars($list['name']); ?>
                                    </label>
                                    <?php if (!empty($list['description'])): ?>
                                        <p class="text-gray-500"><?php echo htmlspecialchars($list['description']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if (in_array($list['id'], $subscribed_lists)): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                                            <i class="ri-check-line ml-1"></i>
                                            כבר משויך
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                
                <div class="mt-6 flex items-center justify-between">
                    <p class="text-sm text-gray-500">
                        <i class="ri-information-line ml-1 text-blue-500"></i>
                        סמן את הרשימות שברצונך להוסיף אליהן את הליד
                    </p>
                    
                    <div class="flex space-x-3">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            <i class="ri-save-line ml-1"></i>
                            שמור שינויים
                        </button>
                        <a href="subscribers.php" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                            חזרה לרשימת הלידים
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php 
include_once 'template/footer.php'; 
ob_end_flush();
?> 