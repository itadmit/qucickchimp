<?php
ob_start(); // התחל לאגור פלט במקום לשלוח אותו מיד לדפדפן
require_once '../config/config.php';

// Set page title
$pageTitle = 'ניהול רשימות';
$pageDescription = 'צפייה וניהול של רשימות אנשי קשר';
$primaryAction = [
    'url' => 'contact_list_create.php',
    'text' => 'הוסף רשימה חדשה',
    'icon' => 'ri-add-line'
];

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Handle delete list
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $listId = (int)$_GET['delete'];
    
    // וידוא שהרשימה שייכת למשתמש הנוכחי
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_lists WHERE id = ? AND user_id = ?");
    $stmt->execute([$listId, $userId]);
    $canDelete = $stmt->fetchColumn();
    
    if ($canDelete) {
        // מחיקת הקשרים לרשימה
        $stmt = $pdo->prepare("DELETE FROM subscriber_lists WHERE list_id = ?");
        $stmt->execute([$listId]);
        
        // מחיקת הרשימה עצמה
        $stmt = $pdo->prepare("DELETE FROM contact_lists WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$listId, $userId])) {
            $_SESSION['success'] = 'הרשימה נמחקה בהצלחה';
        } else {
            $_SESSION['error'] = 'אירעה שגיאה בעת מחיקת הרשימה';
        }
    } else {
        $_SESSION['error'] = 'אין לך הרשאה למחוק רשימה זו';
    }
    
    // Redirect to prevent resubmission
    redirect('contact_lists.php');
}

// Initialize paging variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL query with filters
$sql = "SELECT cl.*, 
               (SELECT COUNT(*) FROM subscriber_lists sl WHERE sl.list_id = cl.id) as subscriber_count
        FROM contact_lists cl
        WHERE cl.user_id = ?";
$params = [$userId];

// Apply search filter
if (!empty($search)) {
    $sql .= " AND (cl.name LIKE ? OR cl.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Count total results for pagination
$countSql = str_replace("cl.*, \n               (SELECT COUNT(*) FROM subscriber_lists sl WHERE sl.list_id = cl.id) as subscriber_count", "COUNT(*)", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalCount = $stmt->fetchColumn();
$totalPages = ceil($totalCount / $perPage);

// Get filtered lists with pagination
$sql .= " ORDER BY cl.created_at DESC LIMIT $offset, $perPage";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $contactLists = $stmt->fetchAll();
} catch (PDOException $e) {
    // If the table doesn't exist yet
    $contactLists = [];
}
?>

<!-- Filters -->
<div class="bg-white rounded-lg shadow mb-6 p-4">
    <form action="contact_lists.php" method="GET" class="flex flex-wrap -mx-2">
        <div class="w-full md:flex-1 px-2">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">חיפוש</label>
            <div class="relative">
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <i class="ri-search-line text-gray-400"></i>
                </div>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="block w-full pr-10 py-2 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                       placeholder="חיפוש לפי שם או תיאור...">
            </div>
        </div>
        
        <div class="w-full md:w-auto px-2 flex space-x-2 items-end mt-4 md:mt-0">
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                <i class="ri-filter-line ml-1"></i>
                סינון
            </button>
            <a href="contact_lists.php" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
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

<!-- Contact Lists -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="flex justify-between items-center p-4 border-b">
        <div>
            <h2 class="text-lg font-medium">רשימות אנשי קשר</h2>
            <p class="text-sm text-gray-500">סה"כ <?php echo $totalCount; ?> רשימות</p>
        </div>
    </div>
    
    <?php if (empty($contactLists)): ?>
    <div class="p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
            <i class="ri-file-list-3-line text-gray-400 text-2xl"></i>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">אין רשימות</h3>
        <p class="text-gray-500 mb-4">לא נמצאו רשימות אנשי קשר במערכת.</p>
        <a href="contact_list_create.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 focus:outline-none">
            <i class="ri-add-line ml-1"></i>
            הוסף רשימה חדשה
        </a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">שם הרשימה</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">תיאור</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">אנשי קשר</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">נוצר בתאריך</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ברירת מחדל</th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">פעולות</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($contactLists as $list): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($list['name']); ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($list['description'] ?? ''); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a href="list_members.php?id=<?php echo $list['id']; ?>" class="text-sm text-purple-600 hover:text-purple-900">
                            <?php echo $list['subscriber_count']; ?> רשומים
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500"><?php echo formatDate($list['created_at']); ?></div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $list['is_default'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $list['is_default'] ? 'כן' : 'לא'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                        <div class="flex items-center justify-end space-x-reverse space-x-2">
                            <a href="add_to_list.php?id=<?php echo $list['id']; ?>" 
                               class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center hover:bg-green-200 transition-colors" 
                               title="הוסף אנשי קשר">
                                <i class="ri-user-add-line text-green-600"></i>
                            </a>
                            <a href="export_list.php?id=<?php echo $list['id']; ?>" 
                               class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center hover:bg-blue-200 transition-colors" 
                               title="ייצא רשימה">
                                <i class="ri-download-2-line text-blue-600"></i>
                            </a>
                            <a href="contact_list_edit.php?id=<?php echo $list['id']; ?>" 
                               class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center hover:bg-indigo-200 transition-colors" 
                               title="ערוך">
                                <i class="ri-pencil-line text-indigo-600"></i>
                            </a>
                            <a href="#" 
                               class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center hover:bg-red-200 transition-colors" 
                               onclick="confirmDelete(<?php echo $list['id']; ?>, '<?php echo htmlspecialchars($list['name']); ?>')" 
                               title="מחק">
                                <i class="ri-delete-bin-line text-red-600"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <!-- Pagination -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
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
                    <!-- Previous page link -->
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="ri-arrow-right-s-line"></i>
                        <span class="sr-only">הקודם</span>
                    </a>
                    <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                        <i class="ri-arrow-right-s-line"></i>
                        <span class="sr-only">הקודם</span>
                    </span>
                    <?php endif; ?>
                    
                    <!-- Page numbers -->
                    <?php
                    $startPage = max(1, min($page - 2, $totalPages - 4));
                    $endPage = min($totalPages, max($page + 2, 5));
                    
                    // Show first page if not included in the range
                    if ($startPage > 1): ?>
                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        1
                    </a>
                    <?php if ($startPage > 2): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                        ...
                    </span>
                    <?php endif; ?>
                    <?php endif; ?>
                    
                    <!-- Page range -->
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <?php if ($i == $page): ?>
                    <span aria-current="page" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-indigo-50 text-sm font-medium text-indigo-600">
                        <?php echo $i; ?>
                    </span>
                    <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <?php echo $i; ?>
                    </a>
                    <?php endif; ?>
                    <?php endfor; ?>
                    
                    <!-- Show last page if not included in the range -->
                    <?php if ($endPage < $totalPages): ?>
                    <?php if ($endPage < $totalPages - 1): ?>
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                        ...
                    </span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <?php echo $totalPages; ?>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Next page link -->
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                        <i class="ri-arrow-left-s-line"></i>
                        <span class="sr-only">הבא</span>
                    </a>
                    <?php else: ?>
                    <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400">
                        <i class="ri-arrow-left-s-line"></i>
                        <span class="sr-only">הבא</span>
                    </span>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Delete confirmation modal -->
<div id="deleteModal" class="hidden fixed z-10 inset-0 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="ri-error-warning-line text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:mr-4 sm:text-right">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            מחיקת רשימה
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500" id="modal-message">
                                האם אתה בטוח שברצונך למחוק את הרשימה?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmDelete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:mr-3 sm:w-auto sm:text-sm">
                    מחק
                </a>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                    ביטול
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for delete confirmation -->
<script>
    function confirmDelete(id, name) {
        const modal = document.getElementById('deleteModal');
        const confirmButton = document.getElementById('confirmDelete');
        const modalMessage = document.getElementById('modal-message');
        
        modalMessage.textContent = `האם אתה בטוח שברצונך למחוק את הרשימה "${name}"?`;
        confirmButton.href = `contact_lists.php?delete=${id}`;
        
        modal.classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
    }
</script>

<?php 
include_once 'template/footer.php'; 
ob_end_flush(); // שחרר את הפלט המאוגר לדפדפן
?> 