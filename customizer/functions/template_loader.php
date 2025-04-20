<?php
/**
 * טעינת תבנית מקובץ
 */
function loadTemplateFromFile($templateId) {
    $templatePath = '';
    
    // Find template path
    switch ($templateId) {
        case 1:
            $templatePath = 'basic';
            break;
        case 2:
            $templatePath = 'business';
            break;
        case 3:
            $templatePath = 'sales';
            break;
        default:
            $templatePath = 'basic';
    }
    
    $templateFile = __DIR__ . '/../templates/' . $templatePath . '/template.html';
    if (file_exists($templateFile)) {
        return file_get_contents($templateFile);
    } else {
        // Fallback to simple template
        return getFallbackTemplate();
    }
}

/**
 * תבנית ברירת מחדל למקרה שאין קובץ תבנית
 */
function getFallbackTemplate() {
    return '
    <!DOCTYPE html>
    <html lang="he" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>דף נחיתה</title>
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
        <style>
            @import url(\'https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap\');
            body {
                font-family: \'Heebo\', sans-serif;
            }

        </style>
    </head>
    <body>
        <header class="bg-indigo-600 text-white py-12">
            <div class="container mx-auto px-4 text-center">
                <h1 class="text-4xl font-bold mb-4">כותרת דף הנחיתה שלך</h1>
                <p class="text-xl mb-8">תיאור קצר של ההצעה או השירות שלך</p>
                <a href="#form" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-bold text-lg hover:bg-gray-100">להרשמה</a>
            </div>
        </header>
        
        <main>
            <section class="py-16 bg-white">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold mb-8">היתרונות שלנו</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="p-6">
                            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-check-line text-3xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-2">יתרון ראשון</h3>
                            <p class="text-gray-600">תיאור קצר של היתרון הראשון שלך</p>
                        </div>
                        <div class="p-6">
                            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-check-line text-3xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-2">יתרון שני</h3>
                            <p class="text-gray-600">תיאור קצר של היתרון השני שלך</p>
                        </div>
                        <div class="p-6">
                            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="ri-check-line text-3xl text-indigo-600"></i>
                            </div>
                            <h3 class="text-xl font-bold mb-2">יתרון שלישי</h3>
                            <p class="text-gray-600">תיאור קצר של היתרון השלישי שלך</p>
                        </div>
                    </div>
                </div>
            </section>
            
            <section id="form" class="py-16 bg-gray-100">
                <div class="container mx-auto px-4">
                    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
                        <h2 class="text-2xl font-bold mb-6 text-center">השאירו פרטים</h2>
                        <form action="/submit-form" method="post" class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">שם מלא</label>
                                <input type="text" id="name" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">אימייל</label>
                                <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500" required>
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">טלפון</label>
                                <input type="tel" id="phone" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">שלח</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </main>
        
        <footer class="bg-gray-800 text-white py-8">
            <div class="container mx-auto px-4 text-center">
                <p>&copy; 2023 החברה שלך. כל הזכויות שמורות.</p>
            </div>
        </footer>
    </body>
    </html>
    ';
}

/**
 * פונקציה לשינוי תבנית עבור דף קיים
 */
function changeTemplate($landingPageId, $templateId, $userId) {
    global $pdo;
    
    // טען את התבנית החדשה
    $newContent = loadTemplateFromFile($templateId);
    
    if (!empty($newContent)) {
        try {
            // עדכן את התוכן ואת מזהה התבנית
            $stmt = $pdo->prepare("UPDATE landing_pages SET 
                content = ?, 
                template_id = ?,
                updated_at = NOW() 
                WHERE id = ? AND user_id = ?");
            
            return $stmt->execute([$newContent, $templateId, $landingPageId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    return false;
}

/**
 * פונקציה שמחזירה רשימת תבניות זמינות
 */
function getAvailableTemplates() {
    return [
        1 => ['id' => 1, 'name' => 'תבנית בסיסית', 'slug' => 'basic', 'thumbnail' => 'basic/thumbnail.svg'],
        2 => ['id' => 2, 'name' => 'תבנית עסקית', 'slug' => 'business', 'thumbnail' => 'business/thumbnail.svg'],
        3 => ['id' => 3, 'name' => 'תבנית מכירות', 'slug' => 'sales', 'thumbnail' => 'sales/thumbnail.svg']
    ];
}