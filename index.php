<?php
require_once 'config/config.php';

// Check if user is already logged in, redirect to admin
if (isLoggedIn()) {
    redirect(APP_URL . '/admin/index.php');
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - פלטפורמת דיוור ודפי נחיתה בעברית</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@100..900&display=swap');
        body {
            font-family: "Noto Sans Hebrew", sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, #FFD166 0%, #EF476F 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <h1 class="text-3xl font-bold text-purple-600">MailHebrew</h1>
            </div>
            <nav>
                <ul class="flex space-x-6">
                    <li><a href="#features" class="text-gray-600 hover:text-purple-600">יתרונות</a></li>
                    <li><a href="#pricing" class="text-gray-600 hover:text-purple-600">מחירים</a></li>
                    <li><a href="login.php" class="text-gray-600 hover:text-purple-600">התחברות</a></li>
                    <li><a href="register.php" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700">הרשמה</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-10 md:mb-0">
                    <h1 class="text-5xl font-bold mb-6">דיוור ודפי נחיתה מעוצבים בעברית</h1>
                    <p class="text-xl mb-8">הפלטפורמה הישראלית המובילה ליצירת דפי נחיתה ומערכות דיוור מתקדמות, עם ממשק בעברית ועיצוב מודרני.</p>
                    <div class="flex space-x-4">
                        <a href="register.php" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-gray-100">התחל בחינם</a>
                        <a href="#demo" class="border border-white text-white px-8 py-3 rounded-lg font-bold text-lg hover:bg-white hover:text-purple-600">צפה בהדגמה</a>
                    </div>
                </div>
                <div class="md:w-1/2">
                    <img src="https://via.placeholder.com/600x400" alt="MailHebrew Dashboard" class="rounded-lg shadow-xl">
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-16">למה לבחור ב-MailHebrew?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="ri-layout-4-line text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">דפי נחיתה מעוצבים</h3>
                    <p class="text-gray-600">בחר מתוך מגוון תבניות מעוצבות והתאם אותן לצרכיך בעזרת עורך קל לשימוש.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="ri-mail-send-line text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">מערכת דיוור חכמה</h3>
                    <p class="text-gray-600">שלח הודעות אימייל מעוצבות ללקוחות שלך, עם מעקב אחר פתיחות ולחיצות.</p>
                </div>
                
                <div class="bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="ri-bar-chart-2-line text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">סטטיסטיקות מפורטות</h3>
                    <p class="text-gray-600">קבל תובנות מעמיקות אודות הביצועים של הקמפיינים שלך בעזרת דוחות מפורטים.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-4">מחירים</h2>
            <p class="text-xl text-center text-gray-600 mb-16">בחר את התוכנית המתאימה לצרכים שלך</p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Basic Plan -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-transform transform hover:scale-105">
                    <div class="p-8">
                        <h3 class="text-2xl font-bold mb-4">בסיסי</h3>
                        <div class="text-4xl font-bold mb-6">₪99 <span class="text-lg font-normal text-gray-500">/חודש</span></div>
                        
                        <ul class="mb-8">
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                דף נחיתה אחד
                            </li>
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 300 לידים בחודש
                            </li>
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 300 הודעות דיוור
                            </li>
                        </ul>
                        
                        <a href="register.php" class="block text-center bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700">התחל עכשיו</a>
                    </div>
                </div>
                
                <!-- Pro Plan -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden border-2 border-purple-500 transform scale-105">
                    <div class="bg-purple-600 text-white py-2 text-center font-bold">המומלץ ביותר</div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold mb-4">פרו</h3>
                        <div class="text-4xl font-bold mb-6">₪199 <span class="text-lg font-normal text-gray-500">/חודש</span></div>
                        
                        <ul class="mb-8">
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 5 דפי נחיתה
                            </li>
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 1,000 לידים בחודש
                            </li>
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 1,000 הודעות דיוור
                            </li>
                        </ul>
                        
                        <a href="register.php" class="block text-center bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700">התחל עכשיו</a>
                    </div>
                </div>
                
                <!-- Ultra Plan -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-transform transform hover:scale-105">
                    <div class="p-8">
                        <h3 class="text-2xl font-bold mb-4">אולטרה</h3>
                        <div class="text-4xl font-bold mb-6">₪399 <span class="text-lg font-normal text-gray-500">/חודש</span></div>
                        
                        <ul class="mb-8">
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 15 דפי נחיתה
                            </li>
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 5,000 לידים בחודש
                            </li>
                            <li class="flex items-center mb-3">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 5,000 הודעות דיוור
                            </li>
                        </ul>
                        
                        <a href="register.php" class="block text-center bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700">התחל עכשיו</a>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 text-center">
                <a href="register.php" class="inline-block bg-purple-600 text-white px-8 py-3 rounded-lg font-bold text-lg hover:bg-purple-700">התחל בחינם למשך 7 ימים</a>
                <p class="text-gray-600 mt-4">ללא התחייבות, ללא צורך בכרטיס אשראי</p>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-purple-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">מוכנים להתחיל?</h2>
            <p class="text-xl mb-10 max-w-2xl mx-auto">הצטרפו לאלפי לקוחות מרוצים המשתמשים ב-MailHebrew כדי להגדיל את העסק שלהם</p>
            <a href="register.php" class="inline-block bg-white text-purple-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-gray-100">צור חשבון חינם</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-2xl font-bold mb-4">MailHebrew</h3>
                    <p class="text-gray-400">הפלטפורמה המובילה בישראל לדיוור ודפי נחיתה</p>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">קישורים מהירים</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white">יתרונות</a></li>
                        <li><a href="#pricing" class="text-gray-400 hover:text-white">מחירים</a></li>
                        <li><a href="login.php" class="text-gray-400 hover:text-white">התחברות</a></li>
                        <li><a href="register.php" class="text-gray-400 hover:text-white">הרשמה</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">עזרה ותמיכה</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white">שאלות נפוצות</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">מדריכים</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">צור קשר</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-4">עקבו אחרינו</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"></path>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"></path>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8 text-center text-gray-400">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. כל הזכויות שמורות.</p>
            </div>
        </div>
    </footer>

    <script>
        $(document).ready(function() {
            // Smooth scrolling
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $($(this).attr('href')).offset().top
                }, 500, 'linear');
            });
        });
    </script>
</body>
</html>