<?php
require_once '../config/config.php';

// קבלת מזהה רשימה
$listId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = $_SESSION['user_id'] ?? 0;

// וידוא שהרשימה קיימת ושייכת למשתמש
try {
    $stmt = $pdo->prepare("SELECT * FROM contact_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $userId]);
    $list = $stmt->fetch();
    
    if (!$list) {
        $_SESSION['error'] = 'הרשימה המבוקשת לא נמצאה או שאין לך הרשאה לגשת אליה';
        redirect('contact_lists.php');
    }
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת פרטי הרשימה';
    redirect('contact_lists.php');
}

// Set page title
$pageTitle = 'חברי רשימה: ' . $list['name'];
$pageDescription = 'ניהול המנויים ברשימת ' . $list['name'];
$primaryAction = [
    'url' => 'add_to_list.php?id=' . $listId,
    'text' => 'הוסף ליד לרשימה',
    'icon' => 'ri-user-add-line'
];

// Include header
include_once 'template/header.php';

// Handle remove from list
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $subscriberId = (int)$_GET['remove'];
    
    // וידוא שהמנוי שייך למשתמש
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM subscribers s
        JOIN subscriber_lists sl ON s.id = sl.subscriber_id
        WHERE s.user_id = ? AND sl.subscriber_id = ? AND sl.list_id = ?
    ");
    $stmt->execute([$userId, $subscriberId, $listId]);
    $canRemove = $stmt->fetchColumn();
    
    if ($canRemove) {
        $stmt = $pdo->prepare("DELETE FROM subscriber_lists WHERE subscriber_id = ? AND list_id = ?");
        if ($stmt->execute([$subscriberId, $listId])) {
            $_SESSION['success'] = 'המנוי הוסר מהרשימה בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת הסרת המנוי מהרשימה';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה להסיר מנוי זה מהרשימה';
    }
    
    // Redirect to prevent resubmission
    redirect('list_members.php?id=' . $listId);
}

// Initialize paging variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;

// Build SQL query with filters
$sql = "SELECT s.*
        FROM subscribers s
        JOIN subscriber_lists sl ON s.id = sl.subscriber_id
        WHERE s.user_id = ? AND sl.list_id = ?";
$params = [$userId, $listId];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (s.email LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Apply subscription status filter
if ($status == 0 || $status == 1) {
    $sql .= " AND s.is_subscribed = ?";
    $params[] = $status;
}

// Count total results for pagination
$countSql = str_replace("s.*", "COUNT(*)", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get filtered subscribers with pagination
$sql .= " ORDER BY sl.created_at DESC LIMIT $offset, $perPage";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $subscribers = $stmt->fetchAll();
    
    // חלץ את התגיות מהשדות המותאמים של כל ליד
    foreach ($subscribers as $index => $subscriber) {
        $tags = [];
        if (!empty($subscriber['custom_fields'])) {
            $customFields = json_decode($subscriber['custom_fields'], true);
            if (isset($customFields['_form_tags'])) {
                // פיצול התגיות לפי פסיקים ושמירה במערך
                $tags = array_map('trim', explode(',', $customFields['_form_tags']));
            }
        }
        $subscribers[$index]['tags'] = $tags;
    }
} catch (PDOException $e) {
    // If the table doesn't exist yet
    $subscribers = [];
}
?>

<!-- List Info and Actions -->
<div class="bg-white rounded-lg shadow overflow-hidden mb-6">
    <div class="p-6 border-b flex flex-col md:flex-row md:justify-between md:items-center">
        <div class="mb-4 md:mb-0">
            <h2 class="text-xl font-medium mb-1"><?php echo htmlspecialchars($list['name']); ?></h2>
            <?php if (!empty($list['description'])): ?>
                <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($list['description']); ?></p>
            <?php endif; ?>
            <div class="mt-1 text-sm text-gray-500">
                סה"כ <?php echo $totalCount; ?> חברים ברשימה
            </div>
        </div>
        
        <div class="flex flex-wrap gap-3">
            <a href="add_to_list.php?id=<?php echo $listId; ?>" class="inline-flex items-center px-4 py-2 bg-purple-600 text-sm text-white rounded-md hover:bg-purple-700">
                <i class="ri-user-add-line ml-1"></i>
                הוסף ליד לרשימה
            </a>
            <a href="import_to_list.php?id=<?php echo $listId; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-sm text-white rounded-md hover:bg-blue-700">
                <i class="ri-upload-line ml-1"></i>
                ייבוא מנויים
            </a>
            <a href="export_list.php?id=<?php echo $listId; ?>" class="inline-flex items-center px-4 py-2 bg-green-600 text-sm text-white rounded-md hover:bg-green-700">
                <i class="ri-download-line ml-1"></i>
                ייצוא לאקסל
            </a>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0">
        <input type="hidden" name="id" value="<?php echo $listId; ?>">
        
        <div class="w-full md:w-1/2 px-2">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">חיפוש</label>
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="ri-search-line text-gray-400"></i>
                </div>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="block w-full pr-10 py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                       placeholder="חיפוש לפי אימייל, שם או טלפון">
            </div>
        </div>
        
        <div class="w-full md:w-1/4 px-2">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">סטטוס</label>
            <select id="status" name="status" class="block w-full py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="-1" <?php if ($status == -1) echo 'selected'; ?>>הכל</option>
                <option value="1" <?php if ($status == 1) echo 'selected'; ?>>פעיל</option>
                <option value="0" <?php if ($status == 0) echo 'selected'; ?>>מבוטל</option>
            </select>
        </div>
        
        <div class="w-full md:w-auto px-2 flex space-x-2">
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                <i class="ri-filter-line ml-1"></i>
                סינון
            </button>
            <a href="list_members.php?id=<?php echo $listId; ?>" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="ri-refresh-line ml-1"></i>
                איפוס
            </a>
        </div>
    </form>
</div>

<?php if (isset($_SESSION['success'])): ?>
<div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6 rounded-md">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="ri-checkbox-circle-line text-green-500 text-xl"></i>
        </div>
        <div class="mr-3">
            <p class="text-sm"><?php echo $_SESSION['success']; ?></p>
        </div>
    </div>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6 rounded-md">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="ri-error-warning-line text-red-500 text-xl"></i>
        </div>
        <div class="mr-3">
            <p class="text-sm"><?php echo $_SESSION['error']; ?></p>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Members List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b">
        <div>
            <h2 class="text-lg font-medium">חברי הרשימה</h2>
            <p class="text-sm text-gray-500">סה"כ <?php echo $totalCount; ?> מנויים ברשימה</p>
        </div>
    </div>
    
    <?php if (empty($subscribers)): ?>
        <div class="p-8 text-center">
            <i class="ri-user-follow-line text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-700 mb-2">אין מנויים ברשימה זו</h3>
            <p class="text-gray-500 mb-6">באפשרותך להוסיף מנויים לרשימה באמצעות הוספה ידנית או ייבוא.</p>
            <div class="flex justify-center space-x-4">
                <a href="add_to_list.php?id=<?php echo $listId; ?>" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    <i class="ri-user-add-line ml-1"></i>
                    הוסף ליד ידנית
                </a>
                <a href="import_to_list.php?id=<?php echo $listId; ?>" class="px-4 py-2 border border-indigo-300 text-indigo-700 rounded-md hover:bg-indigo-50">
                    <i class="ri-upload-line ml-1"></i>
                    ייבוא מנויים
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 w-12">#</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">ליד</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">תגיות</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">סטטוס</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">תאריך הוספה לרשימה</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500 w-24">פעולות</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($subscribers as $index => $subscriber): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-500"><?php echo $offset + $index + 1; ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                        <i class="ri-user-line text-gray-500"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php 
                                                echo !empty($subscriber['first_name']) ? 
                                                    htmlspecialchars($subscriber['first_name'] . ' ' . $subscriber['last_name']) : 
                                                    '---';
                                            ?>
                                        </div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($subscriber['email']); ?></div>
                                        <?php if (!empty($subscriber['phone'])): ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($subscriber['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if (!empty($subscriber['tags'])): ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($subscriber['tags'] as $tag): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <i class="ri-price-tag-3-line ml-1 text-xs"></i>
                                                <?php echo htmlspecialchars($tag); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-400">---</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($subscriber['is_subscribed']): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="ri-check-line ml-1"></i>
                                        פעיל
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="ri-close-line ml-1"></i>
                                        מבוטל
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <?php 
                                // קבלת תאריך ההוספה לרשימה
                                $stmt = $pdo->prepare("SELECT created_at FROM subscriber_lists WHERE subscriber_id = ? AND list_id = ?");
                                $stmt->execute([$subscriber['id'], $listId]);
                                $addDate = $stmt->fetchColumn();
                                echo formatHebrewDate($addDate); 
                                ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <div class="flex items-center justify-end space-x-reverse space-x-2">
                                    <a href="subscriber_edit.php?id=<?php echo $subscriber['id']; ?>" 
                                       class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center hover:bg-indigo-200 transition-colors" 
                                       title="ערוך">
                                        <i class="ri-edit-line text-indigo-600"></i>
                                    </a>
                                    <a href="list_members.php?id=<?php echo $listId; ?>&remove=<?php echo $subscriber['id']; ?>" 
                                       class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center hover:bg-red-200 transition-colors" 
                                       title="הסר מהרשימה"
                                       data-confirm="האם אתה בטוח שברצונך להסיר את <?php echo htmlspecialchars($subscriber['email']); ?> מהרשימה?">
                                        <i class="ri-user-unfollow-line text-red-600"></i>
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
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                מציג
                                <span class="font-medium"><?php echo $offset + 1; ?></span>
                                עד
                                <span class="font-medium"><?php echo min($offset + $perPage, $totalCount); ?></span>
                                מתוך
                                <span class="font-medium"><?php echo $totalCount; ?></span>
                                תוצאות
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <?php
                                $queryParams = $_GET;
                                
                                // Previous page link
                                if ($page > 1) {
                                    $queryParams['page'] = $page - 1;
                                    $prevPageLink = 'list_members.php?' . http_build_query($queryParams);
                                    echo '<a href="' . $prevPageLink . '" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Previous</span>
                                            <i class="ri-arrow-right-s-line"></i>
                                          </a>';
                                } else {
                                    echo '<span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                            <span class="sr-only">Previous</span>
                                            <i class="ri-arrow-right-s-line"></i>
                                          </span>';
                                }
                                
                                // Page number links
                                $range = 2;
                                for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++) {
                                    $queryParams['page'] = $i;
                                    $pageLink = 'list_members.php?' . http_build_query($queryParams);
                                    
                                    if ($i == $page) {
                                        echo '<span class="relative inline-flex items-center px-4 py-2 border border-purple-500 bg-purple-50 text-sm font-medium text-purple-600">
                                                ' . $i . '
                                              </span>';
                                    } else {
                                        echo '<a href="' . $pageLink . '" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                ' . $i . '
                                              </a>';
                                    }
                                }
                                
                                // Next page link
                                if ($page < $totalPages) {
                                    $queryParams['page'] = $page + 1;
                                    $nextPageLink = 'list_members.php?' . http_build_query($queryParams);
                                    echo '<a href="' . $nextPageLink . '" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                            <span class="sr-only">Next</span>
                                            <i class="ri-arrow-left-s-line"></i>
                                          </a>';
                                } else {
                                    echo '<span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                                            <span class="sr-only">Next</span>
                                            <i class="ri-arrow-left-s-line"></i>
                                          </span>';
                                }
                                ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- JavaScript for confirmation modals -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find all elements with a data-confirm attribute
    const confirmActions = document.querySelectorAll('[data-confirm]');
    
    confirmActions.forEach(function(element) {
        element.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
});
</script>

<div class="mt-6">
    <a href="contact_lists.php" class="inline-flex items-center text-purple-600 hover:text-purple-800">
        <i class="ri-arrow-right-line ml-1"></i>
        חזרה לרשימת הרשימות
    </a>
</div>

<?php include_once 'template/footer.php'; ?> 