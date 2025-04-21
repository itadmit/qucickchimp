<?php
ob_start(); // התחל לאגור פלט במקום לשלוח אותו מיד לדפדפן
require_once '../config/config.php';

// Set page title
$pageTitle = 'הוספת רשימה חדשה';
$pageDescription = 'הוספת רשימת אנשי קשר חדשה למערכת';

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Initialize variables
$name = '';
$description = '';
$isDefault = 0;
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    
    // Validate input
    if (empty($name)) {
        $error = 'שם הרשימה הוא שדה חובה';
    } else {
        // Check if the list name already exists for this user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_lists WHERE name = ? AND user_id = ?");
        $stmt->execute([$name, $userId]);
        $nameExists = $stmt->fetchColumn();
        
        if ($nameExists) {
            $error = 'רשימה עם שם זה כבר קיימת';
        } else {
            // If this list is set as default, unset all other defaults
            if ($isDefault) {
                $stmt = $pdo->prepare("UPDATE contact_lists SET is_default = 0 WHERE user_id = ?");
                $stmt->execute([$userId]);
            }
            
            // Insert new list
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contact_lists 
                    (user_id, name, description, is_default, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                
                $result = $stmt->execute([
                    $userId,
                    $name,
                    $description,
                    $isDefault
                ]);
                
                if ($result) {
                    $_SESSION['success'] = 'הרשימה נוספה בהצלחה';
                    redirect('contact_lists.php');
                } else {
                    $error = 'אירעה שגיאה בעת הוספת הרשימה';
                }
            } catch (PDOException $e) {
                // Check if table exists
                if ($e->getCode() == '42S02') { // Table doesn't exist
                    $error = 'טבלת הרשימות אינה קיימת במסד הנתונים';
                } else {
                    $error = 'אירעה שגיאה בעת הוספת הרשימה: ' . $e->getMessage();
                }
            }
        }
    }
}
?>

<div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
    <div class="border-b border-gray-200 px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">
            הוספת רשימת אנשי קשר חדשה
        </h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">
            צור רשימה חדשה לארגון אנשי הקשר שלך
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
    
    <div class="px-4 py-5 sm:p-6">
        <form action="contact_list_create.php" method="POST">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">שם הרשימה <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($name); ?>" 
                           class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           required>
                    <p class="mt-1 text-sm text-gray-500">
                        שם הרשימה יוצג במערכת וישמש לזיהויה
                    </p>
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">תיאור הרשימה</label>
                    <textarea name="description" id="description" rows="3" 
                              class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"><?php echo htmlspecialchars($description); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">
                        תיאור קצר של הרשימה ושימושיה
                    </p>
                </div>
                
                <div class="flex items-start mt-2">
                    <div class="flex items-center h-5">
                        <input id="is_default" name="is_default" type="checkbox" value="1" <?php if ($isDefault) echo 'checked'; ?>
                               class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="mr-3 text-sm">
                        <label for="is_default" class="font-medium text-gray-700">רשימת ברירת מחדל</label>
                        <p class="text-gray-500">רשימה זו תהיה רשימת ברירת המחדל אליה יתווספו אנשי קשר חדשים</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex space-x-3">
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    הוסף רשימה
                </button>
                <a href="contact_lists.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    ביטול
                </a>
            </div>
        </form>
    </div>
</div>

<?php 
include_once 'template/footer.php'; 
ob_end_flush(); // שחרר את הפלט המאוגר לדפדפן
?> 