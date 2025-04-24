<?php
/**
 * סקריפט טסט לשליחת מייל באמצעות Mailgun
 */

// טעינת הגדרות
require_once 'config/config.php';
require_once 'includes/functions.php';

// כותרת
echo "<h1>בדיקת שליחת מייל באמצעות Mailgun</h1>";

// הגדרות הנמען
$to = 'itadmit@gmail.com';
$subject = 'בדיקת שליחת מייל מ-QuickSite';
$message = '<h2>בדיקת מייל</h2><p>זהו מייל בדיקה שנשלח מהמערכת QuickSite.</p>';
$fromName = 'מערכת QuickSite';
$fromEmail = MAILGUN_FROM_EMAIL;

// ניסיון שליחת המייל
try {
    // בדיקה אם Mailgun מופעל
    if (!defined('MAILGUN_ENABLED') || !MAILGUN_ENABLED) {
        throw new Exception('שירות Mailgun אינו מופעל במערכת');
    }

    // טעינת מחלקת Mailgun
    if (!class_exists('MailgunMailer')) {
        require_once 'includes/MailgunMailer.php';
    }

    // יצירת הודעת הצלחה למסך
    echo "<p style='direction: rtl;'>ניסיון שליחת מייל ל-$to...</p>";
    
    // שליחת המייל באמצעות פונקציית sendEmail
    $result = sendEmail(
        $to,
        $subject,
        $message,
        $fromName,
        $fromEmail
    );
    
    // הצגת תוצאות השליחה
    if ($result) {
        echo "<p style='color: green; direction: rtl;'>המייל נשלח בהצלחה!</p>";
        echo "<pre style='direction: ltr;'>";
        echo "To: $to\n";
        echo "Subject: $subject\n";
        echo "From: $fromName <$fromEmail>\n";
        echo "</pre>";
    } else {
        echo "<p style='color: red; direction: rtl;'>שגיאה בשליחת המייל. בדוק את קובץ הלוג לפרטים נוספים.</p>";
    }
    
} catch (Exception $e) {
    // הצגת שגיאות
    echo "<p style='color: red; direction: rtl;'>שגיאה: " . $e->getMessage() . "</p>";
}

// אפשרות לשליחה ישירה דרך API של Mailgun (אלטרנטיבה)
echo "<h2>ניסיון שליחה ישירה דרך API</h2>";

try {
    // יצירת אובייקט Mailgun ישירות
    $mailgun = Mailgun\Mailgun::create(MAILGUN_API_KEY);
    
    // הגדרת פרמטרים לשליחה
    $params = [
        'from'    => "$fromName <$fromEmail>",
        'to'      => $to,
        'subject' => $subject . ' (שליחה ישירה)',
        'html'    => $message . '<p>זהו מייל שנשלח ישירות דרך API של Mailgun.</p>',
        'text'    => strip_tags($message) . "\n\nזהו מייל שנשלח ישירות דרך API של Mailgun."
    ];
    
    // שליחת המייל
    $result = $mailgun->messages()->send(MAILGUN_DOMAIN, $params);
    
    // הצגת תוצאות
    echo "<p style='color: green; direction: rtl;'>המייל נשלח בהצלחה באופן ישיר!</p>";
    echo "<pre style='direction: ltr;'>";
    echo "Message ID: " . $result->getId() . "\n";
    echo "API Response: " . json_encode($result->getMessage(), JSON_PRETTY_PRINT);
    echo "</pre>";
    
} catch (Exception $e) {
    // הצגת שגיאות
    echo "<p style='color: red; direction: rtl;'>שגיאה בשליחה ישירה: " . $e->getMessage() . "</p>";
}
?> 