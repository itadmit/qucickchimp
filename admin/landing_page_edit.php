<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'עריכת דף נחיתה';
$pageDescription = 'ערוך את פרטי דף הנחיתה';

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
    $customSlug = sanitize($_POST['slug'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate input
    if (empty($title)) {
        $error = 'כותרת היא שדה חובה';
    } else {
        // Use custom slug if provided, otherwise generate from title if title changed
        if (!empty($customSlug)) {
            // Make sure slug is URL-friendly
            $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $customSlug)));
        } elseif ($title != $landingPage['title']) {
            $slug = generateSlug($pdo, $title, 'landing_pages', 'slug', $landingPageId);
        }
        
        // Update landing page
        try {
            $stmt = $pdo->prepare("
                UPDATE landing_pages 
                SET title = ?, description = ?, slug = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                $title,
                $description,
                $slug,
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
        
        <div class="flex">
            <a href="<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($slug); ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800">
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
                               class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    
                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">תיאור</label>
                        <textarea id="description" name="description" rows="3" 
                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"><?php echo htmlspecialchars($description); ?></textarea>
                        <p class="mt-1 text-sm text-gray-500">תיאור קצר של מטרת דף הנחיתה (לשימוש פנימי בלבד).</p>
                    </div>
                    
                    <!-- URL Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">כתובת URL</label>
                        <div class="flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                                <?php echo APP_URL; ?>/landing/
                            </span>
                            <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($slug); ?>"
                                   class="border-gray-300 flex-1 block w-full rounded-none rounded-l-md focus:ring-purple-500 focus:border-purple-500 border-l-0"
                                   dir="ltr">
                        </div>
                        <p class="mt-1 text-sm text-gray-500">השאר ריק כדי ליצור באופן אוטומטי מהכותרת.</p>
                    </div>
                    
                    <!-- Status -->
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" 
                               <?php if ($isActive) echo 'checked'; ?>
                               class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                        <label for="is_active" class="mr-2 block text-sm text-gray-700">
                            דף נחיתה פעיל (גלוי לציבור)
                        </label>
                    </div>
                    
                    <div class="pt-5 border-t flex justify-between">
                        <div>
                            <a href="landing_pages.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                                חזרה לרשימה
                            </a>
                            
                            <a href="landing_pages.php?delete=<?php echo $landingPageId; ?>" 
                               class="mr-2 px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50" 
                               data-confirm="האם אתה בטוח שברצונך למחוק דף נחיתה זה?">
                                <i class="ri-delete-bin-line ml-1"></i>
                                מחק דף
                            </a>
                        </div>
                        
                        <div>
                            <a href="../customizer/index.php?id=<?php echo $landingPageId; ?>" class="mr-2 px-4 py-2 border border-indigo-300 text-indigo-700 rounded-md hover:bg-indigo-50">
                                <i class="ri-paint-brush-line ml-1"></i>
                                ערוך עיצוב
                            </a>
                            
                            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
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
            <div class="bg-gray-50 rounded-lg border p-4 mb-6">
                <h3 class="font-medium text-gray-700 mb-3">פרטי דף נחיתה</h3>
                
                <dl class="space-y-2 text-sm">
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
                        <dt class="text-gray-500">סטטוס:</dt>
                        <dd>
                            <?php if ($isActive): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="ri-checkbox-circle-line ml-1"></i>
                                    פעיל
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <i class="ri-close-circle-line ml-1"></i>
                                    לא פעיל
                                </span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
            
            <!-- Performance Card -->
            <div class="bg-blue-50 rounded-lg border p-4">
                <h3 class="font-medium text-blue-700 mb-3">
                    <i class="ri-line-chart-line ml-1"></i>
                    ביצועים
                </h3>
                
                <div class="text-center py-4">
                    <div class="text-3xl font-bold text-blue-700 mb-1"><?php echo $subscribersCount; ?></div>
                    <div class="text-sm text-blue-600">מנויים שנאספו</div>
                    
                    <?php if ($subscribersCount > 0): ?>
                        <a href="subscribers.php?landing_page=<?php echo $landingPageId; ?>" class="inline-block mt-3 text-sm text-blue-800 font-medium hover:underline">
                            צפה במנויים מדף זה
                            <i class="ri-arrow-left-line mr-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
                
                <div class="border-t border-blue-200 pt-3 mt-2">
                    <a href="reports.php?landing_page=<?php echo $landingPageId; ?>" class="flex justify-center items-center text-sm text-blue-700 font-medium hover:text-blue-800">
                        <i class="ri-bar-chart-2-line ml-1"></i>
                        צפה בדוחות מפורטים
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'template/footer.php'; ?>