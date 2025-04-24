<?php
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'עליך להתחבר כדי לגשת לדף זה';
    redirect('login.php');
}

// Get user ID
$userId = $_SESSION['user_id'];

// Check if automation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'לא צוין מזהה אוטומציה';
    redirect('automations.php');
}

$automationId = (int)$_GET['id'];

// Get automation data
try {
    $stmt = $pdo->prepare("
        SELECT * FROM automations 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$automationId, $userId]);
    $automation = $stmt->fetch();
    
    if (!$automation) {
        $_SESSION['error'] = 'האוטומציה לא נמצאה או שאין לך הרשאה לערוך אותה';
        redirect('automations.php');
    }
    
    // Parse trigger config
    $automation['trigger_config'] = json_decode($automation['trigger_config'], true);
    
    // Get steps
    $stmt = $pdo->prepare("
        SELECT * FROM automation_steps 
        WHERE automation_id = ? 
        ORDER BY step_order
    ");
    $stmt->execute([$automationId]);
    $steps = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת האוטומציה: ' . $e->getMessage();
    redirect('automations.php');
}

// Set page title
$pageTitle = 'עריכת אוטומציה: ' . htmlspecialchars($automation['name']);
$pageDescription = 'הגדר את ההתנהגות והצעדים של האוטומציה';

// Include header
include_once 'template/header.php';

// Handle form submission - Save general settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $triggerType = $_POST['trigger_type'] ?? 'subscription';
    
    // Prepare trigger configuration based on trigger type
    $triggerConfig = [];
    
    switch ($triggerType) {
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
    
    // Validate form
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'שם האוטומציה נדרש';
    }
    
    // Specific validation based on trigger type
    switch ($triggerType) {
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
    
    // If no errors, update automation
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE automations 
                SET name = ?, description = ?, status = ?, trigger_type = ?, trigger_config = ?
                WHERE id = ? AND user_id = ?
            ");
            
            if ($stmt->execute([
                $name,
                $description,
                $status,
                $triggerType,
                json_encode($triggerConfig),
                $automationId,
                $userId
            ])) {
                // Update the local automation data
                $automation['name'] = $name;
                $automation['description'] = $description;
                $automation['status'] = $status;
                $automation['trigger_type'] = $triggerType;
                $automation['trigger_config'] = $triggerConfig;
                
                $success = 'הגדרות האוטומציה נשמרו בהצלחה';
            } else {
                $errors[] = 'אירעה שגיאה בעדכון האוטומציה';
            }
        } catch (PDOException $e) {
            $errors[] = 'אירעה שגיאה בעדכון האוטומציה: ' . $e->getMessage();
        }
    }
}

// Handle step updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_step') {
    // Get step data
    $stepType = $_POST['step_type'] ?? '';
    $stepOrder = count($steps) + 1;
    $waitDays = (int)($_POST['wait_days'] ?? 0);
    $actionConfig = [];
    
    // Build action config based on step type
    switch ($stepType) {
        case 'send_email':
            $actionConfig['email_subject'] = $_POST['email_subject'] ?? '';
            $actionConfig['email_content'] = $_POST['email_content'] ?? '';
            break;
            
        case 'add_tag':
        case 'remove_tag':
            $actionConfig['tag'] = $_POST['tag'] ?? '';
            break;
            
        case 'move_to_list':
            $actionConfig['list_id'] = (int)($_POST['list_id'] ?? 0);
            break;
            
        case 'update_field':
            $actionConfig['field'] = $_POST['field'] ?? '';
            $actionConfig['value'] = $_POST['value'] ?? '';
            break;
    }
    
    // Validate step data
    $errors = [];
    
    if (empty($stepType)) {
        $errors[] = 'סוג הצעד נדרש';
    } else {
        // Specific validation based on step type
        switch ($stepType) {
            case 'wait':
                if (empty($waitDays) || $waitDays < 1) {
                    $errors[] = 'יש להזין מספר ימי המתנה חיובי';
                }
                break;
                
            case 'send_email':
                if (empty($actionConfig['email_subject'])) {
                    $errors[] = 'נושא האימייל נדרש';
                }
                if (empty($actionConfig['email_content'])) {
                    $errors[] = 'תוכן האימייל נדרש';
                }
                break;
                
            case 'add_tag':
            case 'remove_tag':
                if (empty($actionConfig['tag'])) {
                    $errors[] = 'יש לציין תג';
                }
                break;
                
            case 'move_to_list':
                if (empty($actionConfig['list_id'])) {
                    $errors[] = 'יש לבחור רשימה';
                }
                break;
                
            case 'update_field':
                if (empty($actionConfig['field'])) {
                    $errors[] = 'יש לציין שדה לעדכון';
                }
                break;
        }
    }
    
    // If no errors, add new step
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO automation_steps 
                (automation_id, step_order, action_type, action_config, wait_days) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([
                $automationId,
                $stepOrder,
                $stepType,
                json_encode($actionConfig),
                $stepType === 'wait' ? $waitDays : null
            ])) {
                // Reload steps
                $stmt = $pdo->prepare("
                    SELECT * FROM automation_steps 
                    WHERE automation_id = ? 
                    ORDER BY step_order
                ");
                $stmt->execute([$automationId]);
                $steps = $stmt->fetchAll();
                
                $success = 'הצעד נוסף בהצלחה לאוטומציה';
            } else {
                $errors[] = 'אירעה שגיאה בהוספת הצעד';
            }
        } catch (PDOException $e) {
            $errors[] = 'אירעה שגיאה בהוספת הצעד: ' . $e->getMessage();
        }
    }
}

// Handle step deletion
if (isset($_GET['delete_step']) && !empty($_GET['delete_step'])) {
    $stepId = (int)$_GET['delete_step'];
    
    // Verify the step belongs to this automation
    $stmt = $pdo->prepare("
        SELECT id FROM automation_steps 
        WHERE id = ? AND automation_id = ?
    ");
    $stmt->execute([$stepId, $automationId]);
    $stepExists = $stmt->fetchColumn();
    
    if ($stepExists) {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Delete the step
            $stmt = $pdo->prepare("DELETE FROM automation_steps WHERE id = ?");
            $stmt->execute([$stepId]);
            
            // Reorder remaining steps
            $stmt = $pdo->prepare("
                SELECT id FROM automation_steps 
                WHERE automation_id = ? 
                ORDER BY step_order
            ");
            $stmt->execute([$automationId]);
            $remainingSteps = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Update order
            foreach ($remainingSteps as $index => $id) {
                $newOrder = $index + 1;
                $stmt = $pdo->prepare("
                    UPDATE automation_steps 
                    SET step_order = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newOrder, $id]);
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Reload steps
            $stmt = $pdo->prepare("
                SELECT * FROM automation_steps 
                WHERE automation_id = ? 
                ORDER BY step_order
            ");
            $stmt->execute([$automationId]);
            $steps = $stmt->fetchAll();
            
            $_SESSION['success'] = 'הצעד נמחק בהצלחה';
            redirect('automation_edit.php?id=' . $automationId);
            
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            
            $_SESSION['error'] = 'אירעה שגיאה במחיקת הצעד: ' . $e->getMessage();
            redirect('automation_edit.php?id=' . $automationId);
        }
    } else {
        $_SESSION['error'] = 'הצעד לא נמצא או שאין לך הרשאה למחוק אותו';
        redirect('automation_edit.php?id=' . $automationId);
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

// Get active subscribers in this automation
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM automation_subscribers 
        WHERE automation_id = ? AND status = 'active'
    ");
    $stmt->execute([$automationId]);
    $activeSubscribers = $stmt->fetchColumn();
} catch (PDOException $e) {
    $activeSubscribers = 0;
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

<!-- כותרת הדף -->
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center">
            <a href="automations.php" class="text-purple-600 hover:text-purple-800 ml-2">
                <i class="ri-arrow-right-line"></i>
            </a>
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($automation['name']); ?></h1>
            
            <?php if ($automation['status'] === 'active'): ?>
                <span class="mr-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                    פעיל
                </span>
            <?php elseif ($automation['status'] === 'inactive'): ?>
                <span class="mr-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    <span class="w-2 h-2 bg-gray-500 rounded-full mr-1"></span>
                    לא פעיל
                </span>
            <?php else: ?>
                <span class="mr-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></span>
                    טיוטה
                </span>
            <?php endif; ?>
        </div>
        <?php if (!empty($automation['description'])): ?>
            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($automation['description']); ?></p>
        <?php endif; ?>
    </div>
    
    <div class="flex space-x-2 space-x-reverse">
        <a href="automations.php?toggle=<?php echo $automation['id']; ?>" 
           class="px-3 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors inline-flex items-center"
           onclick="return confirm('האם אתה בטוח שברצונך <?php echo $automation['status'] === 'active' ? 'להשהות' : 'להפעיל'; ?> את האוטומציה הזו?')">
            <i class="<?php echo $automation['status'] === 'active' ? 'ri-pause-line' : 'ri-play-line'; ?> ml-1"></i>
            <?php echo $automation['status'] === 'active' ? 'השהה' : 'הפעל'; ?>
        </a>
        
        <a href="automations.php?delete=<?php echo $automation['id']; ?>" 
           class="px-3 py-2 bg-white border border-red-300 rounded-md text-red-700 hover:bg-red-50 transition-colors inline-flex items-center"
           onclick="return confirm('האם אתה בטוח שברצונך למחוק את האוטומציה הזו?')">
            <i class="ri-delete-bin-line ml-1"></i>
            מחק
        </a>
    </div>
</div>

<!-- תפריט לשוניות -->
<div class="mb-6 border-b">
    <nav class="flex space-x-6 space-x-reverse">
        <a href="#settings" class="px-1 py-4 border-b-2 border-purple-500 font-medium text-purple-600 tab-link active" data-tab="settings">
            הגדרות כלליות
        </a>
        <a href="#steps" class="px-1 py-4 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 tab-link" data-tab="steps">
            צעדי האוטומציה
        </a>
        <a href="#subscribers" class="px-1 py-4 border-b-2 border-transparent font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 tab-link" data-tab="subscribers">
            לידים פעילים (<?php echo number_format($activeSubscribers); ?>)
        </a>
    </nav>
</div>

<!-- תוכן לשוניות -->
<div class="tab-content">
    <!-- הגדרות כלליות -->
    <div id="settings-tab" class="tab-pane active">
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-medium">הגדרות כלליות</h2>
                <p class="text-gray-600 text-sm mt-1">הגדר את ההתנהגות הבסיסית של האוטומציה</p>
            </div>
            
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="action" value="save_settings">
                
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
                            <p class="mt-1 text-sm text-gray-500">האם האוטומציה פעילה ומעבדת לידים</p>
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
                                    $selectedListId = $automation['trigger_config']['list_id'] ?? 0;
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
                                <option value="created_at" <?php if (($automation['trigger_config']['field'] ?? '') === 'created_at') echo 'selected'; ?>>תאריך הצטרפות</option>
                                <option value="birthdate" <?php if (($automation['trigger_config']['field'] ?? '') === 'birthdate') echo 'selected'; ?>>יום הולדת</option>
                                <option value="custom_date" <?php if (($automation['trigger_config']['field'] ?? '') === 'custom_date') echo 'selected'; ?>>שדה תאריך מותאם אישית</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="days_after" class="block text-sm font-medium text-gray-700 mb-1">מספר ימים לאחר התאריך <span class="text-red-500">*</span></label>
                            <input type="number" id="days_after" name="days_after" min="0" value="<?php echo ($automation['trigger_config']['days'] ?? 0); ?>" 
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
                                    $selectedFormId = $automation['trigger_config']['form_id'] ?? 0;
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
                            <input type="number" id="inactive_days" name="inactive_days" min="1" value="<?php echo ($automation['trigger_config']['days'] ?? 30); ?>" 
                                   class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <p class="mt-1 text-sm text-gray-500">האוטומציה תופעל כאשר ליד לא יהיה פעיל למשך מספר ימים זה</p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="inactive_action" class="block text-sm font-medium text-gray-700 mb-1">סוג פעילות <span class="text-red-500">*</span></label>
                            <select id="inactive_action" name="inactive_action" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                                <option value="email_open" <?php if (($automation['trigger_config']['action'] ?? '') === 'email_open') echo 'selected'; ?>>פתיחת אימייל</option>
                                <option value="email_click" <?php if (($automation['trigger_config']['action'] ?? '') === 'email_click') echo 'selected'; ?>>לחיצה על קישור באימייל</option>
                                <option value="website_visit" <?php if (($automation['trigger_config']['action'] ?? '') === 'website_visit') echo 'selected'; ?>>ביקור באתר</option>
                                <option value="form_submission" <?php if (($automation['trigger_config']['action'] ?? '') === 'form_submission') echo 'selected'; ?>>מילוי טופס</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">סוג הפעילות שהעדר שלה יגרום להפעלת האוטומציה</p>
                        </div>
                    </div>
                </div>
                
                <!-- כפתורי שמירה -->
                <div class="border-t pt-6 flex justify-end space-x-3 space-x-reverse">
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        <i class="ri-save-line ml-1"></i>
                        שמור הגדרות
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- טאב צעדי האוטומציה -->
    <div id="steps-tab" class="tab-pane hidden">
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6 border-b">
                <h2 class="text-xl font-medium">צעדי האוטומציה</h2>
                <p class="text-gray-600 text-sm mt-1">הגדר את הפעולות שיתבצעו באופן אוטומטי</p>
            </div>
            
            <!-- רשימת הצעדים הקיימים -->
            <div class="p-6 <?php echo empty($steps) ? 'hidden' : ''; ?>">
                <h3 class="text-lg font-medium mb-4">צעדים קיימים</h3>
                
                <div class="space-y-4" id="steps-list">
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="border rounded-lg overflow-hidden">
                            <div class="flex items-center justify-between p-4 bg-gray-50">
                                <div class="flex items-center">
                                    <div class="bg-purple-100 text-purple-700 rounded-full w-8 h-8 flex items-center justify-center font-medium ml-3">
                                        <?php echo $step['step_order']; ?>
                                    </div>
                                    
                                    <?php 
                                        $stepIcon = 'ri-time-line';
                                        $stepTitle = 'המתנה';
                                        $stepClass = 'bg-blue-100 text-blue-800';
                                        
                                        switch ($step['action_type']) {
                                            case 'send_email':
                                                $stepIcon = 'ri-mail-send-line';
                                                $stepTitle = 'שליחת אימייל';
                                                $stepClass = 'bg-green-100 text-green-800';
                                                break;
                                                
                                            case 'add_tag':
                                                $stepIcon = 'ri-price-tag-3-line';
                                                $stepTitle = 'הוספת תג';
                                                $stepClass = 'bg-indigo-100 text-indigo-800';
                                                break;
                                                
                                            case 'remove_tag':
                                                $stepIcon = 'ri-price-tag-3-line';
                                                $stepTitle = 'הסרת תג';
                                                $stepClass = 'bg-red-100 text-red-800';
                                                break;
                                                
                                            case 'move_to_list':
                                                $stepIcon = 'ri-list-check';
                                                $stepTitle = 'העברה לרשימה';
                                                $stepClass = 'bg-yellow-100 text-yellow-800';
                                                break;
                                                
                                            case 'update_field':
                                                $stepIcon = 'ri-edit-line';
                                                $stepTitle = 'עדכון שדה';
                                                $stepClass = 'bg-purple-100 text-purple-800';
                                                break;
                                        }
                                    ?>
                                    
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $stepClass; ?>">
                                        <i class="<?php echo $stepIcon; ?> ml-1"></i>
                                        <?php echo $stepTitle; ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center">
                                    <a href="automation_edit.php?id=<?php echo $automationId; ?>&delete_step=<?php echo $step['id']; ?>" 
                                       class="text-red-600 hover:text-red-900 mr-2"
                                       onclick="return confirm('האם אתה בטוח שברצונך למחוק את הצעד הזה?')">
                                        <i class="ri-delete-bin-line"></i>
                                    </a>
                                    
                                    <button type="button" class="text-gray-600 hover:text-gray-900 step-details-toggle">
                                        <i class="ri-arrow-down-s-line"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="p-4 border-t step-details hidden">
                                <?php
                                    // Parse step details
                                    $actionConfig = !empty($step['action_config']) ? json_decode($step['action_config'], true) : [];
                                    
                                    switch ($step['action_type']) {
                                        case 'wait':
                                            echo '<p><strong>משך המתנה:</strong> ' . $step['wait_days'] . ' ימים</p>';
                                            break;
                                            
                                        case 'send_email':
                                            echo '<p><strong>נושא:</strong> ' . htmlspecialchars($actionConfig['email_subject'] ?? '') . '</p>';
                                            echo '<div class="mt-2"><strong>תוכן:</strong><div class="p-3 bg-gray-50 rounded mt-1 text-sm">' . nl2br(htmlspecialchars($actionConfig['email_content'] ?? '')) . '</div></div>';
                                            break;
                                            
                                        case 'add_tag':
                                        case 'remove_tag':
                                            echo '<p><strong>תג:</strong> ' . htmlspecialchars($actionConfig['tag'] ?? '') . '</p>';
                                            break;
                                            
                                        case 'move_to_list':
                                            // Find list name
                                            $listName = 'רשימה לא מוגדרת';
                                            foreach ($contactLists as $list) {
                                                if ($list['id'] == ($actionConfig['list_id'] ?? 0)) {
                                                    $listName = $list['name'];
                                                    break;
                                                }
                                            }
                                            echo '<p><strong>רשימת יעד:</strong> ' . htmlspecialchars($listName) . '</p>';
                                            break;
                                            
                                        case 'update_field':
                                            echo '<p><strong>שדה:</strong> ' . htmlspecialchars($actionConfig['field'] ?? '') . '</p>';
                                            echo '<p><strong>ערך:</strong> ' . htmlspecialchars($actionConfig['value'] ?? '') . '</p>';
                                            break;
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- הודעה כשאין צעדים -->
            <div class="p-6 text-center <?php echo !empty($steps) ? 'hidden' : ''; ?>" id="empty-steps-message">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                    <i class="ri-list-check text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">אין צעדים</h3>
                <p class="text-gray-500 mb-6">טרם הוגדרו צעדים לאוטומציה זו. הוסף צעד ראשון כדי להתחיל.</p>
            </div>
        </div>
        
        <!-- הוספת צעד חדש -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-medium">הוסף צעד חדש</h2>
                <p class="text-gray-600 text-sm mt-1">הוסף פעולה חדשה לאוטומציה</p>
            </div>
            
            <form action="" method="POST" class="p-6">
                <input type="hidden" name="action" value="add_step">
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">סוג הצעד</label>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors" id="step-type-wait-label">
                                <input type="radio" name="step_type" value="wait" id="step-type-wait"
                                       class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showStepOptions('wait')">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <i class="ri-time-line text-xl text-blue-600 ml-2"></i>
                                        <span class="font-medium">המתנה</span>
                                    </div>
                                    <p class="text-sm text-gray-500">המתן מספר ימים לפני ביצוע הצעד הבא</p>
                                </div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors" id="step-type-send_email-label">
                                <input type="radio" name="step_type" value="send_email" id="step-type-send_email"
                                       class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showStepOptions('send_email')">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <i class="ri-mail-send-line text-xl text-green-600 ml-2"></i>
                                        <span class="font-medium">שליחת אימייל</span>
                                    </div>
                                    <p class="text-sm text-gray-500">שלח אימייל אוטומטי לליד</p>
                                </div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors" id="step-type-add_tag-label">
                                <input type="radio" name="step_type" value="add_tag" id="step-type-add_tag"
                                       class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showStepOptions('add_tag')">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <i class="ri-price-tag-3-line text-xl text-indigo-600 ml-2"></i>
                                        <span class="font-medium">הוספת תג</span>
                                    </div>
                                    <p class="text-sm text-gray-500">הוסף תג לליד</p>
                                </div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors" id="step-type-remove_tag-label">
                                <input type="radio" name="step_type" value="remove_tag" id="step-type-remove_tag"
                                       class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showStepOptions('remove_tag')">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <i class="ri-price-tag-3-line text-xl text-red-600 ml-2"></i>
                                        <span class="font-medium">הסרת תג</span>
                                    </div>
                                    <p class="text-sm text-gray-500">הסר תג מהליד</p>
                                </div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors" id="step-type-move_to_list-label">
                                <input type="radio" name="step_type" value="move_to_list" id="step-type-move_to_list"
                                       class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showStepOptions('move_to_list')">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <i class="ri-list-check text-xl text-yellow-600 ml-2"></i>
                                        <span class="font-medium">העברה לרשימה</span>
                                    </div>
                                    <p class="text-sm text-gray-500">העבר את הליד לרשימה אחרת</p>
                                </div>
                            </label>
                        </div>
                        
                        <div>
                            <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-purple-50 transition-colors" id="step-type-update_field-label">
                                <input type="radio" name="step_type" value="update_field" id="step-type-update_field"
                                       class="mt-1 mr-3 text-purple-600 focus:ring-purple-500" onclick="showStepOptions('update_field')">
                                <div>
                                    <div class="flex items-center mb-1">
                                        <i class="ri-edit-line text-xl text-purple-600 ml-2"></i>
                                        <span class="font-medium">עדכון שדה</span>
                                    </div>
                                    <p class="text-sm text-gray-500">עדכן שדה מסוים בפרטי הליד</p>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- אפשרויות לצעד המתנה -->
                <div id="wait-options" class="step-options hidden">
                    <div class="mb-4 p-4 border rounded-lg bg-gray-50">
                        <label for="wait_days" class="block text-sm font-medium text-gray-700 mb-1">ימי המתנה <span class="text-red-500">*</span></label>
                        <input type="number" id="wait_days" name="wait_days" min="1" value="1" 
                               class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <p class="mt-1 text-sm text-gray-500">מספר הימים להמתין לפני ביצוע הצעד הבא</p>
                    </div>
                </div>
                
                <!-- אפשרויות לצעד שליחת אימייל -->
                <div id="send_email-options" class="step-options hidden">
                    <div class="mb-4 p-4 border rounded-lg bg-gray-50">
                        <div class="mb-4">
                            <label for="email_subject" class="block text-sm font-medium text-gray-700 mb-1">נושא האימייל <span class="text-red-500">*</span></label>
                            <input type="text" id="email_subject" name="email_subject" 
                                   class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        
                        <div>
                            <label for="email_content" class="block text-sm font-medium text-gray-700 mb-1">תוכן האימייל <span class="text-red-500">*</span></label>
                            <textarea id="email_content" name="email_content" rows="6" 
                                      class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500"></textarea>
                            <p class="mt-1 text-sm text-gray-500">ניתן להשתמש בתגי תבנית כמו {first_name}, {email} וכו'</p>
                        </div>
                    </div>
                </div>
                
                <!-- אפשרויות לצעד הוספת/הסרת תג -->
                <div id="add_tag-options" class="step-options hidden">
                    <div class="mb-4 p-4 border rounded-lg bg-gray-50">
                        <label for="tag" class="block text-sm font-medium text-gray-700 mb-1">שם התג <span class="text-red-500">*</span></label>
                        <input type="text" id="tag" name="tag" 
                               class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <p class="mt-1 text-sm text-gray-500">התג שיתווסף לליד</p>
                    </div>
                </div>
                
                <!-- אפשרויות לצעד הסרת תג - משתמש באותו div כמו הוספת תג -->
                <div id="remove_tag-options" class="step-options hidden">
                    <div class="mb-4 p-4 border rounded-lg bg-gray-50">
                        <label for="tag_remove" class="block text-sm font-medium text-gray-700 mb-1">שם התג <span class="text-red-500">*</span></label>
                        <input type="text" id="tag_remove" name="tag" 
                               class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                        <p class="mt-1 text-sm text-gray-500">התג שיוסר מהליד</p>
                    </div>
                </div>
                
                <!-- אפשרויות לצעד העברה לרשימה -->
                <div id="move_to_list-options" class="step-options hidden">
                    <div class="mb-4 p-4 border rounded-lg bg-gray-50">
                        <label for="move_list_id" class="block text-sm font-medium text-gray-700 mb-1">רשימת יעד <span class="text-red-500">*</span></label>
                        <select id="move_list_id" name="list_id" class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <option value="">בחר רשימה...</option>
                            <?php foreach ($contactLists as $list): ?>
                                <option value="<?php echo $list['id']; ?>"><?php echo htmlspecialchars($list['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-1 text-sm text-gray-500">הרשימה אליה יועבר הליד</p>
                    </div>
                </div>
                
                <!-- אפשרויות לצעד עדכון שדה -->
                <div id="update_field-options" class="step-options hidden">
                    <div class="mb-4 p-4 border rounded-lg bg-gray-50">
                        <div class="mb-4">
                            <label for="field" class="block text-sm font-medium text-gray-700 mb-1">שם השדה <span class="text-red-500">*</span></label>
                            <input type="text" id="field" name="field" 
                                   class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <p class="mt-1 text-sm text-gray-500">השדה שיעודכן (לדוגמה: first_name, phone, custom_field)</p>
                        </div>
                        
                        <div>
                            <label for="value" class="block text-sm font-medium text-gray-700 mb-1">ערך חדש</label>
                            <input type="text" id="value" name="value" 
                                   class="block w-full py-2 px-3 border rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500">
                            <p class="mt-1 text-sm text-gray-500">הערך החדש שיוכנס לשדה</p>
                        </div>
                    </div>
                </div>
                
                <!-- כפתורי שמירה -->
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                        <i class="ri-add-line ml-1"></i>
                        הוסף צעד
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- טאב לידים פעילים -->
    <div id="subscribers-tab" class="tab-pane hidden">
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h2 class="text-xl font-medium">לידים פעילים באוטומציה</h2>
                <p class="text-gray-600 text-sm mt-1">רשימת הלידים הנמצאים כרגע בתהליך האוטומציה</p>
            </div>
            
            <?php
            // פונקציית עזר להצגת הצעד הנוכחי
            function getStepName($steps, $stepId) {
                foreach ($steps as $step) {
                    if ($step['id'] == $stepId) {
                        switch ($step['action_type']) {
                            case 'wait':
                                return 'המתנה - ' . $step['wait_days'] . ' ימים';
                            case 'send_email':
                                $config = json_decode($step['action_config'], true);
                                return 'שליחת אימייל - ' . htmlspecialchars($config['email_subject'] ?? '');
                            case 'add_tag':
                                $config = json_decode($step['action_config'], true);
                                return 'הוספת תג - ' . htmlspecialchars($config['tag'] ?? '');
                            case 'remove_tag':
                                $config = json_decode($step['action_config'], true);
                                return 'הסרת תג - ' . htmlspecialchars($config['tag'] ?? '');
                            case 'move_to_list':
                                return 'העברה לרשימה';
                            case 'update_field':
                                $config = json_decode($step['action_config'], true);
                                return 'עדכון שדה - ' . htmlspecialchars($config['field'] ?? '');
                            default:
                                return 'צעד ' . $step['step_order'];
                        }
                    }
                }
                return 'לא ידוע';
            }
            
            // קבל רשימת לידים פעילים באוטומציה
            try {
                $stmt = $pdo->prepare("
                    SELECT auto_sub.*, s.email, s.first_name, s.last_name, s.phone
                    FROM automation_subscribers auto_sub
                    JOIN subscribers s ON auto_sub.subscriber_id = s.id
                    WHERE auto_sub.automation_id = ? AND auto_sub.status = 'active'
                    ORDER BY auto_sub.next_action_at
                    LIMIT 100
                ");
                $stmt->execute([$automationId]);
                $activeSubscribers = $stmt->fetchAll();
            } catch (PDOException $e) {
                $activeSubscribers = [];
            }
            
            if (empty($activeSubscribers)):
            ?>
                <div class="p-6 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="ri-user-line text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">אין לידים פעילים</h3>
                    <p class="text-gray-500">אין כרגע לידים פעילים באוטומציה זו.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">ליד</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">הצעד הבא</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">תאריך הצעד הבא</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">מצב</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">פעולות</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($activeSubscribers as $subscriber): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($subscriber['first_name'] . ' ' . $subscriber['last_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($subscriber['email']); ?>
                                            <?php if (!empty($subscriber['phone'])): ?>
                                                <span class="mr-1"><?php echo htmlspecialchars($subscriber['phone']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo getStepName($steps, $subscriber['current_step']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                                if ($subscriber['next_action_at']) {
                                                    echo formatHebrewDate($subscriber['next_action_at']);
                                                } else {
                                                    echo 'מחכה לעיבוד';
                                                }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                                            פעיל
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                                        <a href="subscriber_view.php?id=<?php echo $subscriber['subscriber_id']; ?>" class="text-purple-600 hover:text-purple-900">
                                            צפייה
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// ניהול לשוניות
document.querySelectorAll('.tab-link').forEach(tabLink => {
    tabLink.addEventListener('click', function(e) {
        e.preventDefault();
        
        // הסר את הקלאס active מכל הלשוניות
        document.querySelectorAll('.tab-link').forEach(link => {
            link.classList.remove('active');
            link.classList.remove('text-purple-600');
            link.classList.remove('border-purple-500');
            link.classList.add('text-gray-500');
            link.classList.add('border-transparent');
        });
        
        // הסתר את כל תוכן הלשוניות
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.add('hidden');
        });
        
        // הוסף את הקלאס active ללשונית שנלחצה
        this.classList.add('active');
        this.classList.add('text-purple-600');
        this.classList.add('border-purple-500');
        this.classList.remove('text-gray-500');
        this.classList.remove('border-transparent');
        
        // הצג את תוכן הלשונית המתאימה
        const tabId = this.getAttribute('data-tab');
        document.getElementById(tabId + '-tab').classList.remove('hidden');
    });
});

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

// הצג אפשרויות צעד מתאימות
function showStepOptions(stepType) {
    // הסתר את כל האפשרויות
    document.querySelectorAll('.step-options').forEach(el => {
        el.classList.add('hidden');
    });
    
    // הצג את האופציות המתאימות
    const optionsEl = document.getElementById(stepType + '-options');
    if (optionsEl) {
        optionsEl.classList.remove('hidden');
    }
    
    // סמן את התיבה הנבחרת
    document.querySelectorAll('[id^="step-type-"]').forEach(radio => {
        const label = document.getElementById(radio.id + '-label');
        if (label) {
            if (radio.checked) {
                label.classList.add('bg-purple-50', 'border-purple-500');
            } else {
                label.classList.remove('bg-purple-50', 'border-purple-500');
            }
        }
    });
}

// הצג/הסתר פרטי צעד
document.querySelectorAll('.step-details-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const details = this.closest('.border').querySelector('.step-details');
        const icon = this.querySelector('i');
        
        if (details.classList.contains('hidden')) {
            details.classList.remove('hidden');
            icon.classList.remove('ri-arrow-down-s-line');
            icon.classList.add('ri-arrow-up-s-line');
        } else {
            details.classList.add('hidden');
            icon.classList.remove('ri-arrow-up-s-line');
            icon.classList.add('ri-arrow-down-s-line');
        }
    });
});
</script>

<?php include_once 'template/footer.php'; ?> 