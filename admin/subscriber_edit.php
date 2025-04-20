<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'עריכת מנוי';
$pageDescription = 'עריכת פרטי מנוי קיים';

// Get user ID and subscriber ID
$userId = $_SESSION['user_id'] ?? 0;
$subscriberId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify the subscriber exists and belongs to this user
$subscriber = null;
try {
    $stmt = $pdo->prepare("
        SELECT s.*, l.title as landing_page_title 
        FROM subscribers s
        LEFT JOIN landing_pages l ON s.landing_page_id = l.id
        WHERE s.id = ? AND s.user_id = ?
    ");
    $stmt->execute([$subscriberId, $userId]);
    $subscriber = $stmt->fetch();
} catch (PDOException $e) {
    // Table might not exist
    $_SESSION['error'] = 'אירעה שגיאה בעת טעינת פרטי המנוי';
    redirect('subscribers.php');
}

if (!$subscriber) {
    $_SESSION['error'] = 'המנוי המבוקש לא נמצא או שאין לך הרשאה לערוך אותו';
    redirect('subscribers.php');
}

// Include header
include_once 'template/header.php';

// Initialize variables
$email = $subscriber['email'];
$firstName = $subscriber['first_name'];
$lastName = $subscriber['last_name'];
$phone = $subscriber['phone'];
$landingPageId = $subscriber['landing_page_id'];
$isSubscribed = $subscriber['is_subscribed'];
$customFields = !empty($subscriber['custom_fields']) ? json_decode($subscriber['custom_fields'], true) : [];
$error = '';

// Get landing pages for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, title FROM landing_pages WHERE user_id = ? ORDER BY title");
    $stmt->execute([$userId]);
    $landingPages = $stmt->fetchAll();
} catch (PDOException $e) {
    $landingPages = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize($_POST['email'] ?? '');
    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $landingPageId = !empty($_POST['landing_page_id']) ? (int)$_POST['landing_page_id'] : null;
    $isSubscribed = isset($_POST['is_subscribed']) ? 1 : 0;
    
    // Validate input
    if (empty($email)) {
        $error = 'כתובת אימייל היא שדה חובה';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'כתובת האימייל אינה תקינה';
    } else {
        // Check if email already exists for this user (excluding current subscriber)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE email = ? AND user_id = ? AND id != ?");
        $stmt->execute([$email, $userId, $subscriberId]);
        $emailExists = $stmt->fetchColumn();
        
        if ($emailExists) {
            $error = 'כתובת האימייל כבר קיימת במערכת';
        } else {
            // Prepare custom fields as JSON
            $customFields = [];
            if (isset($_POST['custom_fields']) && 
                isset($_POST['custom_fields']['key']) && 
                isset($_POST['custom_fields']['value']) && 
                is_array($_POST['custom_fields']['key']) && 
                is_array($_POST['custom_fields']['value'])) {
                
                $keys = $_POST['custom_fields']['key'];
                $values = $_POST['custom_fields']['value'];
                
                for ($i = 0; $i < count($keys); $i++) {
                    if (!empty($keys[$i]) && isset($values[$i])) {
                        $customFields[$keys[$i]] = $values[$i];
                    }
                }
            }
            
            // Update subscriber
            try {
                $stmt = $pdo->prepare("
                    UPDATE subscribers 
                    SET email = ?, first_name = ?, last_name = ?, phone = ?, 
                        landing_page_id = ?, custom_fields = ?, is_subscribed = ?
                    WHERE id = ? AND user_id = ?
                ");
                
                $result = $stmt->execute([
                    $email,
                    $firstName,
                    $lastName,
                    $phone,
                    $landingPageId,
                    !empty($customFields) ? json_encode($customFields) : null,
                    $isSubscribed,
                    $subscriberId,
                    $userId
                ]);
                
                if ($result) {
                    $_SESSION['success'] = 'פרטי המנוי עודכנו בהצלחה';
                    redirect('subscribers.php');
                } else {
                    $error = 'אירעה שגיאה בעת עדכון פרטי המנוי';
                }
            } catch (PDOException $e) {
                $error = 'אירעה שגיאה בעת עדכון פרטי המנוי: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <div>
            <h2 class="text-xl font-medium">עריכת מנוי</h2>
            <p class="text-gray-500 text-sm mt-1">עריכת פרטי המנוי <?php echo htmlspecialchars($email); ?></p>
        </div>
        
        <div>
            <span class="mr-2 text-sm">סטטוס:</span>
            <?php if ($isSubscribed): ?>
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
        </div>
    </div>
    
    <form method="POST" action="" class="p-6">
        <?php if ($error): ?>
            <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="mr-1">
                        <i class="ri-error-warning-line text-red-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700"><?php echo $error; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Email -->
            <div class="col-span-2">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    כתובת אימייל <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <i class="ri-mail-line text-gray-400"></i>
                    </div>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                           class="block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                </div>
            </div>
            
            <!-- First Name -->
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">שם פרטי</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($firstName); ?>"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            </div>
            
            <!-- Last Name -->
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">שם משפחה</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($lastName); ?>"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            </div>
            
            <!-- Phone -->
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">טלפון</label>
                <div class="relative">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <i class="ri-phone-line text-gray-400"></i>
                    </div>
                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>"
                           class="block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                           dir="ltr">
                </div>
            </div>
            
            <!-- Landing Page -->
            <div>
                <label for="landing_page_id" class="block text-sm font-medium text-gray-700 mb-1">דף נחיתה</label>
                <select id="landing_page_id" name="landing_page_id" 
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <option value="">בחר דף נחיתה</option>
                    <?php foreach ($landingPages as $page): ?>
                        <option value="<?php echo $page['id']; ?>" <?php if ($landingPageId == $page['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($page['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Subscription Info -->
            <div class="col-span-2 bg-gray-50 p-4 rounded-md">
                <div class="flex items-center mb-3">
                    <input type="checkbox" id="is_subscribed" name="is_subscribed" 
                           <?php if ($isSubscribed) echo 'checked'; ?>
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_subscribed" class="ml-2 block text-sm text-gray-700">
                        מנוי פעיל (יקבל הודעות דיוור)
                    </label>
                </div>
                
                <div class="text-sm text-gray-500">
                    <i class="ri-calendar-line ml-1"></i>
                    תאריך הצטרפות: <?php echo formatHebrewDate($subscriber['created_at']); ?>
                </div>
            </div>
        </div>
        
        <!-- Custom Fields -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-700 mb-3">שדות מותאמים אישית</h3>
            <p class="text-sm text-gray-500 mb-4">באפשרותך להוסיף מידע נוסף על המנוי</p>
            
            <div id="custom-fields-container" class="space-y-3">
                <?php if (!empty($customFields)): ?>
                    <?php foreach ($customFields as $key => $value): ?>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <input type="text" name="custom_fields[key][]" value="<?php echo htmlspecialchars($key); ?>" 
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            </div>
                            <div class="flex">
                                <input type="text" name="custom_fields[value][]" value="<?php echo htmlspecialchars($value); ?>" 
                                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                <button type="button" class="remove-field-btn ml-2 px-3 py-2 bg-red-100 text-red-600 rounded hover:bg-red-200">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <input type="text" name="custom_fields[key][]" placeholder="שם השדה" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div class="flex">
                            <input type="text" name="custom_fields[value][]" placeholder="ערך" 
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <button type="button" class="add-field-btn ml-2 px-3 py-2 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">
                                <i class="ri-add-line"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <button type="button" id="add-custom-field" class="mt-3 inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
                <i class="ri-add-line ml-1"></i>
                הוסף שדה
            </button>
        </div>
        
        <div class="mt-8 pt-5 border-t flex justify-between">
            <div>
                <a href="subscribers.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                    חזרה לרשימה
                </a>
                
                <a href="subscribers.php?delete=<?php echo $subscriberId; ?>" 
                   class="mr-2 px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50" 
                   data-confirm="האם אתה בטוח שברצונך למחוק מנוי זה?">
                    <i class="ri-delete-bin-line ml-1"></i>
                    מחק מנוי
                </a>
            </div>
            
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                שמור שינויים
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('custom-fields-container');
    
    // Add new custom field
    document.getElementById('add-custom-field').addEventListener('click', function() {
        const fieldRow = document.createElement('div');
        fieldRow.className = 'grid grid-cols-2 gap-4 mt-3';
        fieldRow.innerHTML = `
            <div>
                <input type="text" name="custom_fields[key][]" placeholder="שם השדה" 
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
            </div>
            <div class="flex">
                <input type="text" name="custom_fields[value][]" placeholder="ערך" 
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                <button type="button" class="remove-field-btn ml-2 px-3 py-2 bg-red-100 text-red-600 rounded hover:bg-red-200">
                    <i class="ri-delete-bin-line"></i>
                </button>
            </div>
        `;
        container.appendChild(fieldRow);
    });
    
    // Add new field using + button
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-field-btn') || e.target.closest('.add-field-btn')) {
            const fieldRow = document.createElement('div');
            fieldRow.className = 'grid grid-cols-2 gap-4 mt-3';
            fieldRow.innerHTML = `
                <div>
                    <input type="text" name="custom_fields[key][]" placeholder="שם השדה" 
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div class="flex">
                    <input type="text" name="custom_fields[value][]" placeholder="ערך" 
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <button type="button" class="remove-field-btn ml-2 px-3 py-2 bg-red-100 text-red-600 rounded hover:bg-red-200">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                </div>
            `;
            container.appendChild(fieldRow);
        }
        
        // Remove field
        if (e.target.classList.contains('remove-field-btn') || e.target.closest('.remove-field-btn')) {
            const button = e.target.closest('.remove-field-btn');
            const row = button.closest('.grid');
            row.remove();
        }
    });
});
</script>

<?php include_once 'template/footer.php'; ?>