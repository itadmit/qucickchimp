<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'יצירת קמפיין';
$pageDescription = 'צור קמפיין דיוור חדש';
$backButton = [
    'url' => 'campaigns.php',
    'text' => 'חזרה לקמפיינים'
];

// Include header
include_once 'template/header.php';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Error & message handling
$errors = [];
$formData = [
    'name' => '',
    'subject' => '',
    'content' => '',
    'lists' => []
];

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
    
    // If no errors, save campaign
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Save campaign
            $stmt = $pdo->prepare("
                INSERT INTO campaigns 
                (user_id, name, subject, content, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, 'draft', NOW(), NOW())
            ");
            
            $stmt->execute([
                $userId,
                $formData['name'],
                $formData['subject'],
                $formData['content']
            ]);
            
            $campaignId = $pdo->lastInsertId();
            
            // שליחת המייל לרשימת קמפיין
            // קבלת הגדרות SMTP של המשתמש
            $smtpSettings = getUserSmtpSettings($pdo, $userId);
            
            $stmt = $pdo->prepare("SELECT * FROM list_subscribers WHERE list_id = :list_id AND status = 'subscribed'");
            $stmt->execute(['list_id' => $listId]);
            $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // שמירת זמן התחלת משלוח
            $stmt = $pdo->prepare("UPDATE campaigns SET status = 'sending', send_date = NOW() WHERE id = :id");
            $stmt->execute(['id' => $campaignId]);
            
            $totalRecipients = count($recipients);
            $sentCount = 0;
            $errorCount = 0;
            
            foreach ($recipients as $recipient) {
                $recipientName = !empty($recipient['first_name']) ? $recipient['first_name'] : '';
                $trackingPixel = '<img src="' . APP_URL . '/track/' . $campaignId . '/' . $recipient['id'] . '.png" width="1" height="1" alt="" style="display:none;">';
                
                // החלפת תגים במייל
                $personalizedMessage = $message;
                $personalizedSubject = $subject;
                
                // החלפת תגים בנושא
                $personalizedSubject = str_replace('{first_name}', $recipientName, $personalizedSubject);
                
                // החלפת תגים בגוף ההודעה
                $personalizedMessage = str_replace('{first_name}', $recipientName, $personalizedMessage);
                
                // הוספת פיקסל מעקב
                $personalizedMessage = str_replace('</body>', $trackingPixel . '</body>', $personalizedMessage);
                
                // משלוח מייל עם תמיכה ב-SMTP
                $result = sendEmail(
                    $recipient['email'], 
                    $personalizedSubject, 
                    $personalizedMessage, 
                    $smtpSettings['sender_name'], 
                    $smtpSettings['sender_email'], 
                    $replyTo, 
                    $attachmentPaths, 
                    $smtpSettings
                );
                
                // רישום סטטיסטיקת משלוח
                $status = $result ? 'sent' : 'failed';
                $stmt = $pdo->prepare("INSERT INTO campaign_stats (campaign_id, subscriber_id, send_time, status) VALUES (:campaign_id, :subscriber_id, NOW(), :status)");
                $stmt->execute([
                    'campaign_id' => $campaignId,
                    'subscriber_id' => $recipient['id'],
                    'status' => $status
                ]);
                
                if ($result) {
                    $sentCount++;
                } else {
                    $errorCount++;
                }
                
                // שהייה קצרה בין המיילים למניעת חסימה
                usleep(500000); // חצי שנייה
            }
            
            // עדכון סטטיסטיקות הקמפיין
            $stmt = $pdo->prepare("UPDATE campaigns SET status = 'sent', sent_count = :sent_count, error_count = :error_count WHERE id = :id");
            $stmt->execute([
                'id' => $campaignId,
                'sent_count' => $sentCount,
                'error_count' => $errorCount
            ]);
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to edit page
            $_SESSION['success'] = 'קמפיין נוצר בהצלחה';
            redirect("campaign_edit.php?id=$campaignId");
            
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $_SESSION['error'] = 'אירעה שגיאה בעת יצירת הקמפיין';
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

// Get default template content
$defaultTemplate = '<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            direction: rtl;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
        .button {
            display: inline-block;
            background-color: #5f51e8;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{company_name}}</h1>
        </div>
        <div class="content">
            <h2>שלום {{subscriber_name}},</h2>
            <p>ברוכים הבאים לעדכון החודשי שלנו. אנו שמחים לשתף אתכם בחדשות האחרונות.</p>
            <p>תוכן הדיוור שלך יופיע כאן... תוכל להוסיף תמונות, קישורים, כפתורים, ועוד.</p>
            <div style="text-align: center;">
                <a href="{{unsubscribe_link}}" class="button">לפרטים נוספים</a>
            </div>
        </div>
        <div class="footer">
            <p>אם אינך רוצה לקבל הודעות נוספות, <a href="{{unsubscribe_link}}">לחץ כאן להסרה</a>.</p>
            <p>© {{current_year}} {{company_name}}. כל הזכויות שמורות.</p>
        </div>
    </div>
</body>
</html>';
?>

<!-- Main Content -->
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-xl font-medium">יצירת קמפיין חדש</h2>
        <p class="text-gray-500 text-sm mt-1">צור קמפיין דיוור חדש ושמור אותו כטיוטה</p>
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
    
    <form action="campaign_create.php" method="post" class="p-6">
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
                    <select name="template_id" id="template_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">ללא תבנית</option>
                        <?php foreach ($emailTemplates as $template): ?>
                        <option value="<?php echo $template['id']; ?>"><?php echo htmlspecialchars($template['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="mt-1 text-sm text-gray-500">בחר תבנית קיימת או השתמש בעורך להתאמה אישית</p>
                </div>
                
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label for="content" class="block text-sm font-medium text-gray-700">
                            תוכן ההודעה <span class="text-red-500">*</span>
                        </label>
                        <a href="https://quick-site.co.il/customizer-mail/index.php?type=email" id="open-editor" class="inline-flex items-center text-sm text-indigo-600">
                            <i class="ri-palette-line ml-1"></i>
                            פתח במעצב הויזואלי
                        </a>
                    </div>
                    <textarea name="content" id="content" rows="15" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm <?php echo isset($errors['content']) ? 'border-red-300' : ''; ?>" placeholder="הזן את תוכן ההודעה בפורמט HTML"><?php echo !empty($formData['content']) ? htmlspecialchars($formData['content']) : htmlspecialchars($defaultTemplate); ?></textarea>
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
                                <input type="radio" name="send_option" value="draft" checked class="ml-2 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-medium">שמור כטיוטה</div>
                                    <div class="text-sm text-gray-500">
                                        הקמפיין יישמר כטיוטה ויהיה ניתן לערוך אותו בהמשך
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-2 bg-white border rounded-md opacity-50 cursor-not-allowed">
                                <input type="radio" name="send_option" value="schedule" disabled class="ml-2 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-medium">תזמן לשליחה</div>
                                    <div class="text-sm text-gray-500">
                                        הזמן שליחה למועד מאוחר יותר (זמין בעריכת קמפיין)
                                    </div>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-2 bg-white border rounded-md opacity-50 cursor-not-allowed">
                                <input type="radio" name="send_option" value="send" disabled class="ml-2 h-4 w-4 border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <div class="font-medium">שלח מיד</div>
                                    <div class="text-sm text-gray-500">
                                        שלח את הקמפיין באופן מיידי (זמין בעריכת קמפיין)
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
                        צור קמפיין
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle template selection
        const templateSelect = document.getElementById('template_id');
        const contentTextarea = document.getElementById('content');
        const defaultContent = contentTextarea.value;
        
        // Change textarea content based on template selection (in real implementation, this would fetch the template content via AJAX)
        templateSelect.addEventListener('change', function() {
            // In a real implementation, you'd fetch the template content from the server
            // For demonstration, we'll just use the default template
            if (this.value === '') {
                contentTextarea.value = defaultContent;
            } else {
                // This is a placeholder. In a real implementation, fetch the template content from the server
                alert('בהמשך המימוש, כאן תיטען תבנית מהשרת לפי המזהה: ' + this.value);
            }
        });
        
        // Handle visual editor button
        const openEditorBtn = document.getElementById('open-editor');
        
        openEditorBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // In a real implementation, you might redirect to the customizer with the campaign ID
            window.location.href = 'https://quick-site.co.il/customizer-mail/index.php?type=email&content=' + encodeURIComponent(contentTextarea.value);
        });
    });
</script>

<?php include_once 'template/footer.php'; ?> 