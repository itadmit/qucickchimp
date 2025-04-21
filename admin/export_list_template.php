<?php
ob_start();
require_once '../config/config.php';

// בדיקת התחברות משתמש
requireLogin();

// הגדרת שם הקובץ לייצוא
$filename = 'contact_list_template.csv';

// הגדרת כותרות CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// יצירת ה-CSV
$output = fopen('php://output', 'w');

// הוספת BOM לתמיכה בעברית
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// כותרות העמודות
fputcsv($output, ['אימייל', 'שם פרטי', 'שם משפחה', 'טלפון']);

// שורות דוגמה
fputcsv($output, ['user1@example.com', 'ישראל', 'ישראלי', '050-1234567']);
fputcsv($output, ['user2@example.com', 'חיים', 'כהן', '052-7654321']);
fputcsv($output, ['user3@example.com', 'שרה', 'לוי', '054-9876543']);

fclose($output);
exit;

ob_end_flush();
?> 