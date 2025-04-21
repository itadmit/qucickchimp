<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'ניהול לידים';
$pageDescription = 'צפייה וניהול של לידים במערכת';
$primaryAction = [
    'url' => 'subscriber_create.php',
    'text' => 'הוסף ליד חדש',
    'icon' => 'ri-user-add-line'
];

// Include header
include_once 'template/header.php';

/**
 * פונקציית עזר להסרת פרמטר מכתובת URL
 * 
 * @param string $url כתובת ה-URL
 * @param string $param הפרמטר להסרה
 * @return string כתובת ה-URL החדשה ללא הפרמטר
 */
function remove_query_param($url, $param) {
    $parts = parse_url($url);
    if (!isset($parts['query'])) {
        return $url;
    }
    
    parse_str($parts['query'], $query);
    unset($query[$param]);
    
    $parts['query'] = http_build_query($query);
    
    return $parts['path'] . 
           (!empty($parts['query']) ? '?' . $parts['query'] : '') . 
           (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Handle delete subscriber
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $subscriberId = (int)$_GET['delete'];
    
    // Verify the subscriber belongs to this user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE id = ? AND user_id = ?");
    $stmt->execute([$subscriberId, $userId]);
    $canDelete = $stmt->fetchColumn();
    
    if ($canDelete) {
        $stmt = $pdo->prepare("DELETE FROM subscribers WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$subscriberId, $userId])) {
            $_SESSION['success'] = 'הליד נמחק בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת מחיקת הליד';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה למחוק ליד זה';
    }
    
    // Redirect to prevent resubmission
    redirect('subscribers.php');
}

// Initialize paging variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$landingPage = isset($_GET['landing_page']) ? (int)$_GET['landing_page'] : 0;
$isSubscribed = isset($_GET['is_subscribed']) ? (int)$_GET['is_subscribed'] : -1;
$tag = isset($_GET['tag']) ? trim($_GET['tag']) : '';

// הצגת הודעה אם מופעל סינון לפי תגית
$tagFilterMessage = '';
if (!empty($tag)) {
    $tagFilterMessage = '<div class="mb-3 flex justify-between items-center">
        <div class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-sm inline-flex items-center">
            <i class="ri-price-tag-3-line ml-1"></i>
            מסנן לפי תגית: <span class="font-bold mx-1">' . htmlspecialchars($tag) . '</span>
            <a href="' . remove_query_param($_SERVER['REQUEST_URI'], 'tag') . '" class="mr-2 text-purple-700 hover:text-purple-900">
                <i class="ri-close-circle-line"></i>
            </a>
        </div>
    </div>';
}

// Build SQL query with filters
$sql = "SELECT s.*, l.title as landing_page_title 
        FROM subscribers s
        LEFT JOIN landing_pages l ON s.landing_page_id = l.id
        WHERE s.user_id = ?";
$params = [$userId];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (s.email LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.phone LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Apply landing page filter
if ($landingPage > 0) {
    $sql .= " AND s.landing_page_id = ?";
    $params[] = $landingPage;
}

// Apply tag filter
if (!empty($tag)) {
    $sql .= " AND s.custom_fields LIKE ?";
    $params[] = "%$tag%";
}

// Apply subscription status filter
if ($isSubscribed == 0 || $isSubscribed == 1) {
    $sql .= " AND s.is_subscribed = ?";
    $params[] = $isSubscribed;
}

// Count total results for pagination
$countSql = str_replace("s.*, l.title as landing_page_title", "COUNT(*)", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get filtered subscribers with pagination
$sql .= " ORDER BY s.created_at DESC LIMIT $offset, $perPage";
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

// Get landing pages for filter dropdown
try {
    $stmt = $pdo->prepare("SELECT id, title FROM landing_pages WHERE user_id = ? ORDER BY title");
    $stmt->execute([$userId]);
    $landingPages = $stmt->fetchAll();
} catch (PDOException $e) {
    $landingPages = [];
}
?>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <form action="" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0">
        <div class="w-full md:w-1/4 px-2">
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
        
        <div class="w-full md:w-1/5 px-2">
            <label for="landing_page" class="block text-sm font-medium text-gray-700 mb-1">דף נחיתה</label>
            <select id="landing_page" name="landing_page" class="block w-full py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="0">הכל</option>
                <?php foreach ($landingPages as $page): ?>
                    <option value="<?php echo $page['id']; ?>" <?php if ($landingPage == $page['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($page['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="w-full md:w-1/5 px-2">
            <label for="tag" class="block text-sm font-medium text-gray-700 mb-1">תגית</label>
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="ri-price-tag-3-line text-gray-400"></i>
                </div>
                <input type="text" id="tag" name="tag" value="<?php echo htmlspecialchars($tag); ?>" 
                       class="block w-full pr-10 py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                       placeholder="סינון לפי תגית">
            </div>
        </div>
        
        <div class="w-full md:w-1/6 px-2">
            <label for="is_subscribed" class="block text-sm font-medium text-gray-700 mb-1">סטטוס</label>
            <select id="is_subscribed" name="is_subscribed" class="block w-full py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="-1" <?php if ($isSubscribed == -1) echo 'selected'; ?>>הכל</option>
                <option value="1" <?php if ($isSubscribed == 1) echo 'selected'; ?>>פעיל</option>
                <option value="0" <?php if ($isSubscribed == 0) echo 'selected'; ?>>מבוטל</option>
            </select>
        </div>
        
        <div class="w-full md:w-auto px-2 flex space-x-2">
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                <i class="ri-filter-line ml-1"></i>
                סינון
            </button>
            <a href="subscribers.php" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                <i class="ri-refresh-line ml-1"></i>
                איפוס
            </a>
        </div>
    </form>
</div>

<?php 
// הצג את הודעת הסינון לפי תגית אם קיימת
if (!empty($tagFilterMessage)) {
    echo $tagFilterMessage;
}
?>

<!-- Subscribers List -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b">
        <div>
            <h2 class="text-lg font-medium">רשימת לידים</h2>
            <p class="text-sm text-gray-500">סה"כ <?php echo $totalCount; ?> לידים</p>
        </div>
        
        <?php if (!empty($subscribers)): ?>
        <div class="flex space-x-3">
            <a href="export_subscribers.php" class="flex items-center text-sm text-green-600 hover:text-green-700">
                <i class="ri-file-excel-line ml-1"></i>
                ייצוא לאקסל
            </a>
            
            <a href="import_subscribers.php" class="flex items-center text-sm text-blue-600 hover:text-blue-700">
                <i class="ri-upload-line ml-1"></i>
                ייבוא לידים
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($subscribers)): ?>
        <div class="p-8 text-center">
            <i class="ri-user-follow-line text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-700 mb-2">אין לידים עדיין</h3>
            <p class="text-gray-500 mb-6">כאן תוכל לראות את כל הלידים שלך לאחר שיירשמו באמצעות דפי הנחיתה שלך.</p>
            <div class="flex justify-center space-x-4">
                <a href="subscriber_create.php" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    <i class="ri-user-add-line ml-1"></i>
                    הוסף ליד ידנית
                </a>
                <a href="landing_pages.php" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                    <i class="ri-layout-line ml-1"></i>
                    צור דף נחיתה
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
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">דף נחיתה</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">תגיות</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">סטטוס</th>
                        <th class="px-4 py-3 text-right text-sm font-medium text-gray-500">תאריך הצטרפות</th>
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
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <?php if (!empty($subscriber['landing_page_title'])): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                        <i class="ri-layout-line ml-1"></i>
                                        <?php echo htmlspecialchars($subscriber['landing_page_title']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">---</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if (!empty($subscriber['tags'])): ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach ($subscriber['tags'] as $tag): ?>
                                            <a href="?tag=<?php echo urlencode($tag); ?>" 
                                               class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                                                <i class="ri-price-tag-3-line ml-1 text-xs"></i>
                                                <?php echo htmlspecialchars($tag); ?>
                                            </a>
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
                                <?php echo formatHebrewDate($subscriber['created_at']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                <div class="flex items-center space-x-3">
                                    <a href="subscriber_edit.php?id=<?php echo $subscriber['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="ערוך">
                                        <i class="ri-edit-line"></i>
                                    </a>
                                    <a href="subscribers.php?delete=<?php echo $subscriber['id']; ?>" 
                                       class="text-red-600 hover:text-red-900" 
                                       title="מחק"
                                       data-confirm="האם אתה בטוח שברצונך למחוק ליד זה?">
                                        <i class="ri-delete-bin-line"></i>
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
                                    $prevPageLink = 'subscribers.php?' . http_build_query($queryParams);
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
                                    $pageLink = 'subscribers.php?' . http_build_query($queryParams);
                                    
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
                                    $nextPageLink = 'subscribers.php?' . http_build_query($queryParams);
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

<?php include_once 'template/footer.php'; ?>