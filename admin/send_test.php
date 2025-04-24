<?php
/**
 * שליחת מייל בדיקה
 * מאפשר למשתמש לשלוח אימייל בדיקה כדי לוודא שהדומיין עובד כראוי
 */

// Include configuration and functions
require_once '../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth.php';

// וודא שהמשתמש מחובר
checkUserLogin();

// משתנים להצגה בדף
$userId = $_SESSION['user_id'];
$pageTitle = 'שליחת אימייל בדיקה';
$errorMessage = '';
$successMessage = '';
$domain = isset($_GET['domain']) ? $_GET['domain'] : '';

// בדיקה אם הדומיין שייך למשתמש ומאומת
$domainValid = false;
$defaultEmail = '';

try {
    global $pdo;
    $stmt = $pdo->prepare("SELECT domain, verified FROM user_domains WHERE user_id = ? AND domain = ?");
    $stmt->execute([$userId, $domain]);
    $domainInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($domainInfo && $domainInfo['verified']) {
        $domainValid = true;
        $defaultEmail = 'info@' . $domain;
    }
} catch (Exception $e) {
    $errorMessage = 'שגיאה בבדיקת הדומיין: ' . $e->getMessage();
}

// טיפול בשליחת אימייל בדיקה
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_test') {
    // וידוא שהדומיין תקין
    if (!$domainValid) {
        $errorMessage = 'הדומיין אינו מאומת או אינו שייך לך';
    } else {
        $to = trim($_POST['to']);
        $subject = trim($_POST['subject']);
        $message = trim($_POST['message']);
        $fromName = trim($_POST['from_name']);
        $fromEmail = trim($_POST['from_email']);
        
        // בדיקות תקינות
        if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'כתובת הנמען אינה תקינה';
        } elseif (empty($subject)) {
            $errorMessage = 'נא להזין נושא';
        } elseif (empty($message)) {
            $errorMessage = 'נא להזין תוכן';
        } elseif (empty($fromName)) {
            $errorMessage = 'נא להזין שם שולח';
        } elseif (empty($fromEmail) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = 'כתובת השולח אינה תקינה';
        } else {
            // בדיקה שהאימייל בדומיין הנכון
            $emailDomain = substr(strrchr($fromEmail, "@"), 1);
            if ($emailDomain !== $domain) {
                $errorMessage = 'כתובת השולח חייבת להשתמש בדומיין ' . $domain;
            } else {
                // שליחת האימייל
                $result = sendCustomDomainEmail(
                    $userId,
                    $to,
                    $subject,
                    $message,
                    $fromName,
                    $fromEmail
                );
                
                if ($result) {
                    $successMessage = 'אימייל הבדיקה נשלח בהצלחה!';
                } else {
                    $errorMessage = 'שגיאה בשליחת האימייל. נסה שוב מאוחר יותר.';
                }
            }
        }
    }
}

// כולל את Header
include_once ADMIN_PATH . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $pageTitle; ?></h1>
        <a href="domains.php" class="btn btn-outline-primary">חזרה לניהול דומיינים</a>
    </div>
    
    <?php if (!$domainValid): ?>
    <div class="alert alert-warning" role="alert">
        הדומיין שנבחר אינו מאומת או אינו שייך לך. <a href="domains.php">חזור לניהול דומיינים</a> ובחר דומיין מאומת.
    </div>
    <?php else: ?>
    
    <?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $errorMessage; ?>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($successMessage)): ?>
    <div class="alert alert-success" role="alert">
        <?php echo $successMessage; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">שליחת אימייל בדיקה מהדומיין <?php echo htmlspecialchars($domain); ?></h5>
        </div>
        <div class="card-body">
            <p class="card-text">
                שליחת אימייל בדיקה מאפשרת לך לוודא שהדומיין שלך מוגדר כראוי. 
                המערכת תשלח אימייל מהכתובת שתבחר תחת הדומיין <?php echo htmlspecialchars($domain); ?>.
            </p>
            
            <form method="post">
                <input type="hidden" name="action" value="send_test">
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="from_name" class="form-label">שם השולח:</label>
                        <input type="text" class="form-control" id="from_name" name="from_name" 
                               placeholder="השם שיופיע כשולח" value="<?php echo htmlspecialchars($_POST['from_name'] ?? 'QuickSite'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="from_email" class="form-label">כתובת השולח:</label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="from_email" name="from_email" 
                                   placeholder="כתובת אימייל בדומיין שלך" value="<?php echo htmlspecialchars($_POST['from_email'] ?? $defaultEmail); ?>" required>
                            <span class="input-group-text bg-light">@<?php echo htmlspecialchars($domain); ?></span>
                        </div>
                        <div class="form-text">חייב להיות בדומיין <?php echo htmlspecialchars($domain); ?></div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="to" class="form-label">כתובת הנמען:</label>
                    <input type="email" class="form-control" id="to" name="to" 
                           placeholder="למי לשלוח את המייל" value="<?php echo htmlspecialchars($_POST['to'] ?? ''); ?>" required>
                    <div class="form-text">כתובת אימייל שלך, כדי שתוכל לראות שהמייל מגיע</div>
                </div>
                
                <div class="mb-3">
                    <label for="subject" class="form-label">נושא:</label>
                    <input type="text" class="form-control" id="subject" name="subject" 
                           placeholder="נושא האימייל" value="<?php echo htmlspecialchars($_POST['subject'] ?? 'בדיקת שליחת אימייל - ' . $domain); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="message" class="form-label">תוכן:</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($_POST['message'] ?? "שלום,\n\nזהו אימייל בדיקה שנשלח מהדומיין $domain.\n\nאם קיבלת אימייל זה, משמעות הדבר היא שהמערכת עובדת כראוי ואתה יכול להתחיל לשלוח אימיילים מהדומיין שלך!\n\nבברכה,\nצוות QuickSite"); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">שלח אימייל בדיקה</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// כולל את Footer
include_once ADMIN_PATH . '/includes/footer.php';
?> 