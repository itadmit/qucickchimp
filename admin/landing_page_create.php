<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'יצירת דף נחיתה';
$pageDescription = 'צור דף נחיתה חדש';



// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Check if we can create more landing pages
$canCreateNewPage = true;
try {
    if (hasReachedPlanLimits($pdo, $userId, 'landing_pages')) {
        $_SESSION['error'] = 'הגעת למכסת דפי הנחיתה בחשבונך. שדרג את החשבון כדי ליצור דפים נוספים.';
        redirect('landing_pages.php');
    }
} catch (PDOException $e) {
    // Table might not exist yet
}

// Get template ID from URL if provided
$templateId = isset($_GET['template']) ? (int)$_GET['template'] : 1; // Default to first template

// Get template details
try {
    $stmt = $pdo->prepare("SELECT * FROM templates WHERE id = ?");
    $stmt->execute([$templateId]);
    $template = $stmt->fetch();
} catch (PDOException $e) {
    // If templates table doesn't exist, use default templates
    $templatePath = '';
    $templateName = '';
    
    switch ($templateId) {
        case 1:
            $templatePath = 'basic';
            $templateName = 'בסיסי';
            break;
        case 2:
            $templatePath = 'business';
            $templateName = 'עסקי';
            break;
        case 3:
            $templatePath = 'sales';
            $templateName = 'מכירות';
            break;
        default:
            $templatePath = 'basic';
            $templateName = 'בסיסי';
    }
    
    $template = [
        'id' => $templateId,
        'name' => $templateName,
        'html_structure' => '',
        'thumbnail' => '../customizer/templates/' . $templatePath . '/thumbnail.svg'
    ];
}

// Initialize variables
$title = '';
$description = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $templateId = (int)($_POST['template_id'] ?? 1);
    
    // Validate input
    if (empty($title)) {
        $error = 'כותרת היא שדה חובה';
    } else {
        // Generate random slug instead of using title
        $slug = generateRandomSlug($pdo);
        
        // Try to get template content if not provided
        $templateContent = '';
        if (isset($template['html_structure']) && !empty($template['html_structure'])) {
            $templateContent = $template['html_structure'];
        } else {
            // Get default template content from file
            $templatePath = '';
            switch ($templateId) {
                case 1:
                    $templatePath = 'basic';
                    break;
                case 2:
                    $templatePath = 'business';
                    break;
                case 3:
                    $templatePath = 'sales';
                    break;
                default:
                    $templatePath = 'basic';
            }
            
            $templateFilePath = '../customizer/templates/' . $templatePath . '/template.html';
            if (file_exists($templateFilePath)) {
                $templateContent = file_get_contents($templateFilePath);
            }
        }
        
        // Insert new landing page
        try {
            $stmt = $pdo->prepare("
                INSERT INTO landing_pages 
                (user_id, title, description, slug, template_id, content, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $isActive = 1; // Default to active
            
            $result = $stmt->execute([
                $userId,
                $title,
                $description,
                $slug,
                $templateId,
                $templateContent,
                $isActive
            ]);
            
            if ($result) {
                $landingPageId = $pdo->lastInsertId();
                $_SESSION['success'] = 'דף הנחיתה נוצר בהצלחה';
                
                // Redirect to customizer to edit the new page
                redirect('../customizer/index.php?id=' . $landingPageId);
            } else {
                $error = 'אירעה שגיאה בעת יצירת דף הנחיתה';
            }
        } catch (PDOException $e) {
            // Check if table exists
            if ($e->getCode() == '42S02') { // Table doesn't exist
                $error = 'טבלת דפי הנחיתה אינה קיימת במסד הנתונים';
            } else {
                $error = 'אירעה שגיאה בעת יצירת דף הנחיתה: ' . $e->getMessage();
            }
        }
    }
}

// Get all available templates
try {
    $stmt = $pdo->prepare("SELECT id, name, thumbnail FROM templates WHERE is_active = 1");
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
// Include header
include_once 'template/header.php';
?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">יצירת דף נחיתה חדש</h2>
        <p class="text-gray-500 text-sm mt-1">הזן את פרטי דף הנחיתה החדש</p>
    </div>
    
    <form method="POST" action="" class="p-6">
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
        
        <div class="grid grid-cols-1 gap-6">
            <!-- Title -->
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                    כותרת <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <p class="mt-1 text-sm text-gray-500">כותרת דף הנחיתה שלך.</p>
            </div>
            
            <div class="bg-blue-50 border-r-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="mr-1">
                        <i class="ri-information-line text-blue-500"></i>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm text-blue-700">כתובת ה-URL של דף הנחיתה תיווצר באופן אוטומטי ואקראי ולא תהיה ניתנת לשינוי. אם ברצונך להשתמש בכתובת מותאמת אישית, תוכל לחבר דומיין משלך מאוחר יותר.</p>
                    </div>
                </div>
            </div>
            
            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">תיאור</label>
                <textarea id="description" name="description" rows="3" 
                          class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"><?php echo htmlspecialchars($description); ?></textarea>
                <p class="mt-1 text-sm text-gray-500">תיאור קצר של מטרת דף הנחיתה (לשימוש פנימי בלבד).</p>
            </div>
            
            <!-- Hidden Template ID field -->
            <input type="hidden" name="template_id" value="<?php echo $templateId; ?>">
        </div>
        
        <!-- Template Preview -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-700 mb-3">תבנית נבחרת</h3>
            <div class="border rounded-lg overflow-hidden">
                <div class="p-4 bg-gray-50 border-b">
                    <div class="flex items-center">
                        <h4 class="font-medium text-gray-700"><?php echo htmlspecialchars($template['name'] ?? 'תבנית בסיסית'); ?></h4>
                        <a href="landing_pages.php" class="mr-auto text-sm text-purple-600 hover:text-purple-800">
                            בחר תבנית אחרת
                        </a>
                    </div>
                </div>
                <div class="p-4">
                    <div class="bg-white border rounded-md overflow-hidden">
                        <?php if (!empty($template['thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($template['thumbnail']); ?>" alt="<?php echo htmlspecialchars($template['name'] ?? 'תבנית'); ?>" class="w-full h-auto">
                        <?php else: ?>
                            <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                                <i class="ri-layout-4-line text-6xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-8 pt-5 border-t flex justify-between">
            <a href="landing_pages.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                ביטול
            </a>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                צור דף נחיתה
            </button>
        </div>
    </form>
</div>

<!-- All Templates -->
<div class="mt-8 bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">כל התבניות</h2>
        <p class="text-gray-500 text-sm mt-1">בחר תבנית אחרת לדף הנחיתה שלך</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
        <?php foreach ($templates as $tpl): ?>
        <div class="border rounded-lg overflow-hidden shadow-sm <?php echo $tpl['id'] == $templateId ? 'ring-2 ring-purple-500' : ''; ?> hover:shadow-md transition-shadow">
            <div class="relative">
                <?php if (!empty($tpl['thumbnail'])): ?>
                <img src="<?php echo htmlspecialchars($tpl['thumbnail']); ?>" alt="<?php echo htmlspecialchars($tpl['name']); ?>" class="w-full h-40 object-cover">
                <?php else: ?>
                <div class="w-full h-40 bg-gray-200 flex items-center justify-center">
                    <i class="ri-layout-4-line text-4xl text-gray-400"></i>
                </div>
                <?php endif; ?>
                
                <!-- Selected Badge -->
                <?php if ($tpl['id'] == $templateId): ?>
                <div class="absolute top-2 right-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <i class="ri-check-line ml-1"></i>
                        נבחר
                    </span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4">
                <h3 class="font-bold text-lg text-gray-900 mb-2"><?php echo htmlspecialchars($tpl['name']); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo !empty($tpl['description']) ? htmlspecialchars($tpl['description']) : 'תבנית מוכנה ליצירת דף נחיתה מקצועי'; ?></p>
                
                <?php if ($tpl['id'] == $templateId): ?>
                <span class="inline-flex items-center text-purple-600 font-medium">
                    <i class="ri-check-line ml-1"></i>
                    תבנית נבחרת
                </span>
                <?php else: ?>
                <a href="landing_page_create.php?template=<?php echo $tpl['id']; ?>" class="inline-flex items-center text-purple-600 hover:text-purple-800 font-medium">
                    <span>בחר תבנית זו</span>
                    <i class="ri-arrow-left-line mr-1"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include_once 'template/footer.php'; ?>