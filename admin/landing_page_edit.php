<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'עריכת דף נחיתה';
$pageDescription = 'ערוך את פרטי דף הנחיתה';

// CSS לסוויצ'ר - ממוקם לפני כל פלט 
$customStyles = "
<style>
/* סגנון הסוויצ'ר */
.switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .3s;
}

.slider:before {
    position: absolute;
    content: \"\";
    height: 18px;
    width: 18px;
    right: 3px;
    bottom: 3px;
    background-color: white;
    transition: .3s;
}

input:checked + .slider {
    background-color: #8b5cf6;
}

input:focus + .slider {
    box-shadow: 0 0 1px #8b5cf6;
}

input:checked + .slider:before {
    transform: translateX(-22px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}

.switch-container {
    display: flex;
    align-items: center;
}
</style>
";

// Get user ID and landing page ID
$userId = $_SESSION['user_id'] ?? 0;
$landingPageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the landing page exists and belongs to this user
$landingPage = null;
try {
    $stmt = $pdo->prepare("
        SELECT l.*, t.name as template_name 
        FROM landing_pages l
        LEFT JOIN templates t ON l.template_id = t.id
        WHERE l.id = ? AND l.user_id = ?
    ");
    $stmt->execute([$landingPageId, $userId]);
    $landingPage = $stmt->fetch();
} catch (PDOException $e) {
    // Table might not exist
    $_SESSION['error'] = 'אירעה שגיאה בעת טעינת פרטי דף הנחיתה';
    redirect('landing_pages.php');
}

if (!$landingPage) {
    $_SESSION['error'] = 'דף הנחיתה המבוקש לא נמצא או שאין לך הרשאה לערוך אותו';
    redirect('landing_pages.php');
}

// Include header
include_once 'template/header.php';
// הוסף את הסגנונות מיד אחרי ה-header
echo $customStyles;

// Initialize variables
$title = $landingPage['title'];
$description = $landingPage['description'];
$slug = $landingPage['slug'];
$isActive = $landingPage['is_active'];
$error = '';

// Get subscriber count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE landing_page_id = ?");
    $stmt->execute([$landingPageId]);
    $subscribersCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Table might not exist
    $subscribersCount = 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($title)) {
        $error = 'כותרת היא שדה חובה';
    } else {
        // Keep the existing slug - no longer allowing changes
        $slug = $landingPage['slug'];
        
        // Update landing page
        try {
            $stmt = $pdo->prepare("
                UPDATE landing_pages 
                SET title = ?, description = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                $title,
                $description,
                $isActive,
                $landingPageId,
                $userId
            ]);
            
            if ($result) {
                $_SESSION['success'] = 'פרטי דף הנחיתה עודכנו בהצלחה';
                redirect('landing_pages.php');
            } else {
                $error = 'אירעה שגיאה בעת עדכון פרטי דף הנחיתה';
            }
        } catch (PDOException $e) {
            $error = 'אירעה שגיאה בעת עדכון פרטי דף הנחיתה: ' . $e->getMessage();
        }
    }
}
?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <div>
            <h2 class="text-xl font-medium">עריכת דף נחיתה</h2>
            <p class="text-gray-500 text-sm mt-1">עריכת פרטי דף הנחיתה "<?php echo htmlspecialchars($title); ?>"</p>
        </div>
        
        <div class="flex items-center space-x-4 space-x-reverse">
            <!-- סוויצ'ר מצב פעיל - מוסר מכאן -->
            <div class="hidden">
                <span class="text-sm text-gray-700 ml-2">דף פעיל</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="is_active_header" class="sr-only peer" <?php if ($isActive) echo 'checked'; ?> onchange="document.getElementById('is_active').checked = this.checked;">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:translate-x-[-100%] peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                </label>
            </div>
            
            <a href="../customizer/index.php?id=<?php echo $landingPageId; ?>" class="inline-flex items-center bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-md hover:bg-indigo-100 transition-colors">
                <i class="ri-palette-line ml-1"></i>
                ערוך עיצוב
            </a>
            
            <a href="<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($slug); ?>" target="_blank" class="inline-flex items-center bg-blue-50 text-blue-700 px-3 py-1.5 rounded-md hover:bg-blue-100 transition-colors">
                <i class="ri-external-link-line ml-1"></i>
                פתח בחלון חדש
            </a>
        </div>
    </div>
    
    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Left column - Form -->
        <div class="md:col-span-2">
            <form method="POST" action="">
                <?php if ($error): ?>
                    <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6">
                        <div class="flex">
                            <div class="mr-1">
                                <i class="ri-error-warning-line text-red-500"></i>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm text-red-700"><?php echo $error; ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                            כותרת <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">תיאור</label>
                        <textarea id="description" name="description" rows="3" 
                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3"><?php echo htmlspecialchars($description); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">תיאור קצר של מטרת דף הנחיתה (לשימוש פנימי בלבד).</p>
                    </div>
                    
                    <!-- URL Slug (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">כתובת URL</label>
                        <div class="flex flex-row-reverse rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 py-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm" style="direction: ltr;">
                                /landing/<?php echo APP_URL; ?>
                            </span>
                            <div class="border-gray-300 flex-1 block w-full rounded-none rounded-r-md border border-r-0 py-3 bg-gray-100 px-3" style="direction: ltr; text-align: right;">
                                <?php echo htmlspecialchars($slug); ?>
                            </div>
                            <button type="button" onclick="copyToClipboard('<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($slug); ?>')" class="inline-flex items-center px-2 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-blue-600 hover:bg-gray-100">
                                <i class="ri-file-copy-line"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500">כתובת ה-URL נוצרת אוטומטית ואינה ניתנת לשינוי. לחץ על הכפתור כדי להעתיק את הכתובת המלאה.</p>
                    </div>
                    
                    <!-- סוויצ'ר פעיל/לא פעיל -->
                    <div class="flex items-center my-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <div class="flex items-center">
                            <div class="switch-container">
                                <label class="switch">
                                    <input type="checkbox" id="is_active" name="is_active" <?php if ($isActive) echo 'checked'; ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="mr-3 text-base font-medium text-gray-700">דף נחיתה פעיל</span>
                            </div>
                        </div>
                        <div class="mr-auto text-sm">
                            <?php echo $isActive ? 
                                '<span class="text-green-600 flex items-center"><i class="ri-eye-line mr-1"></i> נראה לציבור</span>' : 
                                '<span class="text-gray-500 flex items-center"><i class="ri-eye-off-line mr-1"></i> לא נראה לציבור</span>'; 
                            ?>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t mt-8 flex justify-between">
                        <div>
                            <a href="landing_pages.php" class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                חזרה לרשימה
                            </a>
                        </div>
                        
                        <div class="flex space-x-3 space-x-reverse">
                            <a href="landing_pages.php?delete=<?php echo $landingPageId; ?>" 
                               class="inline-flex items-center px-4 py-2.5 border border-red-300 text-red-700 rounded-md hover:bg-red-50 transition-colors" 
                               data-confirm="האם אתה בטוח שברצונך למחוק דף נחיתה זה?">
                                <i class="ri-delete-bin-line ml-1"></i>
                                מחק דף
                            </a>
                            
                            <button type="submit" class="px-6 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center">
                                <i class="ri-save-line ml-1"></i>
                                שמור שינויים
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Right column - Info -->
        <div class="md:col-span-1">
            <!-- Page Info Card -->
            <div class="bg-gray-50 rounded-lg border p-5 mb-6">
                <h3 class="font-medium text-gray-700 mb-4 pb-2 border-b flex items-center">
                    <i class="ri-file-info-line ml-1 text-gray-500"></i>
                    פרטי דף נחיתה
                </h3>
                
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">תאריך יצירה:</dt>
                        <dd class="font-medium text-gray-900"><?php echo formatHebrewDate($landingPage['created_at']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">עדכון אחרון:</dt>
                        <dd class="font-medium text-gray-900"><?php echo formatHebrewDate($landingPage['updated_at']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">תבנית:</dt>
                        <dd class="font-medium text-gray-900"><?php echo htmlspecialchars($landingPage['template_name'] ?? 'תבנית מותאמת אישית'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">כתובת דף:</dt>
                        <dd class="font-medium text-blue-600 truncate">
                            <a href="<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($slug); ?>" target="_blank" class="hover:underline" dir="ltr">/<?php echo htmlspecialchars($slug); ?></a>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Performance Card -->
            <div class="bg-blue-50 rounded-lg border p-5">
                <h3 class="font-medium text-blue-700 mb-4 pb-2 border-b border-blue-200 flex items-center">
                    <i class="ri-line-chart-line ml-1"></i>
                    ביצועים
                </h3>
                
                <div class="text-center py-4">
                    <div class="text-3xl font-bold text-blue-700 mb-1"><?php echo $subscribersCount; ?></div>
                    <div class="text-sm text-blue-600">מנויים שנאספו</div>
                    
                    <?php if ($subscribersCount > 0): ?>
                        <a href="subscribers.php?landing_page=<?php echo $landingPageId; ?>" class="inline-flex items-center mt-3 text-sm text-blue-800 font-medium hover:underline">
                            <i class="ri-user-line ml-1"></i>
                            צפה במנויים מדף זה
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="border-t border-blue-200 pt-4 mt-2">
                    <a href="reports.php?landing_page=<?php echo $landingPageId; ?>" class="flex justify-center items-center text-sm text-blue-700 font-medium hover:text-blue-800">
                        <i class="ri-bar-chart-2-line ml-1"></i>
                        צפה בדוחות מפורטים
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to copy URL to clipboard
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    
    // Show a temporary message
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="ri-check-line"></i>';
    setTimeout(() => {
        button.innerHTML = originalHtml;
    }, 2000);
}
</script>

<?php include_once 'template/footer.php'; ?>