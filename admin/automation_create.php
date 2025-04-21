<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'יצירת אוטומציה';
$pageDescription = 'צור תהליך אוטומטי חדש לניהול לידים';

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Check for required permissions
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'עליך להתחבר כדי לגשת לדף זה';
    redirect('login.php');
}

// Initialize variables
$automation = [
    'name' => '',
    'description' => '',
    'status' => 'draft',
    'trigger_type' => 'subscription',
    'trigger_config' => json_encode(['list_id' => 0]),
];

// Form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $automation['name'] = trim($_POST['name'] ?? '');
    $automation['description'] = trim($_POST['description'] ?? '');
    $automation['status'] = $_POST['status'] ?? 'draft';
    $automation['trigger_type'] = $_POST['trigger_type'] ?? 'subscription';
    
    // Prepare trigger configuration based on trigger type
    $triggerConfig = [];
    
    switch ($automation['trigger_type']) {
        case 'subscription':
            $triggerConfig['list_id'] = (int)($_POST['list_id'] ?? 0);
            break;
            
        case 'date':
            $triggerConfig['field'] = $_POST['date_field'] ?? 'created_at';
            $triggerConfig['days'] = (int)($_POST['days_after'] ?? 0);
            break;
            
        case 'form_submission':
            $triggerConfig['form_id'] = (int)($_POST['form_id'] ?? 0);
            break;
            
        case 'inactivity':
            $triggerConfig['days'] = (int)($_POST['inactive_days'] ?? 0);
            $triggerConfig['action'] = $_POST['inactive_action'] ?? 'email_open';
            break;
    }
    
    $automation['trigger_config'] = json_encode($triggerConfig);
    
    // Validate form
    $errors = [];
    
    if (empty($automation['name'])) {
        $errors[] = 'שם האוטומציה נדרש';
    }
    
    // Specific validation based on trigger type
    switch ($automation['trigger_type']) {
        case 'subscription':
            if (empty($triggerConfig['list_id'])) {
                $errors[] = 'נא לבחור רשימה';
            }
            break;
            
        case 'date':
            if (empty($triggerConfig['days']) || $triggerConfig['days'] < 0) {
                $errors[] = 'נא להזין מספר ימים תקין';
            }
            break;
            
        case 'form_submission':
            if (empty($triggerConfig['form_id'])) {
                $errors[] = 'נא לבחור טופס';
            }
            break;
            
        case 'inactivity':
            if (empty($triggerConfig['days']) || $triggerConfig['days'] < 1) {
                $errors[] = 'נא להזין מספר ימים תקין';
            }
            break;
    }
    
    // If no errors, save automation
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert automation
            $stmt = $pdo->prepare("
                INSERT INTO automations 
                (user_id, name, description, status, trigger_type, trigger_config) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $automation['name'],
                $automation['description'],
                $automation['status'],
                $automation['trigger_type'],
                $automation['trigger_config']
            ]);
            
            $automationId = $pdo->lastInsertId();
            
            // Add initial wait step if needed
            if (isset($_POST['add_initial_step']) && $_POST['add_initial_step'] === '1') {
                $waitDays = (int)($_POST['initial_wait_days'] ?? 0);
                
                if ($waitDays > 0) {
                    $stmt = $pdo->prepare("
                        INSERT INTO automation_steps 
                        (automation_id, step_order, action_type, wait_days) 
                        VALUES (?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $automationId,
                        1,
                        'wait',
                        $waitDays
                    ]);
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Set success message
            $_SESSION['success'] = 'האוטומציה נוצרה בהצלחה. כעת תוכל להוסיף צעדים לאוטומציה.';
            
            // Redirect to edit page
            redirect('automation_edit.php?id=' . $automationId);
            
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            
            // Set error message
            $errors[] = 'אירעה שגיאה בעת שמירת האוטומציה: ' . $e->getMessage();
        }
    }
}

// Get contact lists for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, name FROM contact_lists WHERE user_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    $contactLists = $stmt->fetchAll();
} catch (PDOException $e) {
    $contactLists = [];
}

// Get forms for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, title FROM landing_pages WHERE user_id = ? AND status = 'active' ORDER BY title");
    $stmt->execute([$userId]);
    $forms = $stmt->fetchAll();
} catch (PDOException $e) {
    $forms = [];
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
<?php if (!empty($errors)): ?>
    <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6 rounded">
        <div class="flex">
            <div class="mr-1">
                <i class="ri-error-warning-line text-red-500"></i>
            </div>
            <div class="mr-3">
                <p class="text-sm text-red-700">אנא תקן את השגיאות הבאות:</p>
                <ul class="mt-1 list-disc list-inside text-sm text-red-700">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

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

<!-- טופס יצירת אוטומציה -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b">
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-medium">יצירת אוטומציה חדשה</h2>
        </div>
    </div>
    
    <form action="" method="POST" class="p-6">
        <!-- מידע בסיסי -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-4">מידע בסיסי</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">שם האוטומציה <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($automation['name']); ?>" 
                           class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"
                           required>
                    <p class="mt-1 text-sm text-gray-500">שם תיאורי שיעזור לך לזהות את האוטומציה</p>
                </div>
                
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">סטטוס</label>
                    <select id="status" name="status" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="draft" <?php if ($automation['status'] === 'draft') echo 'selected'; ?>>טיוטה</option>
                        <option value="inactive" <?php if ($automation['status'] === 'inactive') echo 'selected'; ?>>לא פעיל</option>
                        <option value="active" <?php if ($automation['status'] === 'active') echo 'selected'; ?>>פעיל</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">האם האוטומציה תהיה פעילה מיד עם היצירה</p>
                </div>
                
                <div class="md:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">תיאור האוטומציה</label>
                    <textarea id="description" name="description" rows="3" 
                              class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"><?php echo htmlspecialchars($automation['description']); ?></textarea>
                    <p class="mt-1 text-sm text-gray-500">תיאור אופציונלי המסביר את מטרת האוטומציה</p>
                </div>
            </div>
        </div>
        
        <!-- הגדרות טריגר -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-4">הגדרות טריגר</h3>
            <p class="mb-4 text-sm text-gray-600">הטריגר מגדיר מתי האוטומציה תתחיל לפעול עבור ליד</p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">סוג הטריגר</label>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors <?php if ($automation['trigger_type'] === 'subscription') echo 'bg-purple-50 border-purple-500'; ?>">
                            <input type="radio" name="trigger_type" value="subscription" 
                                  <?php if ($automation['trigger_type'] === 'subscription') echo 'checked'; ?>
                                  class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showTriggerOptions('subscription')">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i class="ri-user-add-line text-xl text-purple-600 ml-2"></i>
                                    <span class="font-medium">הצטרפות לרשימה</span>
                                </div>
                                <p class="text-sm text-gray-500">כאשר ליד מצטרף לרשימה מסוימת</p>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors <?php if ($automation['trigger_type'] === 'date') echo 'bg-purple-50 border-purple-500'; ?>">
                            <input type="radio" name="trigger_type" value="date" 
                                  <?php if ($automation['trigger_type'] === 'date') echo 'checked'; ?>
                                  class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showTriggerOptions('date')">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i class="ri-calendar-event-line text-xl text-purple-600 ml-2"></i>
                                    <span class="font-medium">תאריך מסוים</span>
                                </div>
                                <p class="text-sm text-gray-500">מספר ימים אחרי תאריך מסוים (כמו יום הולדת)</p>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors <?php if ($automation['trigger_type'] === 'form_submission') echo 'bg-purple-50 border-purple-500'; ?>">
                            <input type="radio" name="trigger_type" value="form_submission" 
                                  <?php if ($automation['trigger_type'] === 'form_submission') echo 'checked'; ?>
                                  class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showTriggerOptions('form_submission')">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i class="ri-file-list-3-line text-xl text-purple-600 ml-2"></i>
                                    <span class="font-medium">שליחת טופס</span>
                                </div>
                                <p class="text-sm text-gray-500">כאשר ליד שולח טופס בדף נחיתה</p>
                            </div>
                        </label>
                    </div>
                    
                    <div>
                        <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors <?php if ($automation['trigger_type'] === 'inactivity') echo 'bg-purple-50 border-purple-500'; ?>">
                            <input type="radio" name="trigger_type" value="inactivity" 
                                  <?php if ($automation['trigger_type'] === 'inactivity') echo 'checked'; ?>
                                  class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showTriggerOptions('inactivity')">
                            <div>
                                <div class="flex items-center mb-1">
                                    <i class="ri-time-line text-xl text-purple-600 ml-2"></i>
                                    <span class="font-medium">חוסר פעילות</span>
                                </div>
                                <p class="text-sm text-gray-500">כאשר ליד לא פעיל למשך תקופה מסוימת</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- אפשרויות לטריגר הצטרפות לרשימה -->
            <div id="subscription-options" class="p-4 border rounded-lg bg-gray-50 mt-4 trigger-options <?php if ($automation['trigger_type'] !== 'subscription') echo 'hidden'; ?>">
                <div class="mb-4">
                    <label for="list_id" class="block text-sm font-medium text-gray-700 mb-1">בחר רשימה <span class="text-red-500">*</span></label>
                    <select id="list_id" name="list_id" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">בחר רשימה...</option>
                        <?php foreach ($contactLists as $list): 
                            $triggerConfig = json_decode($automation['trigger_config'], true);
                            $selectedListId = $triggerConfig['list_id'] ?? 0;
                        ?>
                            <option value="<?php echo $list['id']; ?>" <?php if ($selectedListId == $list['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($list['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">האוטומציה תופעל כאשר ליד יצטרף לרשימה זו</p>
                </div>
            </div>
            
            <!-- אפשרויות לטריגר תאריך -->
            <div id="date-options" class="p-4 border rounded-lg bg-gray-50 mt-4 trigger-options <?php if ($automation['trigger_type'] !== 'date') echo 'hidden'; ?>">
                <div class="mb-4">
                    <label for="date_field" class="block text-sm font-medium text-gray-700 mb-1">שדה תאריך <span class="text-red-500">*</span></label>
                    <select id="date_field" name="date_field" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="created_at" <?php if (($triggerConfig['field'] ?? '') === 'created_at') echo 'selected'; ?>>תאריך הצטרפות</option>
                        <option value="birthdate" <?php if (($triggerConfig['field'] ?? '') === 'birthdate') echo 'selected'; ?>>יום הולדת</option>
                        <option value="custom_date" <?php if (($triggerConfig['field'] ?? '') === 'custom_date') echo 'selected'; ?>>שדה תאריך מותאם אישית</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="days_after" class="block text-sm font-medium text-gray-700 mb-1">מספר ימים לאחר התאריך <span class="text-red-500">*</span></label>
                    <input type="number" id="days_after" name="days_after" min="0" value="<?php echo ($triggerConfig['days'] ?? 0); ?>" 
                           class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-sm text-gray-500">האוטומציה תופעל מספר ימים זה לאחר התאריך שנבחר</p>
                </div>
            </div>
            
            <!-- אפשרויות לטריגר שליחת טופס -->
            <div id="form_submission-options" class="p-4 border rounded-lg bg-gray-50 mt-4 trigger-options <?php if ($automation['trigger_type'] !== 'form_submission') echo 'hidden'; ?>">
                <div class="mb-4">
                    <label for="form_id" class="block text-sm font-medium text-gray-700 mb-1">בחר דף נחיתה <span class="text-red-500">*</span></label>
                    <select id="form_id" name="form_id" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="">בחר דף נחיתה...</option>
                        <?php foreach ($forms as $form): 
                            $triggerConfig = json_decode($automation['trigger_config'], true);
                            $selectedFormId = $triggerConfig['form_id'] ?? 0;
                        ?>
                            <option value="<?php echo $form['id']; ?>" <?php if ($selectedFormId == $form['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($form['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">האוטומציה תופעל כאשר ליד ישלח טופס בדף נחיתה זה</p>
                </div>
            </div>
            
            <!-- אפשרויות לטריגר חוסר פעילות -->
            <div id="inactivity-options" class="p-4 border rounded-lg bg-gray-50 mt-4 trigger-options <?php if ($automation['trigger_type'] !== 'inactivity') echo 'hidden'; ?>">
                <div class="mb-4">
                    <label for="inactive_days" class="block text-sm font-medium text-gray-700 mb-1">מספר ימים ללא פעילות <span class="text-red-500">*</span></label>
                    <input type="number" id="inactive_days" name="inactive_days" min="1" value="<?php echo ($triggerConfig['days'] ?? 30); ?>" 
                           class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-sm text-gray-500">האוטומציה תופעל כאשר ליד לא יהיה פעיל למשך מספר ימים זה</p>
                </div>
                
                <div class="mb-4">
                    <label for="inactive_action" class="block text-sm font-medium text-gray-700 mb-1">סוג פעילות <span class="text-red-500">*</span></label>
                    <select id="inactive_action" name="inactive_action" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <option value="email_open" <?php if (($triggerConfig['action'] ?? '') === 'email_open') echo 'selected'; ?>>פתיחת אימייל</option>
                        <option value="email_click" <?php if (($triggerConfig['action'] ?? '') === 'email_click') echo 'selected'; ?>>לחיצה על קישור באימייל</option>
                        <option value="website_visit" <?php if (($triggerConfig['action'] ?? '') === 'website_visit') echo 'selected'; ?>>ביקור באתר</option>
                        <option value="form_submission" <?php if (($triggerConfig['action'] ?? '') === 'form_submission') echo 'selected'; ?>>מילוי טופס</option>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">סוג הפעילות שהעדר שלה יגרום להפעלת האוטומציה</p>
                </div>
            </div>
        </div>
        
        <!-- צעד ראשוני אופציונלי -->
        <div class="mb-8">
            <h3 class="text-lg font-medium mb-4">צעד ראשוני (אופציונלי)</h3>
            
            <div class="p-4 border rounded-lg bg-gray-50">
                <div class="flex items-center mb-4">
                    <input type="checkbox" id="add_initial_step" name="add_initial_step" value="1" class="mr-2 h-4 w-4 text-purple-600 focus:ring-purple-500">
                    <label for="add_initial_step" class="text-sm font-medium text-gray-700">הוסף השהיה בהתחלת האוטומציה</label>
                </div>
                
                <div id="initial-step-options" class="mr-6 hidden">
                    <label for="initial_wait_days" class="block text-sm font-medium text-gray-700 mb-1">מספר ימי המתנה</label>
                    <input type="number" id="initial_wait_days" name="initial_wait_days" min="1" value="1" 
                           class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                    <p class="mt-1 text-sm text-gray-500">האוטומציה תמתין מספר ימים זה לפני ביצוע הפעולה הראשונה</p>
                </div>
            </div>
        </div>
        
        <!-- כפתורי שמירה -->
        <div class="border-t pt-6 flex justify-end space-x-3 space-x-reverse">
            <a href="automations.php" class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                ביטול
            </a>
            
            <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                <i class="ri-save-line ml-1"></i>
                צור אוטומציה
            </button>
        </div>
    </form>
</div>

<script>
// הצג אפשרויות טריגר מתאימות
function showTriggerOptions(triggerType) {
    // הסתר את כל האפשרויות
    document.querySelectorAll('.trigger-options').forEach(el => {
        el.classList.add('hidden');
    });
    
    // הצג את האופציות המתאימות
    const optionsEl = document.getElementById(triggerType + '-options');
    if (optionsEl) {
        optionsEl.classList.remove('hidden');
    }
}

// השהיה ראשונית - טוגל אפשרויות
document.getElementById('add_initial_step').addEventListener('change', function() {
    const initialOptions = document.getElementById('initial-step-options');
    if (this.checked) {
        initialOptions.classList.remove('hidden');
    } else {
        initialOptions.classList.add('hidden');
    }
});
</script>

<?php include_once 'template/footer.php'; ?> 