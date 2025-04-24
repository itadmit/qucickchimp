<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'אוטומציות';
$pageDescription = 'ניהול תהליכים אוטומטיים עבור לידים';
$primaryAction = [
    'url' => 'automation_create.php',
    'text' => 'צור אוטומציה חדשה',
    'icon' => 'ri-add-line'
];

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Handle delete automation
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $automationId = (int)$_GET['delete'];
    
    // Verify the automation belongs to this user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM automations WHERE id = ? AND user_id = ?");
    $stmt->execute([$automationId, $userId]);
    $canDelete = $stmt->fetchColumn();
    
    if ($canDelete) {
        $stmt = $pdo->prepare("DELETE FROM automations WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$automationId, $userId])) {
            $_SESSION['success'] = 'האוטומציה נמחקה בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת מחיקת האוטומציה';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה למחוק אוטומציה זו';
    }
    
    // Redirect to prevent resubmission
    redirect('automations.php');
}

// Handle toggle automation status
if (isset($_GET['toggle']) && !empty($_GET['toggle'])) {
    $automationId = (int)$_GET['toggle'];
    
    // Verify the automation belongs to this user
    $stmt = $pdo->prepare("SELECT id, status FROM automations WHERE id = ? AND user_id = ?");
    $stmt->execute([$automationId, $userId]);
    $automation = $stmt->fetch();
    
    if ($automation) {
        // Toggle status
        $newStatus = ($automation['status'] === 'active') ? 'inactive' : 'active';
        
        $stmt = $pdo->prepare("UPDATE automations SET status = ? WHERE id = ?");
        if ($stmt->execute([$newStatus, $automationId])) {
            $_SESSION['success'] = 'סטטוס האוטומציה עודכן ל-' . ($newStatus === 'active' ? 'פעיל' : 'לא פעיל');
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת עדכון סטטוס האוטומציה';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה לעדכן אוטומציה זו';
    }
    
    // Redirect to prevent resubmission
    redirect('automations.php');
}

// Initialize paging variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build SQL query with filters
$sql = "SELECT a.*, 
               (SELECT COUNT(*) FROM automation_subscribers WHERE automation_id = a.id AND status = 'active') as active_subscribers
         FROM automations a
         WHERE a.user_id = ?";
$params = [$userId];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (a.name LIKE ? OR a.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Apply status filter
if (!empty($status) && in_array($status, ['active', 'inactive', 'draft'])) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}

// Count total results for pagination
$countSql = str_replace("a.*, \n               (SELECT COUNT(*) FROM automation_subscribers WHERE automation_id = a.id AND status = 'active') as active_subscribers", "COUNT(*)", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get filtered automations with pagination
$sql .= " ORDER BY a.created_at DESC LIMIT $offset, $perPage";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $automations = $stmt->fetchAll();
} catch (PDOException $e) {
    // If the table doesn't exist yet
    $automations = [];
    
    // Create the tables if they don't exist
    try {
        // Create automations table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `automations` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `name` varchar(255) NOT NULL,
              `description` text DEFAULT NULL,
              `status` enum('active','inactive','draft') NOT NULL DEFAULT 'draft',
              `trigger_type` enum('subscription','date','form_submission','inactivity') NOT NULL,
              `trigger_config` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create automation_steps table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `automation_steps` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `automation_id` int(11) NOT NULL,
              `step_order` int(11) NOT NULL DEFAULT 0,
              `action_type` enum('send_email','add_tag','remove_tag','move_to_list','update_field','wait') NOT NULL,
              `action_config` text DEFAULT NULL,
              `wait_days` int(11) DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `automation_id` (`automation_id`),
              KEY `step_order` (`step_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create automation_subscribers table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `automation_subscribers` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `automation_id` int(11) NOT NULL,
              `subscriber_id` int(11) NOT NULL,
              `current_step` int(11) DEFAULT NULL,
              `status` enum('active','completed','stopped') NOT NULL DEFAULT 'active',
              `last_action_at` timestamp NULL DEFAULT NULL,
              `next_action_at` timestamp NULL DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `automation_subscriber` (`automation_id`, `subscriber_id`),
              KEY `subscriber_id` (`subscriber_id`),
              KEY `next_action_at` (`next_action_at`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create automation_logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `automation_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `automation_id` int(11) NOT NULL,
              `subscriber_id` int(11) NOT NULL,
              `step_id` int(11) DEFAULT NULL,
              `action` varchar(255) NOT NULL,
              `status` enum('success','failed','pending') NOT NULL DEFAULT 'pending',
              `message` text DEFAULT NULL,
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `automation_id` (`automation_id`),
              KEY `subscriber_id` (`subscriber_id`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (PDOException $tableError) {
        $_SESSION['error'] = 'אירעה שגיאה בעת יצירת טבלאות האוטומציה: ' . $tableError->getMessage();
    }
}

// Get contact lists for filter dropdown
try {
    $stmt = $pdo->prepare("SELECT id, name FROM contact_lists WHERE user_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    $contactLists = $stmt->fetchAll();
} catch (PDOException $e) {
    $contactLists = [];
}

// Success and error messages
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!-- הודעות הצלחה ושגיאה -->
<?php if (isset($error) && !empty($error)): ?>
    <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6 rounded">
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

<?php if (isset($success) && !empty($success)): ?>
    <div class="bg-green-50 border-r-4 border-green-500 p-4 mb-6 rounded">
        <div class="flex">
            <div class="mr-1">
                <i class="ri-checkbox-circle-line text-green-500"></i>
            </div>
            <div class="mr-3">
                <p class="text-sm text-green-700"><?php echo $success; ?></p>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- מסננים -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0">
        <div class="w-full md:w-1/3 px-2">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">חיפוש</label>
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="ri-search-line text-gray-400"></i>
                </div>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="block w-full pr-10 py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                       placeholder="חיפוש לפי שם או תיאור">
            </div>
        </div>
        
        <div class="w-full md:w-1/3 px-2">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">סטטוס</label>
            <select id="status" name="status" class="block w-full py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="">כל הסטטוסים</option>
                <option value="active" <?php if ($status === 'active') echo 'selected'; ?>>פעיל</option>
                <option value="inactive" <?php if ($status === 'inactive') echo 'selected'; ?>>לא פעיל</option>
                <option value="draft" <?php if ($status === 'draft') echo 'selected'; ?>>טיוטה</option>
            </select>
        </div>
        
        <div class="w-full md:w-1/3 px-2 flex space-x-2 space-x-reverse">
            <button type="submit" class="py-2 px-4 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                <i class="ri-filter-line ml-1"></i>
                סנן
            </button>
            
            <a href="automations.php" class="py-2 px-4 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors">
                <i class="ri-refresh-line ml-1"></i>
                נקה
            </a>
        </div>
    </form>
</div>

<!-- טבלת אוטומציות -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-medium">רשימת האוטומציות שלך</h2>
            
            <a href="automation_create.php" class="inline-flex items-center bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                <i class="ri-add-line ml-1"></i>
                צור אוטומציה חדשה
            </a>
        </div>
    </div>
    
    <?php if (empty($automations)): ?>
        <div class="p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                <i class="ri-loop-right-line text-3xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">אין אוטומציות</h3>
            <p class="text-gray-500 mb-6">טרם יצרת אוטומציות. התחל לייעל את התקשורת עם הלידים שלך!</p>
            <a href="automation_create.php" class="inline-flex items-center bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors">
                <i class="ri-add-line ml-1"></i>
                צור אוטומציה ראשונה
            </a>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">שם האוטומציה</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">סוג טריגר</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">סטטוס</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">לידים פעילים</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">תאריך יצירה</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">פעולות</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($automations as $automation): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($automation['name']); ?></div>
                                <?php if (!empty($automation['description'])): ?>
                                    <div class="text-sm text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($automation['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                    $triggerTypes = [
                                        'subscription' => 'הצטרפות לרשימה',
                                        'date' => 'תאריך מסוים',
                                        'form_submission' => 'מילוי טופס',
                                        'inactivity' => 'חוסר פעילות'
                                    ];
                                    $triggerType = $triggerTypes[$automation['trigger_type']] ?? $automation['trigger_type'];
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?php echo $triggerType; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($automation['status'] === 'active'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                        פעיל
                                    </span>
                                <?php elseif ($automation['status'] === 'inactive'): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <span class="w-2 h-2 bg-gray-500 rounded-full mr-1"></span>
                                        לא פעיל
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                                        טיוטה
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo number_format($automation['active_subscribers']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo formatHebrewDate($automation['created_at']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                <div class="flex items-center space-x-3 space-x-reverse">
                                    <!-- עריכה -->
                                    <a href="automation_edit.php?id=<?php echo $automation['id']; ?>" class="text-purple-600 hover:text-purple-900" title="ערוך">
                                        <i class="ri-edit-line text-lg"></i>
                                    </a>
                                    
                                    <!-- הפעלה/השהייה -->
                                    <a href="automations.php?toggle=<?php echo $automation['id']; ?>" class="<?php echo $automation['status'] === 'active' ? 'text-gray-600 hover:text-gray-900' : 'text-green-600 hover:text-green-900'; ?>" 
                                       title="<?php echo $automation['status'] === 'active' ? 'השהה' : 'הפעל'; ?>"
                                       onclick="return confirm('האם אתה בטוח שברצונך <?php echo $automation['status'] === 'active' ? 'להשהות' : 'להפעיל'; ?> את האוטומציה הזו?')">
                                        <i class="<?php echo $automation['status'] === 'active' ? 'ri-pause-circle-line' : 'ri-play-circle-line'; ?> text-lg"></i>
                                    </a>
                                    
                                    <!-- מחיקה -->
                                    <a href="automations.php?delete=<?php echo $automation['id']; ?>" class="text-red-600 hover:text-red-900" title="מחק"
                                       onclick="return confirm('האם אתה בטוח שברצונך למחוק את האוטומציה הזו?')">
                                        <i class="ri-delete-bin-line text-lg"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 border-t">
                <nav class="flex justify-center">
                    <ul class="flex space-x-2 space-x-reverse">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 rounded-md border border-gray-300 hover:bg-gray-50 text-gray-700">
                                    הקודם
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 rounded-md border <?php echo $i == $page ? 'bg-purple-100 border-purple-500 text-purple-700' : 'border-gray-300 hover:bg-gray-50 text-gray-700'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" 
                                   class="px-3 py-1 rounded-md border border-gray-300 hover:bg-gray-50 text-gray-700">
                                    הבא
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include_once 'template/footer.php'; ?> 