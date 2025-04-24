<?php
/**
 * Unsubscribe Page
 * 
 * מאפשר למנויים להסיר את עצמם מרשימת התפוצה
 */

require_once 'config/config.php';

// כותרת הדף
$pageTitle = 'הסרה מרשימת תפוצה';

// פרמטרים נדרשים
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$message = '';
$status = '';

// אימות הפרמטרים
if (empty($email) || empty($token)) {
    $message = 'הקישור להסרה אינו תקין. נא לוודא שהקישור מלא.';
    $status = 'error';
} else {
    // בדיקה שהטוקן תואם לאימייל
    $expectedToken = md5($email . 'salt');
    
    if ($token !== $expectedToken) {
        $message = 'הקישור להסרה אינו תקין. נא לנסות שוב.';
        $status = 'error';
    } else {
        try {
            // חיפוש המנוי בבסיס הנתונים
            $stmt = $pdo->prepare("SELECT id, first_name, is_subscribed FROM subscribers WHERE email = ?");
            $stmt->execute([$email]);
            $subscriber = $stmt->fetch();
            
            if (!$subscriber) {
                $message = 'כתובת האימייל אינה רשומה במערכת.';
                $status = 'warning';
            } else {
                // אם המנוי כבר מסומן כמוסר
                if ($subscriber['is_subscribed'] == 0) {
                    $message = 'כתובת האימייל שלך כבר הוסרה מרשימת התפוצה.';
                    $status = 'info';
                } else {
                    // עדכון סטטוס המנוי
                    $updateStmt = $pdo->prepare("UPDATE subscribers SET is_subscribed = 0 WHERE id = ?");
                    $updateStmt->execute([$subscriber['id']]);
                    
                    $name = !empty($subscriber['first_name']) ? $subscriber['first_name'] : 'מנוי יקר';
                    $message = $name . ', כתובת האימייל שלך הוסרה בהצלחה מרשימת התפוצה.';
                    $status = 'success';
                }
            }
        } catch (PDOException $e) {
            $message = 'אירעה שגיאה בעת עדכון הפרטים. נא לנסות שוב מאוחר יותר.';
            $status = 'error';
            error_log('Unsubscribe error: ' . $e->getMessage());
        }
    }
}

// HTML header
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-6">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800"><?php echo $pageTitle; ?></h1>
            </div>
            
            <?php if ($status === 'success'): ?>
            <div class="bg-green-50 border-r-4 border-green-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm text-green-800"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
            <?php elseif ($status === 'error'): ?>
            <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 112 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm text-red-800"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
            <?php elseif ($status === 'warning'): ?>
            <div class="bg-yellow-50 border-r-4 border-yellow-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 112 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm text-yellow-800"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
            <?php elseif ($status === 'info'): ?>
            <div class="bg-blue-50 border-r-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-9a1 1 0 112 0v4a1 1 0 11-2 0V9zm1-4a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm text-blue-800"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="text-center mt-6">
                <a href="<?php echo APP_URL; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    חזרה לדף הבית
                </a>
            </div>
        </div>
        
        <div class="bg-gray-50 px-6 py-4 border-t">
            <p class="text-xs text-gray-500 text-center">
                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. כל הזכויות שמורות.
            </p>
        </div>
    </div>
</body>
</html> 