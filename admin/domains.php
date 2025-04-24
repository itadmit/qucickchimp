<?php
/**
 * ניהול דומיינים לדיוור
 * מאפשר למשתמשים להוסיף ולנהל את הדומיינים שלהם לשליחת מיילים
 */

// Include configuration and functions
require_once '../config/config.php';
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth.php';

// וודא שהמשתמש מחובר
checkUserLogin();

// משתנים להצגה בדף
$userId = $_SESSION['user_id'];
$pageTitle = 'ניהול דומיינים לדיוור';
$errorMessage = '';
$successMessage = '';
$domains = [];
$dnsRecords = [];

// בדיקה אם הספרייה של Mailgun קיימת
$mailgunAvailable = false;
try {
    if (defined('MAILGUN_ENABLED') && MAILGUN_ENABLED) {
        if (!class_exists('MailgunMailer')) {
            require_once ROOT_PATH . '/includes/MailgunMailer.php';
        }
        $mailgunAvailable = class_exists('Mailgun\Mailgun');
    }
} catch (Exception $e) {
    $errorMessage = 'שגיאה בטעינת הספריות: ' . $e->getMessage();
}

// טיפול בפעולות
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // פעולת הוספת דומיין חדש
    if (isset($_POST['action']) && $_POST['action'] === 'add_domain' && isset($_POST['domain'])) {
        $domain = trim($_POST['domain']);
        
        // בדיקת תקינות הדומיין
        if (empty($domain)) {
            $errorMessage = 'נא להזין דומיין';
        } elseif (!preg_match('/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $domain)) {
            $errorMessage = 'נא להזין דומיין תקין (לדוגמה: example.com)';
        } else {
            // הוספת הדומיין
            $result = manageCustomerDomain($userId, $domain, 'add');
            
            if ($result['success']) {
                $successMessage = $result['message'];
                $dnsRecords = $result['dns_records'] ?? [];
            } else {
                $errorMessage = $result['message'];
            }
        }
    }
    
    // פעולת בדיקת אימות דומיין
    if (isset($_POST['action']) && $_POST['action'] === 'verify_domain' && isset($_POST['domain'])) {
        $domain = trim($_POST['domain']);
        
        $result = manageCustomerDomain($userId, $domain, 'verify');
        
        if ($result['success']) {
            if ($result['verified']) {
                $successMessage = 'הדומיין אומת בהצלחה!';
            } else {
                $errorMessage = 'הדומיין עדיין לא אומת. נא לוודא שהוספת את כל רשומות ה-DNS.';
                $dnsRecords = $result['dns_records'] ?? [];
            }
        } else {
            $errorMessage = $result['message'];
        }
    }
}

// קבלת רשימת הדומיינים של המשתמש
if ($mailgunAvailable) {
    $result = manageCustomerDomain($userId, '', 'list');
    if ($result['success']) {
        $domains = $result['domains'] ?? [];
    }
}

// כולל את Header
include_once ADMIN_PATH . '/includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $pageTitle; ?></h1>
    </div>
    
    <?php if (!$mailgunAvailable): ?>
    <div class="alert alert-warning" role="alert">
        שירות שליחת האימייל אינו זמין כעת. אנא פנה למנהל המערכת.
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
    
    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">הדומיינים שלי</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        הוספת דומיין משלך מאפשרת לך לשלוח הודעות בשם החברה שלך (מכתובת כמו info@your-domain.com)
                        וכך לשפר את אחוזי המסירה והפתיחה של האימיילים שלך.
                    </p>
                    
                    <?php if (empty($domains)): ?>
                    <div class="alert alert-info">
                        אין לך עדיין דומיינים מוגדרים. הוסף את הדומיין הראשון שלך באמצעות הטופס.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>דומיין</th>
                                    <th>מאומת</th>
                                    <th>נוסף בתאריך</th>
                                    <th>פעולות</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($domains as $domain): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($domain['domain']); ?></td>
                                    <td>
                                        <?php if ($domain['verified']): ?>
                                        <span class="badge bg-success">מאומת</span>
                                        <?php else: ?>
                                        <span class="badge bg-warning text-dark">ממתין לאימות</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($domain['created_at'])); ?></td>
                                    <td>
                                        <?php if (!$domain['verified']): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="action" value="verify_domain">
                                            <input type="hidden" name="domain" value="<?php echo htmlspecialchars($domain['domain']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">בדוק אימות</button>
                                        </form>
                                        <?php else: ?>
                                        <a href="send_test.php?domain=<?php echo urlencode($domain['domain']); ?>" class="btn btn-sm btn-outline-success">שלח אימייל בדיקה</a>
                                        <?php endif; ?>
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
        
        <div class="col-md-5">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">הוספת דומיין חדש</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_domain">
                        <div class="mb-3">
                            <label for="domain" class="form-label">דומיין:</label>
                            <input type="text" class="form-control" id="domain" name="domain" 
                                   placeholder="לדוגמה: example.com" required>
                            <div class="form-text">הזן את הדומיין שלך ללא www או http</div>
                        </div>
                        <button type="submit" class="btn btn-primary">הוסף דומיין</button>
                    </form>
                </div>
            </div>
            
            <?php if (!empty($dnsRecords)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">רשומות DNS לאימות</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        להלן רשומות ה-DNS שיש להוסיף לדומיין שלך. לאחר הוספת הרשומות, ייתכן שיידרשו עד 48 שעות לעדכון ברחבי האינטרנט.
                    </p>
                    
                    <div class="alert alert-info">
                        הוסף את הרשומות הבאות באמצעות ממשק הניהול של הדומיין שלך (אצל ספק האחסון).
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>סוג</th>
                                    <th>שם</th>
                                    <th>ערך</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dnsRecords as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($record['name'] ?? ''); ?></td>
                                    <td>
                                        <code class="small"><?php echo htmlspecialchars($record['value'] ?? ''); ?></code>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <form method="post" class="mt-3">
                        <input type="hidden" name="action" value="verify_domain">
                        <input type="hidden" name="domain" value="<?php echo htmlspecialchars($_POST['domain'] ?? ''); ?>">
                        <button type="submit" class="btn btn-primary">בדוק אימות</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php endif; ?>
</div>

<?php
// כולל את Footer
include_once ADMIN_PATH . '/includes/footer.php';
?> 