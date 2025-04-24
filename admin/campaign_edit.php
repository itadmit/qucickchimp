<?php
require_once '../config/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'מזהה קמפיין לא סופק';
    redirect('campaigns.php');
}

$campaignId = (int)$_GET['id'];
$userId = $_SESSION['user_id'] ?? 0;

// Load campaign data
try {
    $stmt = $pdo->prepare("
        SELECT * FROM campaigns 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$campaignId, $userId]);
    $campaign = $stmt->fetch();
} catch (PDOException $e) {
    $_SESSION['error'] = 'אירעה שגיאה בטעינת נתוני הקמפיין';
    redirect('campaigns.php');
}

// Check if campaign exists and belongs to user
if (!$campaign) {
    $_SESSION['error'] = 'הקמפיין המבוקש לא נמצא או שאין לך הרשאות לערוך אותו';
    redirect('campaigns.php');
}

// Check if campaign can be edited (only drafts can be edited)
if ($campaign['status'] !== 'draft') {
    $_SESSION['error'] = 'לא ניתן לערוך קמפיין שכבר נשלח או מתוזמן';
    redirect('campaigns.php');
}

// Set page title
$pageTitle = 'עריכת קמפיין';
$pageDescription = 'ערוך את הקמפיין: ' . $campaign['name'];
$backButton = [
    'url' => 'campaigns.php',
    'text' => 'חזרה לקמפיינים'
];

// Include header
include_once 'template/header.php';

// Error & message handling
$errors = [];
$formData = [
    'name' => $campaign['name'],
    'subject' => $campaign['subject'],
    'content' => $campaign['content'],
    'send_option' => 'draft',
    'scheduled_at' => '',
    'lists' => []
];

// Get selected lists for this campaign
try {
    $stmt = $pdo->prepare("
        SELECT list_id FROM campaign_lists
        WHERE campaign_id = ?
    ");
    $stmt->execute([$campaignId]);
    $selectedLists = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $formData['lists'] = $selectedLists;
} catch (PDOException $e) {
    // Table might not exist yet, initialize empty lists array
    $selectedLists = [];
    
    // Create the table if needed
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `campaign_lists` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `campaign_id` int(11) NOT NULL,
              `list_id` int(11) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `campaign_list` (`campaign_id`, `list_id`),
              KEY `campaign_id` (`campaign_id`),
              KEY `list_id` (`list_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (PDOException $tableError) {
        // Ignore, will handle in form submission
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate campaign name
    if (empty($_POST['name'])) {
        $errors['name'] = 'יש להזין שם לקמפיין';
    } else {
        $formData['name'] = trim($_POST['name']);
    }
    
    // Validate campaign subject
    if (empty($_POST['subject'])) {
        $errors['subject'] = 'יש להזין נושא להודעה';
    } else {
        $formData['subject'] = trim($_POST['subject']);
    }
    
    // Validate campaign content
    if (empty($_POST['content'])) {
        $errors['content'] = 'יש להזין תוכן להודעה';
    } else {
        $formData['content'] = $_POST['content'];
    }
    
    // Validate lists selection
    if (empty($_POST['lists']) || !is_array($_POST['lists'])) {
        $errors['lists'] = 'יש לבחור לפחות רשימת תפוצה אחת';
    } else {
        $formData['lists'] = $_POST['lists'];
    }
    
    // Handle send option
    $formData['send_option'] = $_POST['send_option'] ?? 'draft';
    
    // Validate scheduled time if scheduling
    if ($formData['send_option'] === 'schedule') {
        if (empty($_POST['scheduled_at'])) {
            $errors['scheduled_at'] = 'יש לבחור מועד לשליחה';
        } else {
            $formData['scheduled_at'] = $_POST['scheduled_at'];
            
            // Check if date is in the future
            $scheduledTime = strtotime($formData['scheduled_at']);
            if ($scheduledTime <= time()) {
                $errors['scheduled_at'] = 'מועד השליחה חייב להיות בעתיד';
            }
        }
    }
    
    // If no errors, update campaign
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update campaign
            $stmt = $pdo->prepare("
                UPDATE campaigns 
                SET name = ?, subject = ?, content = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $formData['name'],
                $formData['subject'],
                $formData['content'],
                $campaignId,
                $userId
            ]);
            
            // Delete existing list connections
            $stmt = $pdo->prepare("DELETE FROM campaign_lists WHERE campaign_id = ?");
            $stmt->execute([$campaignId]);
            
            // Insert new list connections
            $insertListStmt = $pdo->prepare("
                INSERT INTO campaign_lists (campaign_id, list_id, created_at) 
                VALUES (?, ?, NOW())
            ");
            
            foreach ($formData['lists'] as $listId) {
                $insertListStmt->execute([$campaignId, $listId]);
            }
            
            // Handle send options
            if ($formData['send_option'] === 'schedule') {
                // Schedule the campaign
                $stmt = $pdo->prepare("
                    UPDATE campaigns 
                    SET status = 'scheduled', scheduled_at = ?
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$formData['scheduled_at'], $campaignId, $userId]);
                
                $_SESSION['success'] = 'הקמפיין תוזמן בהצלחה לשליחה ב-' . date('d/m/Y H:i', strtotime($formData['scheduled_at']));
            } elseif ($formData['send_option'] === 'send') {
                // Send the campaign immediately
                $stmt = $pdo->prepare("
                    UPDATE campaigns 
                    SET status = 'sent', sent_at = NOW()
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$campaignId, $userId]);
                
                // TODO: Queue the campaign for sending
                // In a real implementation, you would add to a queue for processing
                // For now, we'll simulate by updating stats table with pending sends
                
                // First, get all subscribers from the selected lists
                $subscriberSql = "
                    SELECT DISTINCT s.id 
                    FROM subscribers s
                    JOIN subscriber_lists sl ON s.id = sl.subscriber_id
                    WHERE sl.list_id IN (" . implode(',', array_fill(0, count($formData['lists']), '?')) . ")
                    AND s.is_subscribed = 1
                ";
                
                $stmt = $pdo->prepare($subscriberSql);
                $stmt->execute($formData['lists']);
                $subscribers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Insert a record for each subscriber in campaign_stats
                if (!empty($subscribers)) {
                    $statsStmt = $pdo->prepare("
                        INSERT INTO campaign_stats (campaign_id, subscriber_id, is_sent, sent_at)
                        VALUES (?, ?, 1, NOW())
                    ");
                    
                    foreach ($subscribers as $subscriberId) {
                        $statsStmt->execute([$campaignId, $subscriberId]);
                    }
                }
                
                // עכשיו נשלח את המיילים בפועל
                if (!empty($subscribers)) {
                    // קבל את המידע של הנמענים
                    $subscribersInfoSql = "
                        SELECT id, email, first_name, last_name 
                        FROM subscribers 
                        WHERE id IN (" . implode(',', array_fill(0, count($subscribers), '?')) . ")
                        AND is_subscribed = 1
                    ";
                    $subscribersStmt = $pdo->prepare($subscribersInfoSql);
                    $subscribersStmt->execute($subscribers);
                    $subscribersInfo = $subscribersStmt->fetchAll();
                    
                    // קבל את הגדרות ה-SMTP של המשתמש
                    $smtpSettings = getUserSmtpSettings($pdo, $userId);
                    
                    // שלח מייל לכל נמען
                    $sentCount = 0;
                    foreach ($subscribersInfo as $subscriber) {
                        // הכן את ההודעה עם תגיות מותאמות אישית
                        $personalizedContent = $formData['content'];
                        $subscriberName = trim($subscriber['first_name'] . ' ' . $subscriber['last_name']);
                        $personalizedContent = str_replace('{{subscriber_name}}', $subscriberName ?: 'שלום', $personalizedContent);
                        $personalizedContent = str_replace('{{company_name}}', APP_NAME, $personalizedContent);
                        
                        // הוסף קישור להסרה מרשימת תפוצה
                        $unsubscribeLink = APP_URL . '/unsubscribe.php?email=' . urlencode($subscriber['email']) . '&token=' . md5($subscriber['email'] . 'salt');
                        $personalizedContent = str_replace('{{unsubscribe_link}}', $unsubscribeLink, $personalizedContent);
                        
                        // שלח את המייל
                        $sent = sendEmail(
                            $subscriber['email'],
                            $formData['subject'],
                            $personalizedContent,
                            $smtpSettings['sender_name'],
                            $smtpSettings['sender_email'],
                            '',
                            [],
                            $smtpSettings
                        );
                        
                        if ($sent) {
                            $sentCount++;
                            
                            // עדכן את סטטוס השליחה בטבלת הסטטיסטיקות
                            $updateStatsStmt = $pdo->prepare("
                                UPDATE campaign_stats 
                                SET is_sent = 1, sent_at = NOW() 
                                WHERE campaign_id = ? AND subscriber_id = ?
                            ");
                            $updateStatsStmt->execute([$campaignId, $subscriber['id']]);
                        }
                    }
                    
                    $_SESSION['success'] = 'הקמפיין נשלח בהצלחה ל-' . $sentCount . ' מתוך ' . count($subscribers) . ' נמענים';
                } else {
                    $_SESSION['success'] = 'הקמפיין נשלח בהצלחה (אין נמענים ברשימות שנבחרו)';
                }
            } else {
                // Keep as draft
                $_SESSION['success'] = 'הקמפיין נשמר בהצלחה כטיוטה';
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to campaigns page
            redirect('campaigns.php');
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $_SESSION['error'] = 'אירעה שגיאה בעת עדכון הקמפיין';
            $errors['general'] = $e->getMessage();
        }
    }
}

// Get contact lists for selection
try {
    $stmt = $pdo->prepare("
        SELECT cl.id, cl.name, COUNT(sl.subscriber_id) as subscribers_count 
        FROM contact_lists cl
        LEFT JOIN subscriber_lists sl ON cl.id = sl.list_id
        WHERE cl.user_id = ?
        GROUP BY cl.id, cl.name
        ORDER BY cl.name
    ");
    $stmt->execute([$userId]);
    $contactLists = $stmt->fetchAll();
} catch (PDOException $e) {
    $contactLists = [];
}

// Load email templates for selection
try {
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM templates 
        WHERE is_active = 1 AND type = 'email'
        ORDER BY name
    ");
    $stmt->execute();
    $emailTemplates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Create a default array if table doesn't exist yet
    $emailTemplates = [
        ['id' => 1, 'name' => 'תבנית בסיסית'],
        ['id' => 2, 'name' => 'עדכון חדשות'],
        ['id' => 3, 'name' => 'הזמנה לאירוע']
    ];
}
?>

<!-- Main Content -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">עריכת קמפיין</h2>
        <p class="text-gray-500 text-sm mt-1">ערוך את פרטי הקמפיין, תזמן או שלח אותו</p>
    </div>
    
    <?php if (!empty($errors['general'])): ?>
    <div class="p-4 bg-red-50 border-r-4 border-red-500 mb-4">
        <div class="flex">
            <div class="mr-1">
                <i class="ri-error-warning-line text-red-500 text-lg"></i>
            </div>
            <div class="mr-2">
                <p class="text-red-800"><?php echo $errors['general']; ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <form action="campaign_edit.php?id=<?php echo $campaignId; ?>" method="post" class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Column - Campaign Details -->
            <div class="md:col-span-2 space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        שם הקמפיין <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?php echo isset($errors['name']) ? 'border-red-300' : ''; ?>" value="<?php echo htmlspecialchars($formData['name']); ?>" placeholder="לדוגמה: עדכון חודשי - מאי 2023">
                    <?php if (isset($errors['name'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['name']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                        נושא ההודעה <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="subject" id="subject" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?php echo isset($errors['subject']) ? 'border-red-300' : ''; ?>" value="<?php echo htmlspecialchars($formData['subject']); ?>" placeholder="נושא האימייל שהנמענים יראו">
                    <?php if (isset($errors['subject'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['subject']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div>
                    <label for="template_id" class="block text-sm font-medium text-gray-700 mb-1">
                        תבנית
                    </label>
                    <div class="flex gap-2">
                        <select name="template_id" id="template_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">ללא תבנית</option>
                            <?php foreach ($emailTemplates as $template): ?>
                            <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <a href="#" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="ri-eye-line ml-1"></i>
                            תצוגה מקדימה
                        </a>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">בחר תבנית קיימת או השתמש בעורך להתאמה אישית</p>
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="content" class="block text-sm font-medium text-gray-700">
                            תוכן ההודעה <span class="text-red-500">*</span>
                        </label>
                        <a href="../customizer-emails/index.php?type=email&id=<?php echo $campaignId; ?>" id="open-editor" class="inline-flex items-center text-sm text-indigo-600">
                            <i class="ri-palette-line ml-1"></i>
                            פתח במעצב הויזואלי
                        </a>
                    </div>
                    <textarea name="content" id="content" rows="15" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?php echo isset($errors['content']) ? 'border-red-300' : ''; ?>" placeholder="הזן את תוכן ההודעה בפורמט HTML"><?php echo htmlspecialchars($formData['content']); ?></textarea>
                    <?php if (isset($errors['content'])): ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo $errors['content']; ?></p>
                    <?php else: ?>
                    <p class="mt-1 text-sm text-gray-500">תמכו בתגיות: <code>{{subscriber_name}}</code>, <code>{{unsubscribe_link}}</code>, <code>{{company_name}}</code></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column - Recipients & Options -->
            <div class="space-y-6">
                <div class="bg-gray-50 p-4 rounded-md border">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">נמענים</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            רשימות תפוצה <span class="text-red-500">*</span>
                        </label>
                        
                        <?php if (empty($contactLists)): ?>
                        <div class="bg-yellow-50 border-r-4 border-yellow-400 p-3">
                            <p class="text-sm text-yellow-800">אין לך רשימות תפוצה. <a href="contact_list_create.php" class="font-medium underline">צור רשימה חדשה</a></p>
                        </div>
                        <?php else: ?>
                        
                        <div class="space-y-2 max-h-60 overflow-y-auto border rounded-md p-2 bg-white">
                            <?php foreach ($contactLists as $list): ?>
                            <label class="flex items-center p-2 hover:bg-gray-50 rounded">
                                <input type="checkbox" name="lists[]" value="<?php echo $list['id']; ?>" class="ml-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" <?php echo in_array($list['id'], $formData['lists']) ? 'checked' : ''; ?>>
                                <div>
                                    <div class="font-medium"><?php echo htmlspecialchars($list['name']); ?></div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo $list['subscribers_count']; ?> נמענים
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (isset($errors['lists'])): ?>
                        <p class="mt-1 text-sm text-red-600"><?php echo $errors['lists']; ?></p>
                        <?php endif; ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-md border">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">אפשרויות משלוח</h3>
                    
                    <div class="mb-4">
                        <label for="send_option" class="block text-sm font-medium text-gray-700 mb-2">
                            אפשרות משלוח
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center p-2 bg-white border rounded-md">
                                <input type="radio" name="send_option" value="draft" <?php echo $formData['send_option'] === 'draft' ? 'checked' : ''; ?> class="ml-2 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-medium">שמור כטיוטה</div>
                                    <div class="text-sm text-gray-500">
                                        הקמפיין יישמר כטיוטה ויהיה ניתן לערוך אותו בהמשך
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-2 bg-white border rounded-md">
                                <input type="radio" name="send_option" value="schedule" <?php echo $formData['send_option'] === 'schedule' ? 'checked' : ''; ?> class="ml-2 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-medium">תזמן לשליחה</div>
                                    <div class="text-sm text-gray-500">
                                        הזמן שליחה למועד מאוחר יותר
                                    </div>
                                </div>
                            </label>
                            
                            <div id="schedule_options" class="p-3 border-r border-indigo-300 mr-4 bg-indigo-50 <?php echo $formData['send_option'] !== 'schedule' ? 'hidden' : ''; ?>">
                                <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-2">
                                    מועד השליחה <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" id="scheduled_at" name="scheduled_at" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?php echo isset($errors['scheduled_at']) ? 'border-red-300' : ''; ?>" value="<?php echo $formData['scheduled_at']; ?>">
                                <?php if (isset($errors['scheduled_at'])): ?>
                                <p class="mt-1 text-sm text-red-600"><?php echo $errors['scheduled_at']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <label class="flex items-center p-2 bg-white border rounded-md">
                                <input type="radio" name="send_option" value="send" <?php echo $formData['send_option'] === 'send' ? 'checked' : ''; ?> class="ml-2 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-medium">שלח מיד</div>
                                    <div class="text-sm text-gray-500">
                                        הקמפיין יישלח באופן מיידי לכל הנמענים
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="pt-4 flex items-center justify-end space-x-4 space-x-reverse">
                    <a href="campaigns.php" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        בטל
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        שמור שינויים
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle send option toggle
        const sendOptions = document.querySelectorAll('input[name="send_option"]');
        const scheduleOptions = document.getElementById('schedule_options');
        
        sendOptions.forEach(option => {
            option.addEventListener('change', function() {
                if (this.value === 'schedule') {
                    scheduleOptions.classList.remove('hidden');
                } else {
                    scheduleOptions.classList.add('hidden');
                }
            });
        });
        
        // Handle template selection
        const templateSelect = document.getElementById('template_id');
        const contentTextarea = document.getElementById('content');
        const originalContent = contentTextarea.value;
        
        // Change textarea content based on template selection 
        templateSelect.addEventListener('change', function() {
            // In a real implementation, you'd fetch the template content from the server
            // For demonstration, we'll just use the original content
            if (this.value === '') {
                contentTextarea.value = originalContent;
            } else {
                // This is a placeholder. In a real implementation, fetch the template content from the server
                alert('בהמשך המימוש, כאן תיטען תבנית מהשרת לפי המזהה: ' + this.value);
            }
        });
    });
</script>

<?php include_once 'template/footer.php'; ?> 