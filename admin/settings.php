<?php
require_once '../config/config.php';

// Set page title
$pageTitle = 'הגדרות';
$pageDescription = 'ניהול הגדרות המערכת והחשבון';

// Get user ID
$userId = $_SESSION['user_id'] ?? 0;

// Initialize error message
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check which form was submitted
    if (isset($_POST['account_settings'])) {
        // Handle account settings form
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $timezone = sanitize($_POST['timezone'] ?? 'Asia/Jerusalem');
        $language = sanitize($_POST['language'] ?? 'he');
        
        // Validate input
        if (empty($fullName)) {
            $error = 'שם מלא הוא שדה חובה';
        } elseif (empty($email)) {
            $error = 'כתובת אימייל היא שדה חובה';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'כתובת האימייל אינה תקינה';
        } else {
            // Check if email already exists for another user
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'כתובת האימייל כבר קיימת במערכת';
            } else {
                // Update user account settings
                try {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET full_name = ?, email = ?, timezone = ?, language = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    
                    $result = $stmt->execute([
                        $fullName,
                        $email,
                        $timezone,
                        $language,
                        $userId
                    ]);
                    
                    if ($result) {
                        $success = 'הגדרות החשבון עודכנו בהצלחה';
                    } else {
                        $error = 'אירעה שגיאה בעת עדכון הגדרות החשבון';
                    }
                } catch (PDOException $e) {
                    $error = 'אירעה שגיאה בעת עדכון הגדרות החשבון: ' . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['email_settings'])) {
        // Handle email settings form
        $senderName = sanitize($_POST['sender_name'] ?? '');
        $senderEmail = sanitize($_POST['sender_email'] ?? '');
        $replyToEmail = sanitize($_POST['reply_to_email'] ?? '');
        
        // Validate input
        if (empty($senderName)) {
            $error = 'שם השולח הוא שדה חובה';
        } elseif (empty($senderEmail)) {
            $error = 'אימייל השולח הוא שדה חובה';
        } elseif (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'אימייל השולח אינו תקין';
        } elseif (!empty($replyToEmail) && !filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'אימייל ה"השב ל" אינו תקין';
        } else {
            // Update user email settings
            try {
                // Check if user_settings table exists and create if not
                $stmt = $pdo->query("SHOW TABLES LIKE 'user_settings'");
                if ($stmt->rowCount() === 0) {
                    $pdo->exec("
                        CREATE TABLE user_settings (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            setting_key VARCHAR(100) NOT NULL,
                            setting_value TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            UNIQUE KEY (user_id, setting_key)
                        )
                    ");
                }
                
                // Use INSERT ... ON DUPLICATE KEY UPDATE for each setting
                $settings = [
                    'sender_name' => $senderName,
                    'sender_email' => $senderEmail,
                    'reply_to_email' => $replyToEmail
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("
                        INSERT INTO user_settings (user_id, setting_key, setting_value) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$userId, $key, $value, $value]);
                }
                
                $success = 'הגדרות האימייל עודכנו בהצלחה';
            } catch (PDOException $e) {
                $error = 'אירעה שגיאה בעת עדכון הגדרות האימייל: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['password_change'])) {
        // Handle password change form
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate input
        if (empty($currentPassword)) {
            $error = 'סיסמה נוכחית היא שדה חובה';
        } elseif (empty($newPassword)) {
            $error = 'סיסמה חדשה היא שדה חובה';
        } elseif (strlen($newPassword) < 8) {
            $error = 'הסיסמה החדשה חייבת להכיל לפחות 8 תווים';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'הסיסמאות אינן תואמות';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                $error = 'הסיסמה הנוכחית אינה נכונה';
            } else {
                // Update password
                try {
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $result = $stmt->execute([$hashedPassword, $userId]);
                    
                    if ($result) {
                        $success = 'הסיסמה עודכנה בהצלחה';
                    } else {
                        $error = 'אירעה שגיאה בעת עדכון הסיסמה';
                    }
                } catch (PDOException $e) {
                    $error = 'אירעה שגיאה בעת עדכון הסיסמה: ' . $e->getMessage();
                }
            }
        }
    } elseif (isset($_POST['notification_settings'])) {
        // Handle notification settings form
        $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
        $subscriberNotifications = isset($_POST['subscriber_notifications']) ? 1 : 0;
        $campaignNotifications = isset($_POST['campaign_notifications']) ? 1 : 0;
        
        // Update user notification settings
        try {
            // Check if user_settings table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'user_settings'");
            if ($stmt->rowCount() === 0) {
                $pdo->exec("
                    CREATE TABLE user_settings (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        setting_key VARCHAR(100) NOT NULL,
                        setting_value TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY (user_id, setting_key)
                    )
                ");
            }
            
            // Use INSERT ... ON DUPLICATE KEY UPDATE for each setting
            $settings = [
                'email_notifications' => $emailNotifications,
                'subscriber_notifications' => $subscriberNotifications,
                'campaign_notifications' => $campaignNotifications
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO user_settings (user_id, setting_key, setting_value) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$userId, $key, $value, $value]);
            }
            
            $success = 'הגדרות ההתראות עודכנו בהצלחה';
        } catch (PDOException $e) {
            $error = 'אירעה שגיאה בעת עדכון הגדרות ההתראות: ' . $e->getMessage();
        }
    }
}

// Get user data and settings
try {
    // Get user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch();
    
    // Get user settings
    $userSettings = [];
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM user_settings WHERE user_id = ?");
        $stmt->execute([$userId]);
        while ($row = $stmt->fetch()) {
            $userSettings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Table might not exist yet
    }
} catch (PDOException $e) {
    $error = 'אירעה שגיאה בעת טעינת הנתונים: ' . $e->getMessage();
}

// Include header
include_once 'template/header.php';
?>

<!-- Settings Tabs -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="border-b border-gray-200">
        <nav class="flex -mb-px">
            <a href="#account" id="tab-account" class="text-gray-700 py-4 px-6 block font-medium text-center border-b-2 border-purple-500">
                <i class="ri-user-settings-line ml-1"></i>
                פרטי חשבון
            </a>
            <a href="#email" id="tab-email" class="text-gray-500 hover:text-gray-700 py-4 px-6 block font-medium text-center border-b-2 border-transparent">
                <i class="ri-mail-settings-line ml-1"></i>
                הגדרות אימייל
            </a>
            <a href="#security" id="tab-security" class="text-gray-500 hover:text-gray-700 py-4 px-6 block font-medium text-center border-b-2 border-transparent">
                <i class="ri-shield-keyhole-line ml-1"></i>
                אבטחה
            </a>
            <a href="#notifications" id="tab-notifications" class="text-gray-500 hover:text-gray-700 py-4 px-6 block font-medium text-center border-b-2 border-transparent">
                <i class="ri-notification-2-line ml-1"></i>
                התראות
            </a>
        </nav>
    </div>
    
    <?php if ($error): ?>
        <div class="bg-red-50 border-r-4 border-red-500 p-4 m-6">
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
    
    <?php if ($success): ?>
        <div class="bg-green-50 border-r-4 border-green-500 p-4 m-6">
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
    
    <!-- Account Settings Tab -->
    <div id="panel-account" class="tab-panel p-6">
        <form method="POST" action="">
            <div class="max-w-3xl mx-auto space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">פרטי חשבון</h3>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="full_name" class="block text-sm font-medium text-gray-700 mb-1">שם מלא <span class="text-red-500">*</span></label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">כתובת אימייל <span class="text-red-500">*</span></label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                            </div>
                            
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">אזור זמן</label>
                                <select id="timezone" name="timezone" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                                    <option value="Asia/Jerusalem" <?php echo ($userData['timezone'] ?? 'Asia/Jerusalem') === 'Asia/Jerusalem' ? 'selected' : ''; ?>>ישראל (Asia/Jerusalem)</option>
                                    <option value="Europe/London" <?php echo ($userData['timezone'] ?? '') === 'Europe/London' ? 'selected' : ''; ?>>לונדון (GMT)</option>
                                    <option value="America/New_York" <?php echo ($userData['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>ניו יורק (EST/EDT)</option>
                                    <option value="Europe/Paris" <?php echo ($userData['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : ''; ?>>פריז (CET/CEST)</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700 mb-1">שפה</label>
                                <select id="language" name="language" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                                    <option value="he" <?php echo ($userData['language'] ?? 'he') === 'he' ? 'selected' : ''; ?>>עברית</option>
                                    <option value="en" <?php echo ($userData['language'] ?? '') === 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6 flex justify-end">
                    <button type="submit" name="account_settings" class="px-6 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center">
                        <i class="ri-save-line ml-1"></i>
                        שמור הגדרות
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Email Settings Tab -->
    <div id="panel-email" class="tab-panel p-6 hidden">
        <form method="POST" action="">
            <div class="max-w-3xl mx-auto space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">הגדרות שולח אימייל</h3>
                    <p class="text-gray-500 text-sm mb-4">הגדרות אלו ישמשו בכל האימיילים הנשלחים מהמערכת.</p>
                    
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="sender_name" class="block text-sm font-medium text-gray-700 mb-1">שם השולח <span class="text-red-500">*</span></label>
                                <input type="text" id="sender_name" name="sender_name" value="<?php echo htmlspecialchars($userSettings['sender_name'] ?? ''); ?>" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                                <p class="mt-1 text-sm text-gray-500">השם שיופיע כשולח האימייל</p>
                            </div>
                            
                            <div>
                                <label for="sender_email" class="block text-sm font-medium text-gray-700 mb-1">אימייל השולח <span class="text-red-500">*</span></label>
                                <input type="email" id="sender_email" name="sender_email" value="<?php echo htmlspecialchars($userSettings['sender_email'] ?? ''); ?>" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                                <p class="mt-1 text-sm text-gray-500">כתובת האימייל שתופיע כשולח ההודעה</p>
                            </div>
                            
                            <div>
                                <label for="reply_to_email" class="block text-sm font-medium text-gray-700 mb-1">אימייל לתשובה (Reply-To)</label>
                                <input type="email" id="reply_to_email" name="reply_to_email" value="<?php echo htmlspecialchars($userSettings['reply_to_email'] ?? ''); ?>"
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                                <p class="mt-1 text-sm text-gray-500">כתובת אימייל לקבלת תשובות (אם שונה מאימייל השולח)</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6 flex justify-end">
                    <button type="submit" name="email_settings" class="px-6 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center">
                        <i class="ri-save-line ml-1"></i>
                        שמור הגדרות
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Security Tab -->
    <div id="panel-security" class="tab-panel p-6 hidden">
        <form method="POST" action="">
            <div class="max-w-3xl mx-auto space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">החלפת סיסמה</h3>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">סיסמה נוכחית <span class="text-red-500">*</span></label>
                                <input type="password" id="current_password" name="current_password" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                            </div>
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">סיסמה חדשה <span class="text-red-500">*</span></label>
                                <input type="password" id="new_password" name="new_password" minlength="8" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                                <p class="mt-1 text-sm text-gray-500">לפחות 8 תווים</p>
                            </div>
                            
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">אימות סיסמה חדשה <span class="text-red-500">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" minlength="8" required
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-purple-500 focus:border-purple-500 py-3">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6 flex justify-end">
                    <button type="submit" name="password_change" class="px-6 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center">
                        <i class="ri-lock-password-line ml-1"></i>
                        עדכן סיסמה
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Notifications Tab -->
    <div id="panel-notifications" class="tab-panel p-6 hidden">
        <form method="POST" action="">
            <div class="max-w-3xl mx-auto space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-4">הגדרות התראות</h3>
                    <div class="bg-gray-50 p-4 rounded-md">
                        <div class="space-y-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="email_notifications" name="email_notifications" 
                                      <?php echo (!empty($userSettings['email_notifications']) && $userSettings['email_notifications'] == 1) ? 'checked' : ''; ?>
                                      class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <label for="email_notifications" class="mr-2 block text-sm text-gray-700">
                                    קבל התראות במייל
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="subscriber_notifications" name="subscriber_notifications" 
                                      <?php echo (!empty($userSettings['subscriber_notifications']) && $userSettings['subscriber_notifications'] == 1) ? 'checked' : ''; ?>
                                      class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <label for="subscriber_notifications" class="mr-2 block text-sm text-gray-700">
                                    קבל התראות על מנויים חדשים
                                </label>
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" id="campaign_notifications" name="campaign_notifications" 
                                      <?php echo (!empty($userSettings['campaign_notifications']) && $userSettings['campaign_notifications'] == 1) ? 'checked' : ''; ?>
                                      class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded">
                                <label for="campaign_notifications" class="mr-2 block text-sm text-gray-700">
                                    קבל התראות על ביצועי קמפיינים
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="border-t pt-6 flex justify-end">
                    <button type="submit" name="notification_settings" class="px-6 py-2.5 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center">
                        <i class="ri-save-line ml-1"></i>
                        שמור הגדרות
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Tabs functionality
    const tabs = ['account', 'email', 'security', 'notifications'];
    
    // Function to activate tab
    function activateTab(tabId) {
        // Hide all tab panels
        tabs.forEach(tab => {
            document.getElementById(`panel-${tab}`).classList.add('hidden');
            document.getElementById(`tab-${tab}`).classList.remove('border-purple-500', 'text-gray-700');
            document.getElementById(`tab-${tab}`).classList.add('border-transparent', 'text-gray-500');
        });
        
        // Show selected tab panel
        document.getElementById(`panel-${tabId}`).classList.remove('hidden');
        document.getElementById(`tab-${tabId}`).classList.add('border-purple-500', 'text-gray-700');
        document.getElementById(`tab-${tabId}`).classList.remove('border-transparent', 'text-gray-500');
        
        // Update URL hash
        window.location.hash = tabId;
    }
    
    // Add click event listeners to tabs
    tabs.forEach(tab => {
        document.getElementById(`tab-${tab}`).addEventListener('click', (e) => {
            e.preventDefault();
            activateTab(tab);
        });
    });
    
    // Check if URL has a hash and activate that tab
    document.addEventListener('DOMContentLoaded', () => {
        const hash = window.location.hash.substring(1);
        if (hash && tabs.includes(hash)) {
            activateTab(hash);
        }
    });
</script>

<?php include_once 'template/footer.php'; ?> 