<?php
// הגדרות התחברות למסד הנתונים

// נתוני התחברות
$host = 'localhost';
$db   = 'mail_templates';  // שם מסד הנתונים
$user = 'root';            // שם משתמש למסד הנתונים - יש לשנות בהתאם
$pass = '';                // סיסמה למסד הנתונים - יש לשנות בהתאם
$charset = 'utf8mb4';

// יצירת מחרוזת DSN
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// אפשרויות PDO
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // יצירת אובייקט PDO
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // במקרה של שגיאה, רישום שגיאה ללוג
    $errorLog = __DIR__ . '/../db_error.log';
    file_put_contents($errorLog, date('Y-m-d H:i:s') . ' - שגיאת התחברות למסד נתונים: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
    
    // זריקת שגיאה
    throw new PDOException($e->getMessage(), (int)$e->getCode());
} 