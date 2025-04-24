<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'הוספת ליד חדש';
$pageDescription = 'הוספת ליד חדש למערכת';

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Initialize variables
$email = '';
$firstName = '';
$lastName = '';
$phone = '';
$landingPageId = '';
$isSubscribed = 1;
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
        // Check if email already exists for this user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscribers WHERE email = ? AND user_id = ?");
        $stmt->execute([$email, $userId]);
        $emailExists = $stmt->fetchColumn();
        
        if ($emailExists) {
            $error = 'כתובת האימייל כבר קיימת במערכת';
        } else {
            // Check plan limits
            if (hasReachedPlanLimits($pdo, $userId, 'leads')) {
                $error = 'הגעת למכסת הלידים החודשית שלך. שקול לשדרג את החשבון.';
            } else {
                // Prepare custom fields as JSON
                $customFields = [];
                if (isset($_POST['custom_fields']) && is_array($_POST['custom_fields'])) {
                    foreach ($_POST['custom_fields'] as $key => $value) {
                        if (!empty($key) && !empty($value)) {
                            $customFields[$key] = $value;
                        }
                    }
                }
                
                // Insert new subscriber
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO subscribers 
                        (user_id, landing_page_id, email, first_name, last_name, phone, custom_fields, is_subscribed, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $result = $stmt->execute([
                        $userId,
                        $landingPageId,
                        $email,
                        $firstName,
                        $lastName,
                        $phone,
                        !empty($customFields) ? json_encode($customFields) : null,
                        $isSubscribed
                    ]);
                    
                    if ($result) {
                        $_SESSION['success'] = 'הליד נוסף בהצלחה';
                        redirect('subscribers.php');
                    } else {
                        $error = 'אירעה שגיאה בעת הוספת הליד';
                    }
                } catch (PDOException $e) {
                    // Check if table exists
                    if ($e->getCode() == '42S02') { // Table doesn't exist
                        $error = 'טבלת הלידים אינה קיימת במסד הנתונים';
                    } else {
                        $error = 'אירעה שגיאה בעת הוספת הליד: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}
?>

<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">הוספת ליד חדש</h2>
        <p class="text-gray-500 text-sm mt-1">מלא את הפרטים להוספת ליד חדש למערכת</p>
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
            
            <!-- Status -->
            <div class="col-span-2">
                <div class="flex items-center">
                    <input type="checkbox" id="is_subscribed" name="is_subscribed" 
                           <?php if ($isSubscribed) echo 'checked'; ?>
                           class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                    <label for="is_subscribed" class="ml-2 block text-sm text-gray-700">
                        ליד פעיל (יקבל הודעות דיוור)
                    </label>
                </div>
            </div>
        </div>
        
        <!-- Custom Fields -->
        <div class="mt-8">
            <h3 class="text-lg font-medium text-gray-700 mb-3">שדות מותאמים אישית</h3>
            <p class="text-sm text-gray-500 mb-4">באפשרותך להוסיף מידע נוסף על הליד</p>
            
            <div id="custom-fields-container" class="space-y-3">
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
            </div>
        </div>
        
        <div class="mt-8 pt-5 border-t flex justify-between">
            <a href="subscribers.php" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                ביטול
            </a>
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700">
                הוסף ליד
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('custom-fields-container');
    
    // Add new field
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
        
        if (e.target.classList.contains('remove-field-btn') || e.target.closest('.remove-field-btn')) {
            const button = e.target.closest('.remove-field-btn');
            const row = button.closest('.grid');
            row.remove();
        }
    });
});
</script>

<?php include_once 'template/footer.php'; ?>