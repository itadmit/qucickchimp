<?php
/**
 * הגדרת Mailgun במערכת QuickSite
 * סקריפט זה מדריך את מנהל המערכת כיצד להגדיר את Mailgun ולעדכן את בסיס הנתונים
 */

// Include configuration
require_once dirname(__DIR__) . '/config/config.php';
require_once INCLUDES_PATH . '/functions.php';

// בדיקה האם משתמש מחובר כמנהל
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// בדיקה האם קיים Composer
if (!file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    die('נא להתקין את Composer ולהריץ את הפקודה: <br>composer require mailgun/mailgun-php:^3.0 symfony/http-client php-http/multipart-stream-builder');
}

// בדיקה אם הספרייה של Mailgun הותקנה
$mailgunInstalled = false;
try {
    require_once ROOT_PATH . '/vendor/autoload.php';
    $mailgunInstalled = class_exists('Mailgun\Mailgun');
} catch (Exception $e) {
    $mailgunInstalled = false;
}

// בדיקה אם קובץ ההגדרה של Mailgun קיים
$mailgunClassExists = file_exists(ROOT_PATH . '/includes/MailgunMailer.php');

// הפעולה לביצוע (תצוגה/עדכון)
$action = isset($_POST['action']) ? $_POST['action'] : 'display';

// עדכון בסיס הנתונים
$dbUpdated = false;
if ($action === 'update_db') {
    try {
        $sqlFile = ROOT_PATH . '/install/sql/update_mailgun.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $pdo->exec($sql);
            $dbUpdated = true;
        }
    } catch (PDOException $e) {
        $error = 'שגיאה בעדכון בסיס הנתונים: ' . $e->getMessage();
    }
}

// עדכון הגדרות Mailgun
$configUpdated = false;
if ($action === 'update_config' && isset($_POST['mailgun_api_key']) && isset($_POST['mailgun_domain'])) {
    $apiKey = $_POST['mailgun_api_key'];
    $domain = $_POST['mailgun_domain'];
    
    // עדכון קובץ ההגדרות
    $configPath = ROOT_PATH . '/config/mail_config.php';
    $configContent = <<<PHP
<?php
/**
 * Mailgun Configuration
 */

// Email Configuration - Mailgun (ברירת מחדל)
define('MAIL_SERVICE', 'mailgun'); // 'mailgun' or 'smtp'

// Mailgun Configuration
define('MAILGUN_ENABLED', true);
define('MAILGUN_API_KEY', '{$apiKey}');
define('MAILGUN_DOMAIN', '{$domain}');
define('MAILGUN_FROM_NAME', APP_NAME);
define('MAILGUN_FROM_EMAIL', APP_EMAIL);

// SMTP Configuration (גיבוי)
define('SMTP_ENABLED', false); // מושבת כברירת מחדל
define('SMTP_HOST', 'smtp.mailgun.org');
define('SMTP_PORT', 587);
define('SMTP_SECURITY', 'tls');
define('SMTP_USERNAME', 'postmaster@' . MAILGUN_DOMAIN);
define('SMTP_PASSWORD', ''); // יש להגדיר את הסיסמה אם רוצים להשתמש ב-SMTP כגיבוי
PHP;

    // שמירת הקובץ
    if (file_put_contents($configPath, $configContent)) {
        $configUpdated = true;
    } else {
        $error = 'לא ניתן לשמור את קובץ ההגדרות. וודא שיש הרשאות כתיבה';
    }
}

// בדיקה אם יש Mailgun API key
$hasApiKey = defined('MAILGUN_API_KEY') && !empty(MAILGUN_API_KEY) && MAILGUN_API_KEY !== 'key-XXXXXXXXXXXXXXXXXXX';

// HTML header
?>
<!DOCTYPE html>
<html dir="rtl" lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>הגדרת Mailgun ב-QuickSite</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 30px auto;
        }
        .step {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .step-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .step-content {
            margin-bottom: 15px;
        }
        .step-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            color: white;
            font-weight: bold;
        }
        .status-success {
            background-color: #28a745;
        }
        .status-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .status-danger {
            background-color: #dc3545;
        }
        .instruction {
            background-color: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #6c757d;
            margin-bottom: 15px;
        }
        code {
            background-color: #f1f1f1;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>הגדרת Mailgun במערכת QuickSite</h1>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($configUpdated): ?>
        <div class="alert alert-success" role="alert">
            הגדרות Mailgun עודכנו בהצלחה!
        </div>
        <?php endif; ?>
        
        <?php if ($dbUpdated): ?>
        <div class="alert alert-success" role="alert">
            בסיס הנתונים עודכן בהצלחה!
        </div>
        <?php endif; ?>
        
        <!-- שלב 1: התקנת ספריות -->
        <div class="step">
            <div class="step-title">שלב 1: התקנת ספריות Mailgun</div>
            <div class="step-content">
                הספריות של Mailgun והתלויות שלהן חייבות להיות מותקנות באמצעות Composer.
            </div>
            <div class="step-status <?php echo $mailgunInstalled ? 'status-success' : 'status-danger'; ?>">
                <?php echo $mailgunInstalled ? 'מותקן' : 'לא מותקן'; ?>
            </div>
            
            <?php if (!$mailgunInstalled): ?>
            <div class="instruction">
                <p>יש להריץ את הפקודה הבאה בתיקיית האתר:</p>
                <code>composer require mailgun/mailgun-php:^3.0 symfony/http-client php-http/multipart-stream-builder</code>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- שלב 2: בדיקת קובץ MailgunMailer -->
        <div class="step">
            <div class="step-title">שלב 2: קובץ מחלקת Mailgun</div>
            <div class="step-content">
                המערכת צריכה מחלקה שתעטוף את ה-API של Mailgun.
            </div>
            <div class="step-status <?php echo $mailgunClassExists ? 'status-success' : 'status-danger'; ?>">
                <?php echo $mailgunClassExists ? 'קיים' : 'חסר'; ?>
            </div>
            
            <?php if (!$mailgunClassExists): ?>
            <div class="instruction">
                <p>יש ליצור את הקובץ <code>/includes/MailgunMailer.php</code> עם התוכן המתאים.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- שלב 3: עדכון בסיס הנתונים -->
        <div class="step">
            <div class="step-title">שלב 3: עדכון בסיס הנתונים</div>
            <div class="step-content">
                יש לעדכן את מבנה בסיס הנתונים כדי לתמוך בתכונות החדשות כמו שמירת דומיינים לכל לקוח.
            </div>
            <div class="step-status <?php echo $dbUpdated ? 'status-success' : 'status-warning'; ?>">
                <?php echo $dbUpdated ? 'עודכן' : 'טרם עודכן'; ?>
            </div>
            
            <?php if (!$dbUpdated): ?>
            <div class="instruction">
                <p>לחץ על הכפתור הבא לעדכון בסיס הנתונים:</p>
                <form method="post">
                    <input type="hidden" name="action" value="update_db">
                    <button type="submit" class="btn btn-primary">עדכן בסיס נתונים</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- שלב 4: הגדרת Mailgun -->
        <div class="step">
            <div class="step-title">שלב 4: הגדרת Mailgun</div>
            <div class="step-content">
                יש להגדיר את פרטי החשבון של Mailgun.
            </div>
            <div class="step-status <?php echo $hasApiKey ? 'status-success' : 'status-warning'; ?>">
                <?php echo $hasApiKey ? 'מוגדר' : 'טרם הוגדר'; ?>
            </div>
            
            <?php if (!$hasApiKey || $action === 'show_config_form'): ?>
            <div class="instruction">
                <p>הזן את פרטי החשבון של Mailgun:</p>
                <form method="post">
                    <input type="hidden" name="action" value="update_config">
                    <div class="mb-3">
                        <label for="mailgun_api_key" class="form-label">Mailgun API Key:</label>
                        <input type="text" class="form-control" id="mailgun_api_key" name="mailgun_api_key" 
                               value="<?php echo defined('MAILGUN_API_KEY') ? MAILGUN_API_KEY : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="mailgun_domain" class="form-label">Mailgun Domain:</label>
                        <input type="text" class="form-control" id="mailgun_domain" name="mailgun_domain" 
                               value="<?php echo defined('MAILGUN_DOMAIN') ? MAILGUN_DOMAIN : ''; ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">שמור הגדרות</button>
                </form>
            </div>
            <?php elseif ($hasApiKey): ?>
            <div class="instruction">
                <p>הגדרות Mailgun שלך:</p>
                <ul>
                    <li>API Key: <?php echo substr(MAILGUN_API_KEY, 0, 8) . '...'; ?></li>
                    <li>Domain: <?php echo MAILGUN_DOMAIN; ?></li>
                </ul>
                <form method="post">
                    <input type="hidden" name="action" value="show_config_form">
                    <button type="submit" class="btn btn-outline-primary">ערוך הגדרות</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- סיכום -->
        <div class="step">
            <div class="step-title">סיכום</div>
            <div class="step-content">
                אחרי השלמת כל השלבים, המערכת שלך תשתמש ב-Mailgun לשליחת אימייל.
                כעת לקוחות שלך יוכלו להגדיר דומיינים משלהם לשליחת מיילים.
            </div>
            
            <div class="instruction">
                <p>מעבר למערכת ניהול:</p>
                <a href="../admin/index.php" class="btn btn-success">לוח בקרה</a>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html> 