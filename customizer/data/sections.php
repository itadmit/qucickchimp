<?php
// Define section groups based on the provided catalogue
$sectionGroups = [
    'hero' => [
        'name' => 'כותרת ראשית (Hero)',
        'description' => 'פתיחת הדף, רושם ראשוני',
        'sections' => [
            'hero-1' => 'כותרת בסיסית',
            'hero-2' => 'כותרת עם תמונה בצד',
            'hero-3' => 'כותרת עם רקע גרדיאנטי',
            'hero-4' => 'כותרת עם וידאו רקע'
        ]
    ],
    'about' => [
        'name' => 'מידע (About/Info)',
        'description' => 'הצגת מידע כללי על העסק/שירות',
        'sections' => [
            'about-1' => 'מידע עם תמונה בצד',
            'about-2' => 'מידע עם איקונים',
            'about-3' => 'מידע בפורמט קרדים'
        ]
    ],
    'services' => [
        'name' => 'שירותים (Services)',
        'description' => 'הצגת השירותים המוצעים',
        'sections' => [
            'services-1' => 'תצוגת גריד',
            'services-2' => 'תצוגת רשימה עם איקונים',
            'services-3' => 'קרדים עם אפקט hover'
        ]
    ],
    'features' => [
        'name' => 'יתרונות (Features)',
        'description' => 'הדגשת היתרונות הייחודיים',
        'sections' => [
            'features-1' => 'יתרונות בפורמט מספרים',
            'features-2' => 'יתרונות עם איקונים',
            'features-3' => 'יתרונות בתצוגת צעדים'
        ]
    ],
    'testimonials' => [
        'name' => 'המלצות (Testimonials)',
        'description' => 'הצגת המלצות וחוות דעת מלקוחות',
        'sections' => [
            'testimonials-1' => 'סליידר המלצות',
            'testimonials-2' => 'גריד של קרדים',
            'testimonials-3' => 'המלצות עם תמונות גדולות'
        ]
    ],
    'gallery' => [
        'name' => 'גלריה (Gallery)',
        'description' => 'הצגת תמונות של עבודות/מוצרים',
        'sections' => [
            'gallery-1' => 'גלריית גריד רגילה',
            'gallery-2' => 'גלריית מסונריה',
            'gallery-3' => 'גלריה עם אפקט lightbox'
        ]
    ],
    'pricing' => [
        'name' => 'מחירים (Pricing)',
        'description' => 'הצגת מחירון/חבילות שירות',
        'sections' => [
            'pricing-1' => 'קרדים של חבילות',
            'pricing-2' => 'טבלת השוואה',
            'pricing-3' => 'מחירון רשימתי'
        ]
    ],
    'faq' => [
        'name' => 'שאלות נפוצות (FAQ)',
        'description' => 'מענה לשאלות שכיחות',
        'sections' => [
            'faq-1' => 'אקורדיון קלאסי',
            'faq-2' => 'שאלות בחלוקה לקטגוריות',
            'faq-3' => 'שאלות עם איקונים'
        ]
    ],
    'contact' => [
        'name' => 'צור קשר (Contact)',
        'description' => 'יצירת קשר, השארת פרטים',
        'sections' => [
            'contact-1' => 'טופס פשוט',
            'contact-2' => 'טופס לצד מפה',
            'contact-3' => 'פרטי קשר עם אייקונים'
        ]
    ],
    'cta' => [
        'name' => 'הנעה לפעולה (CTA)',
        'description' => 'הנעה לפעולה',
        'sections' => [
            'cta-1' => 'CTA פשוט',
            'cta-2' => 'CTA עם תמונת רקע',
            'cta-3' => 'CTA עם טופס קצר'
        ]
    ],
    'footer' => [
        'name' => 'פוטר (Footer)',
        'description' => 'חלק תחתון של העמוד',
        'sections' => [
            'footer-1' => 'פוטר פשוט',
            'footer-2' => 'פוטר עם קישורים',
            'footer-3' => 'פוטר מורחב עם טופס'
        ]
    ]
];

// Get available sections from templates folder
$sectionsDir = __DIR__ . '/../sections';
$availableSections = [];

if (is_dir($sectionsDir)) {
    $files = scandir($sectionsDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_dir($sectionsDir . '/' . $file)) {
            $availableSections[] = $file;
        }
    }
}