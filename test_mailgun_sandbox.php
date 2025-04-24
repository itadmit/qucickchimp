<?php
/**
 * סקריפט טסט לשליחת מייל באמצעות Mailgun עם Sandbox Domain
 */

// טעינת הגדרות
require_once 'config/config.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

// כותרת
echo "<h1>בדיקת שליחת מייל באמצעות Mailgun Sandbox</h1>";

// הצגת האפשרויות הקיימות
echo "<pre>";
echo "MAILGUN_API_KEY: " . (defined('MAILGUN_API_KEY') ? substr(MAILGUN_API_KEY, 0, 8) . '...' : 'לא מוגדר') . "\n";
echo "MAILGUN_DOMAIN: " . (defined('MAILGUN_DOMAIN') ? MAILGUN_DOMAIN : 'לא מוגדר') . "\n";
echo "</pre>";

// הגדרות הנמען והשולח
$to = 'itadmit@gmail.com';
$subject = 'בדיקת שליחת מייל מ-Sandbox';
$message = '<h2>בדיקת מייל</h2><p>זהו מייל בדיקה שנשלח מ-Sandbox Domain של Mailgun.</p>';

try {
    // קבלת רשימת הדומיינים מ-Mailgun
    $mailgun = Mailgun\Mailgun::create(MAILGUN_API_KEY);
    
    // לקבל רשימה של הדומיינים
    echo "<h2>רשימת הדומיינים בחשבון:</h2>";
    
    $domains = $mailgun->domains()->index();
    
    // הצגת רשימת הדומיינים
    echo "<ul>";
    $sandboxDomain = null;
    
    foreach ($domains->getDomains() as $domain) {
        $domainName = $domain->getName();
        $domainState = $domain->getState();
        
        echo "<li>$domainName - State: $domainState</li>";
        
        // בדיקה אם זהו sandbox domain
        if (strpos($domainName, 'sandbox') !== false) {
            $sandboxDomain = $domainName;
        }
    }
    echo "</ul>";
    
    // אם יש sandbox domain - ננסה לשלוח דרכו
    if ($sandboxDomain) {
        echo "<h2>נמצא Sandbox Domain: $sandboxDomain</h2>";
        echo "<p>מנסה לשלוח מייל באמצעות הדומיין הזה...</p>";
        
        // הגדרת פרמטרים לשליחה
        $params = [
            'from'    => "Mailgun Sandbox <postmaster@$sandboxDomain>",
            'to'      => $to,
            'subject' => $subject,
            'html'    => $message,
            'text'    => strip_tags($message)
        ];
        
        // שליחת המייל
        $result = $mailgun->messages()->send($sandboxDomain, $params);
        
        // הצגת תוצאות
        echo "<p style='color: green; direction: rtl;'>המייל נשלח בהצלחה!</p>";
        echo "<pre>";
        echo "Message ID: " . $result->getId() . "\n";
        echo "API Response: " . json_encode($result->getMessage(), JSON_PRETTY_PRINT);
        echo "</pre>";
    } else {
        echo "<p style='color: orange; direction: rtl;'>לא נמצא Sandbox Domain בחשבון.</p>";
        
        // במקרה שאין sandbox domain, ננסה ליצור אחד חדש או להשתמש בדומיין הרגיל
        echo "<p>מנסה לשלוח באמצעות הדומיין הרגיל...</p>";
        
        // הגדרת פרמטרים לשליחה
        $params = [
            'from'    => "QuickSite Test <postmaster@" . MAILGUN_DOMAIN . ">",
            'to'      => $to,
            'subject' => $subject,
            'html'    => $message,
            'text'    => strip_tags($message)
        ];
        
        // שליחת המייל
        $result = $mailgun->messages()->send(MAILGUN_DOMAIN, $params);
        
        // הצגת תוצאות
        echo "<p style='color: green; direction: rtl;'>המייל נשלח בהצלחה!</p>";
        echo "<pre>";
        echo "Message ID: " . $result->getId() . "\n";
        echo "API Response: " . json_encode($result->getMessage(), JSON_PRETTY_PRINT);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    // הצגת שגיאות
    echo "<p style='color: red; direction: rtl;'>שגיאה: " . $e->getMessage() . "</p>";
}
?> 