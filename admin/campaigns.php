<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'קמפיינים';
$pageDescription = 'ניהול קמפיינים ודיוורים';
$primaryAction = [
    'url' => 'campaign_create.php',
    'text' => 'צור קמפיין חדש',
    'icon' => 'ri-mail-add-line'
];

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Handle delete campaign
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $campaignId = (int)$_GET['delete'];
    
    // Verify the campaign belongs to this user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM campaigns WHERE id = ? AND user_id = ?");
    $stmt->execute([$campaignId, $userId]);
    $canDelete = $stmt->fetchColumn();
    
    if ($canDelete) {
        $stmt = $pdo->prepare("DELETE FROM campaigns WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$campaignId, $userId])) {
            // Also delete associated stats
            $pdo->prepare("DELETE FROM campaign_stats WHERE campaign_id = ?")->execute([$campaignId]);
            $_SESSION['success'] = 'הקמפיין נמחק בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת מחיקת הקמפיין';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה למחוק קמפיין זה';
    }
    
    // Redirect to prevent resubmission
    redirect('campaigns.php');
}

// Initialize paging variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

// Build SQL query with filters
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM campaign_stats WHERE campaign_id = c.id) as recipient_count,
               (SELECT COUNT(*) FROM campaign_stats WHERE campaign_id = c.id AND is_opened = 1) as opened_count
         FROM campaigns c
         WHERE c.user_id = ?";
$params = [$userId];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.subject LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Apply status filter
if (!empty($status) && in_array($status, ['draft', 'scheduled', 'sent'])) {
    $sql .= " AND c.status = ?";
    $params[] = $status;
}

// Count total results for pagination
$countSql = str_replace("c.*, \n               (SELECT COUNT(*) FROM campaign_stats WHERE campaign_id = c.id) as recipient_count,\n               (SELECT COUNT(*) FROM campaign_stats WHERE campaign_id = c.id AND is_opened = 1) as opened_count", "COUNT(*)", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get filtered campaigns with pagination
$sql .= " ORDER BY c.created_at DESC LIMIT $offset, $perPage";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $campaigns = $stmt->fetchAll();
} catch (PDOException $e) {
    // If the table doesn't exist yet
    $campaigns = [];
    
    // Create the tables if they don't exist
    try {
        // Create campaigns table if it doesn't exist (though it should exist already)
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `campaigns` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `name` varchar(255) NOT NULL,
              `subject` varchar(255) NOT NULL,
              `content` longtext NOT NULL,
              `status` enum('draft','scheduled','sent') DEFAULT 'draft',
              `scheduled_at` datetime DEFAULT NULL,
              `sent_at` datetime DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        
        // Create campaign_stats table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `campaign_stats` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `campaign_id` int(11) NOT NULL,
              `subscriber_id` int(11) NOT NULL,
              `is_sent` tinyint(1) DEFAULT '0',
              `is_opened` tinyint(1) DEFAULT '0',
              `is_clicked` tinyint(1) DEFAULT '0',
              `sent_at` datetime DEFAULT NULL,
              `opened_at` datetime DEFAULT NULL,
              `clicked_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `campaign_id` (`campaign_id`),
              KEY `subscriber_id` (`subscriber_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (PDOException $tableError) {
        $_SESSION['error'] = 'אירעה שגיאה בעת יצירת טבלאות הקמפיינים: ' . $tableError->getMessage();
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
?>

<!-- Filters -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form action="campaigns.php" method="get" class="flex flex-wrap items-end gap-4">
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">חיפוש</label>
            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="חפש לפי שם או נושא">
        </div>
        
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">סטטוס</label>
            <select id="status" name="status" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">כל הסטטוסים</option>
                <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>טיוטה</option>
                <option value="scheduled" <?php echo $status === 'scheduled' ? 'selected' : ''; ?>>מתוזמן</option>
                <option value="sent" <?php echo $status === 'sent' ? 'selected' : ''; ?>>נשלח</option>
            </select>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="ri-filter-line ml-1"></i>
                סנן
            </button>
            
            <a href="campaigns.php" class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="ri-refresh-line ml-1"></i>
                נקה
            </a>
        </div>
    </form>
</div>

<!-- Campaigns List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <div>
            <h2 class="text-xl font-medium">רשימת קמפיינים</h2>
            <p class="text-gray-500 text-sm mt-1">יצירה וניהול קמפיינים לדיוור</p>
        </div>
        <div>
            <a href="campaign_create.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <i class="ri-add-line ml-1"></i>
                צור קמפיין חדש
            </a>
        </div>
    </div>
    
    <?php if (empty($campaigns)): ?>
    <div class="p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
            <i class="ri-mail-line text-indigo-600 text-3xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">עדיין אין לך קמפיינים</h3>
        <p class="text-gray-500 max-w-md mx-auto mb-6">קמפיינים מאפשרים לך לשלוח הודעות לרשימות התפוצה שלך, לתזמן שליחת דיוורים ולעקוב אחר ביצועי המשלוח.</p>
        <div class="mt-4">
            <a href="campaign_create.php" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                <i class="ri-add-line ml-1"></i>
                צור קמפיין ראשון
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">שם</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">נושא</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">סטטוס</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">שיעור פתיחה</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">תאריך</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">פעולות</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($campaigns as $campaign): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">
                            <?php echo htmlspecialchars($campaign['name']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                        <?php echo htmlspecialchars($campaign['subject']); ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($campaign['status'] === 'draft'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="ri-draft-line ml-1"></i>
                            טיוטה
                        </span>
                        <?php elseif ($campaign['status'] === 'scheduled'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="ri-time-line ml-1"></i>
                            מתוזמן לשליחה
                            <span class="mr-1"><?php echo date('d/m/Y H:i', strtotime($campaign['scheduled_at'])); ?></span>
                        </span>
                        <?php elseif ($campaign['status'] === 'sent'): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="ri-check-line ml-1"></i>
                            נשלח
                            <span class="mr-1"><?php echo date('d/m/Y H:i', strtotime($campaign['sent_at'])); ?></span>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php 
                        if ($campaign['recipient_count'] > 0 && $campaign['status'] === 'sent') {
                            $openRate = round(($campaign['opened_count'] / $campaign['recipient_count']) * 100);
                            $color = 'bg-gray-200';
                            if ($openRate >= 30) $color = 'bg-green-500';
                            elseif ($openRate >= 15) $color = 'bg-yellow-500';
                            else $color = 'bg-red-500';
                        ?>
                        <div class="flex items-center">
                            <div class="ml-2 w-full bg-gray-200 rounded-full h-2.5 max-w-[100px]">
                                <div class="<?php echo $color; ?> h-2.5 rounded-full" style="width: <?php echo $openRate; ?>%"></div>
                            </div>
                            <span><?php echo $openRate; ?>%</span>
                        </div>
                        <div class="text-gray-500 text-xs mt-1">
                            <?php echo $campaign['opened_count']; ?> מתוך <?php echo $campaign['recipient_count']; ?>
                        </div>
                        <?php } else { ?>
                        <span class="text-gray-400">אין נתונים</span>
                        <?php } ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                        <?php 
                        if ($campaign['status'] === 'sent' && !empty($campaign['sent_at'])) {
                            echo date('d/m/Y H:i', strtotime($campaign['sent_at']));
                        } elseif ($campaign['status'] === 'scheduled' && !empty($campaign['scheduled_at'])) {
                            echo date('d/m/Y H:i', strtotime($campaign['scheduled_at']));
                        } else {
                            echo date('d/m/Y H:i', strtotime($campaign['created_at']));
                        }
                        ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex space-s-2">
                            <?php if ($campaign['status'] === 'draft'): ?>
                            <a href="campaign_edit.php?id=<?php echo $campaign['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="ערוך">
                                <i class="ri-edit-line text-lg"></i>
                            </a>
                            <?php else: ?>
                            <a href="campaign_view.php?id=<?php echo $campaign['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="צפה">
                                <i class="ri-eye-line text-lg"></i>
                            </a>
                            <?php endif; ?>
                            
                            <a href="campaign_duplicate.php?id=<?php echo $campaign['id']; ?>" class="text-green-600 hover:text-green-900 mr-2" title="שכפל">
                                <i class="ri-file-copy-line text-lg"></i>
                            </a>
                            
                            <?php if ($campaign['status'] === 'draft'): ?>
                            <a href="#" class="text-red-600 hover:text-red-900 mr-2 delete-item" data-id="<?php echo $campaign['id']; ?>" data-name="<?php echo htmlspecialchars($campaign['name']); ?>" title="מחק">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($campaign['status'] === 'sent'): ?>
                            <a href="campaign_report.php?id=<?php echo $campaign['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-2" title="דוח ביצועים">
                                <i class="ri-bar-chart-line text-lg"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
        <div class="flex-1 flex justify-between items-center">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                הקודם
            </a>
            <?php else: ?>
            <button disabled class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white">
                הקודם
            </button>
            <?php endif; ?>
            
            <div class="hidden md:block">
                <span class="text-sm text-gray-700">
                    מציג <span class="font-medium"><?php echo $offset + 1; ?></span> עד <span class="font-medium"><?php echo min($offset + $perPage, $totalCount); ?></span> מתוך <span class="font-medium"><?php echo $totalCount; ?></span> תוצאות
                </span>
            </div>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                הבא
            </a>
            <?php else: ?>
            <button disabled class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-300 bg-white">
                הבא
            </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Delete confirmation modal -->
<div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75" id="modalOverlay"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ri-error-warning-line text-red-600 text-xl"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            מחיקת קמפיין
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                האם אתה בטוח שברצונך למחוק את הקמפיין "<span id="campaignName"></span>"?<br>
                                פעולה זו לא ניתנת לביטול ותוביל למחיקת כל נתוני הקמפיין.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmDelete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    מחק
                </a>
                <button type="button" id="cancelDelete" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:mr-3 sm:w-auto sm:text-sm">
                    בטל
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete confirmation modal
        const deleteModal = document.getElementById('deleteModal');
        const modalOverlay = document.getElementById('modalOverlay');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDelete = document.getElementById('confirmDelete');
        const campaignName = document.getElementById('campaignName');
        
        // Open delete modal
        document.querySelectorAll('.delete-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                // Set campaign name and delete URL
                campaignName.textContent = name;
                confirmDelete.href = 'campaigns.php?delete=' + id;
                
                // Show modal
                deleteModal.classList.remove('hidden');
            });
        });
        
        // Close modal events
        [modalOverlay, cancelDelete].forEach(item => {
            item.addEventListener('click', function() {
                deleteModal.classList.add('hidden');
            });
        });
    });
</script>

<?php include_once 'template/footer.php'; ?> 