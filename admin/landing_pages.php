<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'דפי נחיתה';
$pageDescription = 'צור וערוך דפי נחיתה לאיסוף לידים';
$primaryAction = [
    'url' => 'landing_page_create.php',
    'text' => 'צור דף נחיתה',
    'icon' => 'ri-layout-4-line'
];

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Handle delete landing page
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $landingPageId = (int)$_GET['delete'];
    
    // Verify the landing page belongs to this user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM landing_pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$landingPageId, $userId]);
    $canDelete = $stmt->fetchColumn();
    
    if ($canDelete) {
        $stmt = $pdo->prepare("DELETE FROM landing_pages WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$landingPageId, $userId])) {
            $_SESSION['success'] = 'דף הנחיתה נמחק בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת מחיקת דף הנחיתה';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה למחוק דף נחיתה זה';
    }
    
    // Redirect to prevent resubmission
    redirect('landing_pages.php');
}

// Handle toggle active status
if (isset($_GET['toggle_active']) && !empty($_GET['toggle_active'])) {
    $landingPageId = (int)$_GET['toggle_active'];
    
    // Verify the landing page belongs to this user
    $stmt = $pdo->prepare("SELECT id, is_active FROM landing_pages WHERE id = ? AND user_id = ?");
    $stmt->execute([$landingPageId, $userId]);
    $landingPage = $stmt->fetch();
    
    if ($landingPage) {
        $newStatus = $landingPage['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE landing_pages SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $landingPageId])) {
            $_SESSION['success'] = 'סטטוס דף הנחיתה עודכן בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת עדכון סטטוס דף הנחיתה';
        }
    } else {
        $_SESSION['error'] = 'דף הנחיתה לא נמצא או שאין לך הרשאה לערוך אותו';
    }
    
    // Redirect to prevent resubmission
    redirect('landing_pages.php');
}

// Check if we can create more landing pages
$canCreateNewPage = true;
try {
    if (hasReachedPlanLimits($pdo, $userId, 'landing_pages')) {
        $canCreateNewPage = false;
    }
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get landing pages
try {
    $stmt = $pdo->prepare("
        SELECT l.*, 
            (SELECT COUNT(*) FROM subscribers WHERE landing_page_id = l.id) as subscribers_count
        FROM landing_pages l
        WHERE l.user_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$userId]);
    $landingPages = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist
    $landingPages = [];
}

// Get templates for creating new pages
try {
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE is_active = 1");
    $stmt->execute();
    $templates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback to default templates if table doesn't exist
    $templates = [
        ['id' => 1, 'name' => 'בסיסי', 'thumbnail' => '../customizer/templates/basic/thumbnail.svg'],
        ['id' => 2, 'name' => 'עסקי', 'thumbnail' => '../customizer/templates/business/thumbnail.svg'],
        ['id' => 3, 'name' => 'מכירות', 'thumbnail' => '../customizer/templates/sales/thumbnail.svg']
    ];
}
?>

<!-- Plan Limit Warning -->
<?php if (!$canCreateNewPage): ?>
<div class="bg-yellow-50 border-r-4 border-yellow-400 p-4 mb-6 rounded-md">
    <div class="flex">
        <div class="mr-1">
            <i class="ri-error-warning-line text-yellow-500 text-2xl"></i>
        </div>
        <div class="mr-3">
            <h3 class="text-yellow-800 font-medium">הגעת למכסת דפי הנחיתה בחשבונך</h3>
            <p class="text-yellow-700">על מנת ליצור דפי נחיתה נוספים, אנא שדרג את החשבון שלך לתוכנית גבוהה יותר.</p>
            <a href="billing.php" class="inline-block mt-2 text-yellow-800 font-medium hover:underline">שדרג עכשיו <i class="ri-arrow-left-line mr-1"></i></a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Landing Pages Grid -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">דפי הנחיתה שלי</h2>
        <p class="text-gray-500 text-sm mt-1">צור ונהל דפי נחיתה לאיסוף לידים</p>
    </div>
    
    <?php if (empty($landingPages)): ?>
    <div class="p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-4">
            <i class="ri-layout-4-line text-purple-600 text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">עדיין אין לך דפי נחיתה</h3>
        <p class="text-gray-500 max-w-md mx-auto mb-6">דפי נחיתה מאפשרים לך ליצור דפים ייעודיים לאיסוף לידים ומנויים לרשימת התפוצה שלך.</p>
        <?php if ($canCreateNewPage): ?>
        <div class="mt-4">
            <a href="landing_page_create.php" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                <i class="ri-add-line ml-1"></i>
                צור דף נחיתה ראשון
            </a>
        </div>
        <?php else: ?>
        <div class="mt-4">
            <a href="billing.php" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                <i class="ri-vip-crown-line ml-1"></i>
                שדרג את החשבון
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
        <?php foreach ($landingPages as $page): ?>
        <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div class="relative">
                <?php if (!empty($page['thumbnail'])): ?>
                <img src="<?php echo htmlspecialchars($page['thumbnail']); ?>" alt="<?php echo htmlspecialchars($page['title']); ?>" class="w-full h-40 object-cover">
                <?php else: ?>
                <div class="w-full h-40 bg-gray-50 overflow-hidden relative">
                    <!-- מסגרת דמוי-מחשב מפושטת -->
                    <div class="absolute top-0 left-0 right-0 h-4 bg-gray-800 flex items-center px-1">
                        <div class="flex space-x-1 rtl:space-x-reverse">
                            <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-yellow-400"></div>
                            <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div>
                        </div>
                    </div>
                    
                    <!-- קונטיינר אייפרם -->
                    <div class="absolute top-4 left-0 right-0 bottom-0 overflow-hidden bg-white">
                        <div class="transform scale-[0.12] origin-top-left w-[800%] h-[600px]">
                            <iframe src="<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($page['slug']); ?>" 
                                class="w-full h-full border-0" 
                                style="pointer-events:none;" 
                                scrolling="no" 
                                loading="lazy"
                                title="תצוגה מקדימה של <?php echo htmlspecialchars($page['title']); ?>">
                            </iframe>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Status Badge -->
                <div class="absolute top-2 right-2">
                    <?php if ($page['is_active']): ?>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">
                        <i class="ri-checkbox-circle-line ml-1"></i>
                        פעיל
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        <i class="ri-error-warning-line ml-1"></i>
                        לא פעיל
                    </span>
                    <?php endif; ?>
                </div>
                
                <!-- Stats Badge -->
                <div class="absolute top-2 left-2">
                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800">
                        <i class="ri-user-follow-line ml-1"></i>
                        <?php echo $page['subscribers_count']; ?> לידים
                    </span>
                </div>
            </div>
            
            <div class="p-4">
                <div class="flex justify-between items-start mb-2">
                    <h3 class="font-bold text-lg text-gray-900 truncate"><?php echo htmlspecialchars($page['title']); ?></h3>
                </div>
                
                <p class="text-sm text-gray-500 mb-4 h-10 overflow-hidden">
                    <?php 
                    echo !empty($page['description']) ? 
                        htmlspecialchars(substr($page['description'], 0, 70)) . (strlen($page['description']) > 70 ? '...' : '') : 
                        'אין תיאור'; 
                    ?>
                </p>
                
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center">
                        <i class="ri-link ml-1 text-gray-400"></i>
                        <a href="<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($page['slug']); ?>" target="_blank" 
                           class="text-xs text-gray-500 hover:text-indigo-600 hover:underline truncate" dir="ltr">
                            /<?php echo htmlspecialchars($page['slug']); ?>
                        </a>
                    </div>
                    <div class="flex items-center">
                        <span class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($page['created_at'])); ?></span>
                        <i class="ri-calendar-line mr-1 text-gray-400"></i>
                    </div>
                </div>
                
                <div class="flex justify-between items-center pt-3 border-t">
                    <div class="flex items-center space-x-reverse space-x-2">
                        <a href="landing_page_edit.php?id=<?php echo $page['id']; ?>" class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center hover:bg-indigo-200 transition-colors" title="ערוך פרטים">
                            <i class="ri-pencil-line text-indigo-600"></i>
                        </a>
                        <a href="../customizer/index.php?id=<?php echo $page['id']; ?>" class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center hover:bg-purple-200 transition-colors" title="ערוך עיצוב">
                            <i class="ri-palette-line text-purple-600"></i>
                        </a>
                        <a href="<?php echo APP_URL; ?>/landing/<?php echo htmlspecialchars($page['slug']); ?>" target="_blank" class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center hover:bg-blue-200 transition-colors" title="צפה בדף">
                            <i class="ri-external-link-line text-blue-600"></i>
                        </a>
                        <a href="landing_pages.php?toggle_active=<?php echo $page['id']; ?>" class="w-8 h-8 rounded-full <?php echo $page['is_active'] ? 'bg-green-100 hover:bg-green-200' : 'bg-gray-100 hover:bg-gray-200'; ?> flex items-center justify-center transition-colors" title="<?php echo $page['is_active'] ? 'השבת' : 'הפעל'; ?>">
                            <i class="<?php echo $page['is_active'] ? 'ri-toggle-fill text-green-600' : 'ri-toggle-line text-gray-500'; ?>"></i>
                        </a>
                    </div>
                    <a href="landing_pages.php?delete=<?php echo $page['id']; ?>" class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center hover:bg-red-200 transition-colors" title="מחק" data-confirm="האם אתה בטוח שברצונך למחוק דף נחיתה זה?">
                        <i class="ri-delete-bin-line text-red-600"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Add New Card (if not at limit) -->
        <?php if ($canCreateNewPage): ?>
        <div class="border border-dashed rounded-lg overflow-hidden flex items-center justify-center h-64">
            <a href="landing_page_create.php" class="text-center p-5 hover:bg-gray-50 rounded-lg transition-colors">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-100 rounded-full mb-3">
                    <i class="ri-file-add-line text-purple-600 text-2xl"></i>
                </div>
                <h3 class="font-medium text-gray-900 mb-1">צור דף נחיתה חדש</h3>
                <p class="text-gray-500 text-sm">התחל לאסוף לידים חדשים</p>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Templates Section -->
<?php if ($canCreateNewPage && !empty($templates)): ?>
<div class="mt-8 bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">תבניות זמינות</h2>
        <p class="text-gray-500 text-sm mt-1">בחר תבנית ליצירת דף נחיתה חדש</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
        <?php foreach ($templates as $template): ?>
        <div class="border rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <div class="relative">
                <?php if (!empty($template['thumbnail'])): ?>
                <img src="<?php echo htmlspecialchars($template['thumbnail']); ?>" alt="<?php echo htmlspecialchars($template['name']); ?>" class="w-full h-40 object-cover">
                <?php else: ?>
                <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                    <i class="ri-file-list-3-line text-4xl text-gray-400"></i>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4">
                <h3 class="font-bold text-lg text-gray-900 mb-2"><?php echo htmlspecialchars($template['name']); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo !empty($template['description']) ? htmlspecialchars($template['description']) : 'תבנית מוכנה ליצירת דף נחיתה מקצועי'; ?></p>
                
                <a href="landing_page_create.php?template=<?php echo $template['id']; ?>" class="inline-flex items-center text-purple-600 hover:text-purple-800 font-medium">
                    <span>השתמש בתבנית זו</span>
                    <i class="ri-arrow-left-line mr-1"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Tips Section -->
<div class="mt-8 bg-blue-50 border-blue-200 border rounded-lg p-6">
    <div class="flex">
        <div class="mr-4">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-100 rounded-full">
                <i class="ri-information-line text-blue-600 text-2xl"></i>
            </div>
        </div>
        <div>
            <h3 class="text-lg font-medium text-blue-800 mb-2">טיפים ליצירת דפי נחיתה אפקטיביים</h3>
            <ul class="text-blue-700 space-y-2">
                <li class="flex items-start">
                    <i class="ri-checkbox-circle-line ml-2 mt-1"></i>
                    <span>השתמש בכותרת ברורה שמעבירה את ההצעה העיקרית שלך</span>
                </li>
                <li class="flex items-start">
                    <i class="ri-checkbox-circle-line ml-2 mt-1"></i>
                    <span>הוסף תמונות ואלמנטים חזותיים שתומכים במסר שלך</span>
                </li>
                <li class="flex items-start">
                    <i class="ri-checkbox-circle-line ml-2 mt-1"></i>
                    <span>השתמש בטפסים קצרים עם מינימום שדות נדרשים</span>
                </li>
                <li class="flex items-start">
                    <i class="ri-checkbox-circle-line ml-2 mt-1"></i>
                    <span>הוסף חברתיות והמלצות כדי לבנות אמון</span>
                </li>
                <li class="flex items-start">
                    <i class="ri-checkbox-circle-line ml-2 mt-1"></i>
                    <span>ודא שכפתור "שליחה" בולט וברור</span>
                </li>
            </ul>
            <a href="#" class="inline-block mt-3 text-blue-800 font-medium hover:underline">קרא עוד על יצירת דפי נחיתה <i class="ri-arrow-left-line mr-1"></i></a>
        </div>
    </div>
</div>

<?php include_once 'template/footer.php'; ?>