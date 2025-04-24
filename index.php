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
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
        }
        .feature-card {
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            border-color: #8B5CF6;
        }
        .testimonial-card {
            transition: all 0.3s ease;
        }
        .testimonial-card:hover {
            transform: scale(1.03);
        }
        .hero-image {
            border-radius: 8px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: all 0.5s ease;
        }
        .hero-image:hover {
            transform: translateY(-10px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
        }
        .nav-link {
            position: relative;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -4px;
            left: 0;
            background-color: #8B5CF6;
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm fixed top-0 right-0 left-0 z-10">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <h1 class="text-3xl font-bold text-purple-600">קוויק סייט</h1>
            </div>
            <nav>
                <ul class="flex space-x-6">
                    <li><a href="#features" class="text-gray-600 hover:text-purple-600 nav-link">יתרונות</a></li>
                    <li><a href="#how-it-works" class="text-gray-600 hover:text-purple-600 nav-link">איך זה עובד</a></li>
                    <li><a href="#testimonials" class="text-gray-600 hover:text-purple-600 nav-link">לקוחות</a></li>
                    <li><a href="#pricing" class="text-gray-600 hover:text-purple-600 nav-link">מחירים</a></li>
                    <li><a href="login.php" class="text-gray-600 hover:text-purple-600 nav-link">התחברות</a></li>
                    <li><a href="register.php" class="bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition-colors">הרשמה</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="gradient-bg text-white pt-32 pb-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center">
                <div class="md:w-1/2 mb-10 md:mb-0">
                    <h1 class="text-5xl font-bold mb-6">דיוור ודפי נחיתה מעוצבים בעברית</h1>
                    <p class="text-xl mb-8">הפלטפורמה הישראלית המובילה ליצירת דפי נחיתה ומערכות דיוור מתקדמות, עם ממשק בעברית ועיצוב מודרני.</p>
                    <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-0 sm:space-x-reverse sm:space-x-4">
                        <a href="register.php" class="bg-white text-purple-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-gray-100 transition-colors text-center">התחל בחינם</a>
                        <a href="#demo" class="border border-white text-white px-8 py-3 rounded-lg font-bold text-lg hover:bg-white hover:text-purple-600 transition-colors text-center">צפה בהדגמה</a>
                    </div>
                    <div class="mt-8 flex items-center">
                        <div class="flex -space-x-2 space-x-reverse">
                            <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-1.2.1&auto=format&fit=crop&w=80&h=80&q=80" class="w-10 h-10 rounded-full border-2 border-white" alt="משתמש">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=80&h=80&q=80" class="w-10 h-10 rounded-full border-2 border-white" alt="משתמש">
                            <img src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=80&h=80&q=80" class="w-10 h-10 rounded-full border-2 border-white" alt="משתמש">
                        </div>
                        <div class="mr-4">
                            <p class="font-semibold">הצטרפו ל-2,500+ לקוחות מרוצים</p>
                            <div class="flex mt-1">
                                <i class="ri-star-fill text-yellow-400"></i>
                                <i class="ri-star-fill text-yellow-400"></i>
                                <i class="ri-star-fill text-yellow-400"></i>
                                <i class="ri-star-fill text-yellow-400"></i>
                                <i class="ri-star-fill text-yellow-400"></i>
                                <span class="mr-1 font-medium">4.9/5</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="md:w-1/2">
                    <img src="https://images.unsplash.com/photo-1563986768609-322da13575f3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80" alt="קוויק סייט Dashboard" class="rounded-lg shadow-xl hero-image">
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="py-10 bg-white">
        <div class="container mx-auto px-4">
            <div class="bg-white rounded-xl shadow-lg -mt-20 p-8 grid grid-cols-1 md:grid-cols-3 gap-6 relative z-20">
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">+10,000</div>
                    <p class="text-gray-600">דפי נחיתה נוצרו</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">+200,000</div>
                    <p class="text-gray-600">לידים שנאספו</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600 mb-2">+500,000</div>
                    <p class="text-gray-600">מיילים שנשלחו</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl font-bold mb-4">למה לבחור ב-קוויק סייט?</h2>
                <p class="text-xl text-gray-600">הפתרון המושלם להגדלת ההמרות ושיפור התקשורת עם הלקוחות שלך</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="feature-card bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="ri-layout-4-line text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">דפי נחיתה מעוצבים</h3>
                    <p class="text-gray-600 mb-6">תבניות מקצועיות שתוכננו כדי להגדיל המרות. התאמה אישית קלה ומהירה ללא צורך בידע טכני.</p>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>מגוון תבניות מותאמות לעסקים</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>עורך גרירה ושחרור קל לשימוש</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>תמיכה במובייל ובכל הדפדפנים</span>
                        </li>
                    </ul>
                </div>
                
                <div class="feature-card bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="ri-mail-send-line text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">מערכת דיוור חכמה</h3>
                    <p class="text-gray-600 mb-6">שלח הודעות אימייל מעוצבות ללקוחות שלך, עם מעקב מתקדם ויכולות פילוח מדויקות.</p>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>תבניות אימייל מקצועיות</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>אוטומציות ותזמון הודעות</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>אחוזי המסירה הגבוהים בתעשייה</span>
                        </li>
                    </ul>
                </div>
                
                <div class="feature-card bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mb-6">
                        <i class="ri-bar-chart-2-line text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">סטטיסטיקות מפורטות</h3>
                    <p class="text-gray-600 mb-6">קבל תובנות מעמיקות אודות הביצועים של הקמפיינים שלך בעזרת דוחות מפורטים בזמן אמת.</p>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>דוחות אנליטיקה מתקדמים</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>מעקב אחר המרות בזמן אמת</span>
                        </li>
                        <li class="flex items-center">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>A/B טסטינג מובנה</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-16 text-center">
                <a href="register.php" class="inline-block bg-purple-600 text-white px-8 py-3 rounded-lg font-bold text-lg hover:bg-purple-700 transition duration-300">נסה בחינם עכשיו</a>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl font-bold mb-4">איך זה עובד?</h2>
                <p class="text-xl text-gray-600">תהליך פשוט ב-3 צעדים להגדרת מערכת דיוור ודפי נחיתה</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="relative">
                    <div class="bg-white p-8 rounded-xl shadow-md relative z-10">
                        <div class="w-16 h-16 bg-purple-600 text-white rounded-full flex items-center justify-center mb-6 text-2xl font-bold">1</div>
                        <h3 class="text-2xl font-bold mb-4">יצירת דף נחיתה</h3>
                        <p class="text-gray-600">בחר תבנית מקצועית, התאם אותה לצרכים שלך בעזרת העורך הקל לשימוש והפק דף מושלם להמרות.</p>
                    </div>
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-2 bg-purple-200 z-0"></div>
                </div>
                
                <div class="relative">
                    <div class="bg-white p-8 rounded-xl shadow-md relative z-10">
                        <div class="w-16 h-16 bg-purple-600 text-white rounded-full flex items-center justify-center mb-6 text-2xl font-bold">2</div>
                        <h3 class="text-2xl font-bold mb-4">איסוף לידים</h3>
                        <p class="text-gray-600">המערכת אוספת את פרטי הלקוחות באופן אוטומטי ומאחסנת אותם במערכת CRM המובנית.</p>
                    </div>
                    <div class="hidden md:block absolute top-1/2 left-0 w-full h-2 bg-purple-200 z-0"></div>
                </div>
                
                <div class="relative">
                    <div class="bg-white p-8 rounded-xl shadow-md relative z-10">
                        <div class="w-16 h-16 bg-purple-600 text-white rounded-full flex items-center justify-center mb-6 text-2xl font-bold">3</div>
                        <h3 class="text-2xl font-bold mb-4">שליחת קמפיינים</h3>
                        <p class="text-gray-600">צור קמפיינים אוטומטיים, תזמן הודעות, ועקוב אחר הביצועים בזמן אמת דרך לוח הבקרה.</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 text-center">
                <a href="#demo" class="inline-block bg-white border border-purple-600 text-purple-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-purple-50 transition duration-300">צפה בהדגמה מלאה</a>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl font-bold mb-4">מה לקוחותינו אומרים?</h2>
                <p class="text-xl text-gray-600">אלפי עסקים כבר משתמשים במערכת שלנו להגדלת המכירות</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="testimonial-card bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="flex items-center mb-6">
                        <div class="flex">
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                        </div>
                    </div>
                    <blockquote class="text-gray-600 mb-6">"מאז שהתחלתי להשתמש ב-קוויק סייט, אחוזי ההמרה שלי עלו ביותר מ-40%. הממשק נוח ופשוט לשימוש, ומייצר תוצאות מדהימות!"</blockquote>
                    <div class="flex items-center">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&auto=format&fit=crop&w=120&h=120&q=80" alt="יוסי כהן" class="w-12 h-12 rounded-full ml-4">
                        <div>
                            <h4 class="font-bold">יוסי כהן</h4>
                            <p class="text-gray-500 text-sm">מנכ"ל, חברת ביטוח ישיר</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="flex items-center mb-6">
                        <div class="flex">
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                        </div>
                    </div>
                    <blockquote class="text-gray-600 mb-6">"המערכת פשוט עובדת! תוך חודש הצלחנו להגדיל את רשימת התפוצה שלנו ב-200% ולהגדיל את המכירות. התמיכה המקצועית מצוינת."</blockquote>
                    <div class="flex items-center">
                        <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=crop&w=120&h=120&q=80" alt="גילה לוי" class="w-12 h-12 rounded-full ml-4">
                        <div>
                            <h4 class="font-bold">גילה לוי</h4>
                            <p class="text-gray-500 text-sm">בעלים, חנות אופנה אונליין</p>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-card bg-gray-50 p-8 rounded-xl shadow-md">
                    <div class="flex items-center mb-6">
                        <div class="flex">
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                            <i class="ri-star-fill text-yellow-400"></i>
                        </div>
                    </div>
                    <blockquote class="text-gray-600 mb-6">"יצרנו דף נחיתה תוך פחות משעה, וכבר בשבוע הראשון אספנו מאות לידים איכותיים. הממשק בעברית חסך לנו המון זמן וכאב ראש."</blockquote>
                    <div class="flex items-center">
                        <img src="https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?ixlib=rb-1.2.1&auto=format&fit=crop&w=120&h=120&q=80" alt="דוד אברהם" class="w-12 h-12 rounded-full ml-4">
                        <div>
                            <h4 class="font-bold">דוד אברהם</h4>
                            <p class="text-gray-500 text-sm">סמנכ"ל שיווק, חברת הייטק</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-16 text-center">
                <a href="#" class="text-purple-600 hover:text-purple-800 font-bold">קרא עוד סיפורי הצלחה <i class="ri-arrow-left-line"></i></a>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl font-bold mb-4">מחירים פשוטים וברורים</h2>
                <p class="text-xl text-gray-600">בחר את התוכנית המתאימה לצרכים ולתקציב שלך</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Basic Plan -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-transform transform hover:scale-105">
                    <div class="p-8">
                        <h3 class="text-2xl font-bold mb-4">בסיסי</h3>
                        <div class="text-4xl font-bold mb-6">₪99 <span class="text-lg font-normal text-gray-500">/חודש</span></div>
                        <p class="text-gray-600 mb-6">מושלם לעסקים קטנים שמתחילים את דרכם</p>
                        
                        <ul class="mb-8 space-y-4">
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                דף נחיתה אחד
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 300 לידים בחודש
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 300 הודעות דיוור
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                תמיכה במייל
                            </li>
                            <li class="flex items-center text-gray-400">
                                <i class="ri-close-line text-red-500 ml-2"></i>
                                אוטומציות מתקדמות
                            </li>
                            <li class="flex items-center text-gray-400">
                                <i class="ri-close-line text-red-500 ml-2"></i>
                                דוחות מתקדמים
                            </li>
                        </ul>
                        
                        <a href="register.php" class="block text-center bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 transition duration-300">התחל עכשיו</a>
                    </div>
                </div>
                
                <!-- Pro Plan -->
                <div class="bg-white rounded-xl shadow-xl overflow-hidden border-2 border-purple-500 transform scale-105 relative">
                    <div class="absolute top-0 right-0 bg-purple-600 text-white px-4 py-1 rounded-bl-lg font-bold">המומלץ ביותר</div>
                    <div class="p-8">
                        <h3 class="text-2xl font-bold mb-4">פרו</h3>
                        <div class="text-4xl font-bold mb-6">₪199 <span class="text-lg font-normal text-gray-500">/חודש</span></div>
                        <p class="text-gray-600 mb-6">לעסקים שרוצים לצמוח ולהתרחב</p>
                        
                        <ul class="mb-8 space-y-4">
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 5 דפי נחיתה
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 1,000 לידים בחודש
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 1,000 הודעות דיוור
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                תמיכה במייל וטלפון
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                אוטומציות מתקדמות
                            </li>
                            <li class="flex items-center text-gray-400">
                                <i class="ri-close-line text-red-500 ml-2"></i>
                                דוחות מתקדמים
                            </li>
                        </ul>
                        
                        <a href="register.php" class="block text-center bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 transition duration-300">התחל עכשיו</a>
                    </div>
                </div>
                
                <!-- Ultra Plan -->
                <div class="bg-white rounded-xl shadow-md overflow-hidden transition-transform transform hover:scale-105">
                    <div class="p-8">
                        <h3 class="text-2xl font-bold mb-4">אולטרה</h3>
                        <div class="text-4xl font-bold mb-6">₪399 <span class="text-lg font-normal text-gray-500">/חודש</span></div>
                        <p class="text-gray-600 mb-6">לעסקים גדולים עם צרכים מורכבים</p>
                        
                        <ul class="mb-8 space-y-4">
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 15 דפי נחיתה
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 5,000 לידים בחודש
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                עד 5,000 הודעות דיוור
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                תמיכה VIP 24/7
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                אוטומציות מתקדמות
                            </li>
                            <li class="flex items-center">
                                <i class="ri-check-line text-green-500 ml-2"></i>
                                דוחות מתקדמים ואנליטיקס
                            </li>
                        </ul>
                        
                        <a href="register.php" class="block text-center bg-purple-600 text-white py-3 rounded-lg font-bold hover:bg-purple-700 transition duration-300">התחל עכשיו</a>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 text-center">
                <a href="register.php" class="inline-block bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-purple-700 hover:to-indigo-700 transition duration-300 shadow-lg">התחל בחינם למשך 7 ימים</a>
                <p class="text-gray-600 mt-4">ללא התחייבות, ללא צורך בכרטיס אשראי</p>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20 bg-white relative overflow-hidden">
        <div class="absolute top-0 left-0 right-0 h-40 bg-gradient-to-b from-gray-50 to-white"></div>
        <div class="container mx-auto px-4 relative z-10">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl font-bold mb-4">צפה בהדגמה</h2>
                <p class="text-xl text-gray-600">גלה איך קוויק סייט יכול לעזור לעסק שלך לגדול</p>
            </div>
            
            <div class="max-w-4xl mx-auto bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                <div class="aspect-w-16 aspect-h-9 relative">
                    <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80" alt="הדגמת המערכת" class="w-full h-full object-cover">
                    <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-40">
                        <button class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg hover:bg-gray-100 transition duration-300">
                            <i class="ri-play-fill text-4xl text-purple-600"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6 bg-white border-t border-gray-200">
                    <h3 class="text-xl font-bold mb-2">המדריך המלא ל-קוויק סייט</h3>
                    <p class="text-gray-600">בסרטון זה נדגים כיצד להקים דף נחיתה, לאסוף לידים ולשלוח קמפיין אימייל - כל זה תוך פחות מ-15 דקות.</p>
                </div>
            </div>
            
            <div class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full mx-auto flex items-center justify-center mb-4">
                        <i class="ri-movie-line text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">סרטוני הדרכה</h3>
                    <p class="text-gray-600">צפה בסרטוני הדרכה מפורטים על כל תכונות המערכת</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full mx-auto flex items-center justify-center mb-4">
                        <i class="ri-presentation-line text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">הדגמה אישית</h3>
                    <p class="text-gray-600">תאם הדגמה אישית עם אחד המומחים שלנו</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full mx-auto flex items-center justify-center mb-4">
                        <i class="ri-file-text-line text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="font-bold mb-2">מדריכים ומסמכים</h3>
                    <p class="text-gray-600">הורד מדריכים מפורטים לתפעול המערכת</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-gradient-to-r from-purple-600 to-indigo-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">מוכנים להגדיל את העסק שלכם?</h2>
            <p class="text-xl mb-10 max-w-2xl mx-auto">הצטרפו לאלפי לקוחות מרוצים המשתמשים ב-קוויק סייט כדי להגדיל את המכירות, לשפר את התקשורת עם הלקוחות ולבנות מותג מוביל.</p>
            
            <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-0 sm:space-x-reverse sm:space-x-6">
                <a href="register.php" class="inline-block bg-white text-purple-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-gray-100 transition duration-300 shadow-lg">צור חשבון חינם</a>
                <a href="#" class="inline-block text-white border border-white px-8 py-3 rounded-lg font-bold text-lg hover:bg-white hover:bg-opacity-10 transition duration-300">שאל אותנו שאלה</a>
            </div>
            
            <div class="mt-16 flex flex-wrap justify-center items-center gap-10">
                <div class="text-center">
                    <div class="text-2xl font-bold mb-1">7</div>
                    <p class="text-white text-opacity-80">ימי ניסיון</p>
                </div>
                <div class="h-10 border-l border-white border-opacity-30"></div>
                <div class="text-center">
                    <div class="text-2xl font-bold mb-1">24/7</div>
                    <p class="text-white text-opacity-80">תמיכה טכנית</p>
                </div>
                <div class="h-10 border-l border-white border-opacity-30"></div>
                <div class="text-center">
                    <div class="text-2xl font-bold mb-1">30</div>
                    <p class="text-white text-opacity-80">יום החזר כספי</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-4xl font-bold mb-4">שאלות נפוצות</h2>
                <p class="text-xl text-gray-600">תשובות לשאלות שאולי יש לך</p>
            </div>
            
            <div class="max-w-3xl mx-auto divide-y">
                <div class="py-6">
                    <h3 class="text-xl font-bold mb-2">האם אני יכול להפסיק את המנוי בכל עת?</h3>
                    <p class="text-gray-600">כן, אתה יכול לבטל את המנוי בכל עת ללא התחייבות. לא נגבה ממך תשלומים נוספים מעבר לתקופה ששילמת עליה.</p>
                </div>
                
                <div class="py-6">
                    <h3 class="text-xl font-bold mb-2">האם המערכת תומכת בעברית?</h3>
                    <p class="text-gray-600">בהחלט! המערכת שלנו נבנתה מהיסוד בחשיבה על השוק הישראלי. הממשק כולו בעברית, תומך בכתיבה מימין לשמאל ומותאם לצרכים של העסק הישראלי.</p>
                </div>
                
                <div class="py-6">
                    <h3 class="text-xl font-bold mb-2">איך עובד תהליך איסוף הלידים?</h3>
                    <p class="text-gray-600">דפי הנחיתה שלנו כוללים טפסים חכמים שאוספים את פרטי הלקוחות ומעבירים אותם ישירות למערכת ה-CRM המובנית. משם תוכל לשלוח מיילים, לנהל את הלידים ולעקוב אחר ההתקדמות שלהם.</p>
                </div>
                
                <div class="py-6">
                    <h3 class="text-xl font-bold mb-2">האם יש תקופת ניסיון?</h3>
                    <p class="text-gray-600">כן, אנחנו מציעים תקופת ניסיון חינם של 7 ימים לכל התוכניות. בתקופה זו תוכל להתנסות בכל תכונות המערכת ללא הגבלה וללא צורך בכרטיס אשראי.</p>
                </div>
            </div>
            
            <div class="mt-10 text-center">
                <a href="#" class="text-purple-600 hover:text-purple-800 font-bold">צפה בכל השאלות הנפוצות <i class="ri-arrow-left-line"></i></a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12">
                <div>
                    <h3 class="text-2xl font-bold mb-6">קוויק סייט</h3>
                    <p class="text-gray-400 mb-6">הפלטפורמה המובילה בישראל לדיוור ודפי נחיתה, עם ממשק מלא בעברית ותמיכה ישראלית.</p>
                    <div class="flex space-x-4 space-x-reverse">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="ri-facebook-fill text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="ri-instagram-fill text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="ri-twitter-fill text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="ri-linkedin-fill text-xl"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">מוצרים</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">דפי נחיתה</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">דיוור אלקטרוני</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">אוטומציות</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">ניהול לידים</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">אנליטיקס</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">עזרה ותמיכה</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">מרכז תמיכה</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">מדריכים</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">סרטוני הדרכה</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">שאלות נפוצות</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">צור קשר</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-lg font-bold mb-6">החברה</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">אודותינו</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">קריירה</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">בלוג</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">תנאי שימוש</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">מדיניות פרטיות</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400">&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. כל הזכויות שמורות.</p>
                <div class="mt-4 md:mt-0">
                    <img src="https://via.placeholder.com/250x30?text=Secure+Payment+Methods" alt="אמצעי תשלום" class="h-8">
                </div>
            </div>
        </div>
    </footer>

    <script>
        $(document).ready(function() {
            // Smooth scrolling
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $($(this).attr('href')).offset().top - 80
                }, 500, 'linear');
            });
            
            // Demo Video Play
            $('button.w-20').on('click', function() {
                // המקום לטיפול בהפעלת וידאו
                alert('הוידאו יופעל כאן');
            });
        });
    </script>
</body>
</html>