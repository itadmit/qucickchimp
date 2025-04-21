-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: אפריל 21, 2025 בזמן 05:05 PM
-- גרסת שרת: 5.7.39
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mailhebrew`
--

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `automations`
--

CREATE TABLE `automations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive','draft') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `trigger_type` enum('subscription','date','form_submission','inactivity') COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_config` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- הוצאת מידע עבור טבלה `automations`
--

INSERT INTO `automations` (`id`, `user_id`, `name`, `description`, `status`, `trigger_type`, `trigger_config`, `created_at`, `updated_at`) VALUES
(1, 1, 'הודעת חימום', '', 'inactive', 'subscription', '{\"list_id\":1}', '2025-04-21 16:58:43', '2025-04-21 16:59:10');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `automation_logs`
--

CREATE TABLE `automation_logs` (
  `id` int(11) NOT NULL,
  `automation_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `step_id` int(11) DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failed','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `automation_steps`
--

CREATE TABLE `automation_steps` (
  `id` int(11) NOT NULL,
  `automation_id` int(11) NOT NULL,
  `step_order` int(11) NOT NULL DEFAULT '0',
  `action_type` enum('send_email','add_tag','remove_tag','move_to_list','update_field','wait') COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_config` text COLLATE utf8mb4_unicode_ci,
  `wait_days` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- הוצאת מידע עבור טבלה `automation_steps`
--

INSERT INTO `automation_steps` (`id`, `automation_id`, `step_order`, `action_type`, `action_config`, `wait_days`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'wait', NULL, 1, '2025-04-21 16:58:43', '2025-04-21 16:58:43');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `automation_subscribers`
--

CREATE TABLE `automation_subscribers` (
  `id` int(11) NOT NULL,
  `automation_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `current_step` int(11) DEFAULT NULL,
  `status` enum('active','completed','stopped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_action_at` timestamp NULL DEFAULT NULL,
  `next_action_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `campaigns`
--

CREATE TABLE `campaigns` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `status` enum('draft','scheduled','sent') DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `campaign_stats`
--

CREATE TABLE `campaign_stats` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `is_sent` tinyint(1) DEFAULT '0',
  `is_opened` tinyint(1) DEFAULT '0',
  `is_clicked` tinyint(1) DEFAULT '0',
  `sent_at` datetime DEFAULT NULL,
  `opened_at` datetime DEFAULT NULL,
  `clicked_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `contact_lists`
--

CREATE TABLE `contact_lists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- הוצאת מידע עבור טבלה `contact_lists`
--

INSERT INTO `contact_lists` (`id`, `user_id`, `name`, `description`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 'לקוחות 2025', 'רשימה חדשה', 1, '2025-04-21 16:59:07', '2025-04-21 16:59:07'),
(2, 1, '2', '2', 0, '2025-04-21 17:04:48', '2025-04-21 17:04:48');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `form_fields`
--

CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `landing_page_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'שם השדה בטופס (באנגלית)',
  `label` varchar(255) NOT NULL COMMENT 'תווית השדה',
  `type` varchar(50) NOT NULL DEFAULT 'text' COMMENT 'סוג השדה (text, email, tel, textarea, select, checkbox)',
  `is_required` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'האם שדה חובה',
  `options` text COMMENT 'אפשרויות עבור שדות מסוג select',
  `order` int(11) NOT NULL DEFAULT '0' COMMENT 'סדר הצגה',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- הוצאת מידע עבור טבלה `form_fields`
--

INSERT INTO `form_fields` (`id`, `user_id`, `landing_page_id`, `name`, `label`, `type`, `is_required`, `options`, `order`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'mslvl', 'מסלול', 'select', 0, 'פרו\r\nמתקדם', 1, '2025-04-21 11:23:14', '2025-04-21 11:23:14');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `landing_pages`
--

CREATE TABLE `landing_pages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `slug` varchar(255) NOT NULL,
  `template_id` int(11) NOT NULL,
  `content` longtext,
  `form_destination` int(11) DEFAULT NULL,
  `webhook_url` varchar(255) DEFAULT NULL,
  `notification_emails` text,
  `thank_you_url` varchar(255) DEFAULT NULL,
  `thank_you_message` text,
  `send_confirmation_email` tinyint(1) NOT NULL DEFAULT '0',
  `config` json DEFAULT NULL,
  `sections` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- הוצאת מידע עבור טבלה `landing_pages`
--

INSERT INTO `landing_pages` (`id`, `user_id`, `title`, `description`, `slug`, `template_id`, `content`, `form_destination`, `webhook_url`, `notification_emails`, `thank_you_url`, `thank_you_message`, `send_confirmation_email`, `config`, `sections`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 1, 'yogev', 'הצטרפו עכשיו לקורסים הדיגיטליים שישנו לכם את הקריירה – למדו מתי שתרצו, בקצב שלכם, עם מרצים מנוסים ותוכן פרקטי שמוביל לתוצאות.', 'yogev', 3, '<html lang=\"he\" dir=\"rtl\"><head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>דף נחיתה למכירות</title>\r\n    <link href=\"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css\" rel=\"stylesheet\">\r\n    <link href=\"https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css\" rel=\"stylesheet\">\r\n    <style>\r\n        @import url(\'https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap\');\r\n        body {\r\n            font-family: \'Heebo\', sans-serif;\r\n        }\r\n    </style>\r\n<style id=\"color-scheme\">\r\n                :root {\r\n                    --primary-color: #6366f1;\r\n                    --secondary-color: #4f46e5;\r\n                    --accent-color: #ec4899;\r\n                    --bg-color: #ffffff;\r\n                    --text-color: #111827;\r\n                }\r\n                \r\n                body {\r\n                    background-color: var(--bg-color);\r\n                    color: var(--text-color);\r\n                }\r\n                \r\n                .bg-primary, .bg-indigo-600, .bg-purple-600, .bg-blue-600 {\r\n                    background-color: var(--primary-color) !important;\r\n                }\r\n                \r\n                .text-primary, .text-indigo-600, .text-purple-600, .text-blue-600 {\r\n                    color: var(--primary-color) !important;\r\n                }\r\n                \r\n                .border-primary, .border-indigo-600, .border-purple-600, .border-blue-600 {\r\n                    border-color: var(--primary-color) !important;\r\n                }\r\n                \r\n                .bg-secondary, .bg-indigo-700, .bg-purple-700, .bg-blue-700 {\r\n                    background-color: var(--secondary-color) !important;\r\n                }\r\n                \r\n                .text-secondary, .text-indigo-700, .text-purple-700, .text-blue-700 {\r\n                    color: var(--secondary-color) !important;\r\n                }\r\n                \r\n                .border-secondary, .border-indigo-700, .border-purple-700, .border-blue-700 {\r\n                    border-color: var(--secondary-color) !important;\r\n                }\r\n                \r\n                a, button, .btn, .button {\r\n                    transition: all 0.3s ease;\r\n                }\r\n                \r\n                a.btn, button.btn, .btn, .button {\r\n                    display: inline-block;\r\n                    padding: 0.5rem 1rem;\r\n                    border-radius: 0.375rem;\r\n                    font-weight: 500;\r\n                    text-align: center;\r\n                }\r\n                \r\n                a.btn-primary, button.btn-primary, .btn-primary {\r\n                    background-color: var(--primary-color);\r\n                    color: white;\r\n                }\r\n                \r\n                a.btn-primary:hover, button.btn-primary:hover, .btn-primary:hover {\r\n                    background-color: var(--secondary-color);\r\n                }\r\n                \r\n                a.btn-outline, button.btn-outline, .btn-outline {\r\n                    background-color: transparent;\r\n                    border: 1px solid var(--primary-color);\r\n                    color: var(--primary-color);\r\n                }\r\n                \r\n                a.btn-outline:hover, button.btn-outline:hover, .btn-outline:hover {\r\n                    background-color: var(--primary-color);\r\n                    color: white;\r\n                }\r\n            </style><style id=\"font-style\">\r\n                body, html {\r\n                    font-family: Heebo, sans-serif;\r\n                }\r\n            </style></head>\r\n<body>\r\n    <!-- Hero Section -->\r\n    <section id=\"hero\" class=\"bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-20\" data-section-type=\"hero-3\" data-title=\"המוצר שישנה את חייכם!\" data-index=\"0\" style=\"outline: none;\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <span class=\"inline-block bg-white text-indigo-600 rounded-full px-4 py-1 text-sm font-bold mb-4\">מבצע מיוחד לזמן מוגבל</span>\r\n            <h1 class=\"text-4xl md:text-6xl font-bold mb-6\">המוצר שישנה את חייכם!</h1>\r\n            <p class=\"text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90\">פתרון מהפכני שפותר את הבעיה המרכזית של הלקוחות שלכם ומספק תוצאות מיידיות</p>\r\n            \r\n            <div class=\"flex flex-wrap justify-center gap-4 mb-8\">\r\n                <a href=\"#pricing\" class=\"bg-white text-indigo-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition duration-300 shadow-md\">רכשו עכשיו</a>\r\n                <a href=\"#features\" class=\"bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-indigo-600 transition duration-300\">גלו עוד</a>\r\n            </div>\r\n            \r\n            <div class=\"mt-8 text-sm opacity-80\">\r\n                <p>הצטרפו ל-5,000+ לקוחות מרוצים</p>\r\n            </div>\r\n            \r\n            <div class=\"mt-8 flex justify-center items-center space-x-8 rtl:space-x-reverse\">\r\n                <div class=\"h-12 bg-white opacity-30\" style=\"border:0px\"></div>\r\n                <div class=\"text-center\">\r\n                    <span class=\"block text-4xl font-bold\">97%</span>\r\n                    <span class=\"text-sm opacity-80\">שביעות רצון</span>\r\n                </div>\r\n\r\n                <div class=\"h-12 w-px bg-white opacity-30\"></div>\r\n                <div class=\"text-center\">\r\n                    <span class=\"block text-4xl font-bold\">24/7</span>\r\n                    <span class=\"text-sm opacity-80\">תמיכה זמינה</span>\r\n                </div>\r\n                <div class=\"h-12 w-px bg-white opacity-30\"></div>\r\n                <div class=\"text-center\">\r\n                    <span class=\"block text-4xl font-bold\">30+</span>\r\n                    <span class=\"text-sm opacity-80\">ימי ניסיון</span>\r\n                </div>\r\n                \r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n    <!-- Features Section -->\r\n    <section id=\"features\" class=\"py-16 bg-white\" data-section-type=\"features-2\" data-title=\"יתרונות המוצר שלנו\" data-index=\"1\" style=\"outline: none;\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <h2 class=\"text-3xl font-bold mb-2 text-gray-800\">יתרונות המוצר שלנו</h2>\r\n            <p class=\"text-xl mb-12 text-gray-600 max-w-3xl mx-auto\">הסיבות שהמוצר שלנו הוא הפתרון הטוב ביותר עבורכם</p>\r\n            \r\n            <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                <div class=\"p-6 transition duration-300 hover:shadow-md\">\r\n                    <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                        <i class=\"ri-timer-line text-2xl text-indigo-600\"></i>\r\n                    </div>\r\n                    <h3 class=\"text-xl font-bold mb-2 text-gray-800\">חוסך זמן</h3>\r\n                    <p class=\"text-gray-600\">מאפשר לכם לחסוך שעות עבודה יקרות בכל שבוע</p>\r\n                </div>\r\n                \r\n                <div class=\"p-6 transition duration-300 hover:shadow-md\">\r\n                    <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                        <i class=\"ri-secure-payment-line text-2xl text-indigo-600\"></i>\r\n                    </div>\r\n                    <h3 class=\"text-xl font-bold mb-2 text-gray-800\">חוסך כסף</h3>\r\n                    <p class=\"text-gray-600\">ההשקעה מחזירה את עצמה תוך פחות מחודש</p>\r\n                </div>\r\n                \r\n                <div class=\"p-6 transition duration-300 hover:shadow-md\">\r\n                    <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                        <i class=\"ri-rocket-line text-2xl text-indigo-600\"></i>\r\n                    </div>\r\n                    <h3 class=\"text-xl font-bold mb-2 text-gray-800\">מגביר יעילות</h3>\r\n                    <p class=\"text-gray-600\">מאפשר לכם להגדיל את התפוקה ב-200% לפחות</p>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n    <!-- Product Showcase -->\r\n    <section id=\"about\" class=\"py-16 bg-gray-50\" data-section-type=\"about-1\" data-title=\"איך המוצר שלנו עובד\" data-index=\"2\" style=\"outline: none;\">\r\n        <div class=\"container mx-auto px-4\">\r\n            <div class=\"flex flex-wrap items-center\">\r\n                <div class=\"w-full md:w-1/2 mb-10 md:mb-0 md:order-last\">\r\n                    <h2 class=\"text-3xl font-bold mb-4 text-gray-800\">איך המוצר שלנו עובד</h2>\r\n                    <p class=\"text-lg mb-6 text-gray-600\">המוצר שלנו משתמש בטכנולוגיה מתקדמת כדי לספק תוצאות מהירות ויעילות. בעזרת ממשק משתמש פשוט וידידותי, כל אחד יכול להשתמש במוצר בקלות ובנוחות.</p>\r\n                    <p class=\"text-lg mb-6 text-gray-600\">הפיתוח שלנו מבוסס על מחקר רב-שנים ושיתוף פעולה עם מומחים מובילים בתחום. זה מה שמאפשר לנו להציע פתרון שעובד בצורה יוצאת דופן.</p>\r\n                    <div class=\"flex flex-wrap gap-4\">\r\n                        <a href=\"#pricing\" class=\"bg-indigo-600 text-white px-6 py-3 rounded-lg font-bold text-lg hover:bg-indigo-700 transition duration-300\">רכשו עכשיו</a>\r\n                        <a href=\"#testimonials\" class=\"bg-transparent border-2 border-indigo-600 text-indigo-600 px-6 py-3 rounded-lg font-bold text-lg hover:bg-indigo-50 transition duration-300\">ראו המלצות</a>\r\n                    </div>\r\n                </div>\r\n                <div class=\"w-full md:w-1/2 md:pl-10\" style=\"position: relative;\">\r\n                    <img src=\"https://images.unsplash.com/photo-1555774698-0b77e0d5fac6?ixlib=rb-4.0.3&amp;ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&amp;auto=format&amp;fit=crop&amp;w=1000&amp;q=80\" alt=\"תמונת המוצר\" class=\"rounded-lg shadow-lg w-full\" style=\"cursor: pointer;\">\r\n                <div class=\"image-edit-overlay\" style=\"position: absolute; inset: 0px; background-color: rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; margin-left: 40px; pointer-events: none;\"><div><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"white\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 13a3 3 0 11-6 0 3 3 0 016 0z\"></path></svg></div></div></div>\r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n    <!-- Testimonials Section -->\r\n    <section id=\"testimonials\" class=\"py-16 bg-white\" data-section-type=\"testimonials-1\" data-title=\"מה הלקוחות אומרים עלינו?\" data-index=\"3\" style=\"outline: none;\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <h2 class=\"text-3xl font-bold mb-2 text-gray-800\">מה הלקוחות אומרים עלינו?</h2>\r\n            <p class=\"text-xl mb-12 text-gray-600 max-w-3xl mx-auto\">המלצות מאנשים אמיתיים שכבר משתמשים במוצר</p>\r\n            \r\n            <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                <div class=\"p-6 bg-white rounded-lg shadow-md relative\">\r\n                    <div class=\"text-indigo-500 text-6xl absolute top-2 right-4 opacity-20\">\"</div>\r\n                    <div class=\"flex justify-center mb-4\">\r\n                        <div class=\"flex text-yellow-400\">\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                        </div>\r\n                    </div>\r\n                    <p class=\"text-gray-600 mb-6 relative z-10\">המוצר פשוט מדהים! חסך לי שעות עבודה ושיפר את התוצאות של העסק שלי. אני לא יכול לדמיין איך הייתי מסתדר בלעדיו.</p>\r\n                    <div class=\"flex items-center\" style=\"position: relative;\">\r\n                        <img src=\"https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&amp;ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&amp;auto=format&amp;fit=crop&amp;w=100&amp;q=80\" alt=\"לקוח 1\" class=\"w-12 h-12 rounded-full object-cover ml-3\" style=\"cursor: pointer;\">\r\n                        <div class=\"text-right\">\r\n                            <h4 class=\"font-bold text-gray-800\">משה כהן</h4>\r\n                            <p class=\"text-gray-500 text-sm\">מנכ\"ל, חברת אלפא</p>\r\n                        </div>\r\n                    <div class=\"image-edit-overlay\" style=\"position: absolute; inset: 0px; background-color: rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; margin-left: 40px; pointer-events: none;\"><div><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"white\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 13a3 3 0 11-6 0 3 3 0 016 0z\"></path></svg></div></div></div>\r\n                </div>\r\n                \r\n                <div class=\"p-6 bg-white rounded-lg shadow-md relative\">\r\n                    <div class=\"text-indigo-500 text-6xl absolute top-2 right-4 opacity-20\">\"</div>\r\n                    <div class=\"flex justify-center mb-4\">\r\n                        <div class=\"flex text-yellow-400\">\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                        </div>\r\n                    </div>\r\n                    <p class=\"text-gray-600 mb-6 relative z-10\">הגדלנו את ההכנסות שלנו ב-45% מאז שהתחלנו להשתמש במוצר! זו היתה אחת ההחלטות העסקיות הטובות ביותר שקיבלנו.</p>\r\n                    <div class=\"flex items-center\" style=\"position: relative;\">\r\n                        <img src=\"https://images.unsplash.com/photo-1573497019940-1c28c88b4f3e?ixlib=rb-4.0.3&amp;ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&amp;auto=format&amp;fit=crop&amp;w=100&amp;q=80\" alt=\"לקוח 2\" class=\"w-12 h-12 rounded-full object-cover ml-3\" style=\"cursor: pointer;\">\r\n                        <div class=\"text-right\">\r\n                            <h4 class=\"font-bold text-gray-800\">רונית לוי</h4>\r\n                            <p class=\"text-gray-500 text-sm\">בעלת עסק</p>\r\n                        </div>\r\n                    <div class=\"image-edit-overlay\" style=\"position: absolute; inset: 0px; background-color: rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; margin-left: 40px; pointer-events: none;\"><div><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"white\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 13a3 3 0 11-6 0 3 3 0 016 0z\"></path></svg></div></div></div>\r\n                </div>\r\n                \r\n                <div class=\"p-6 bg-white rounded-lg shadow-md relative\">\r\n                    <div class=\"text-indigo-500 text-6xl absolute top-2 right-4 opacity-20\">\"</div>\r\n                    <div class=\"flex justify-center mb-4\">\r\n                        <div class=\"flex text-yellow-400\">\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                            <i class=\"ri-star-fill mx-0.5\"></i>\r\n                        </div>\r\n                    </div>\r\n                    <p class=\"text-gray-600 mb-6 relative z-10\">חששתי בהתחלה, אבל תוך שבוע כבר ראיתי תוצאות מדהימות. התמיכה שלהם מעולה והמוצר פשוט מעל ומעבר לציפיות.</p>\r\n                    <div class=\"flex items-center\" style=\"position: relative;\">\r\n                        <img src=\"https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&amp;ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&amp;auto=format&amp;fit=crop&amp;w=100&amp;q=80\" alt=\"לקוח 3\" class=\"w-12 h-12 rounded-full object-cover ml-3\" style=\"cursor: pointer;\">\r\n                        <div class=\"text-right\">\r\n                            <h4 class=\"font-bold text-gray-800\">אלון גולדמן</h4>\r\n                            <p class=\"text-gray-500 text-sm\">יזם</p>\r\n                        </div>\r\n                    <div class=\"image-edit-overlay\" style=\"position: absolute; inset: 0px; background-color: rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; margin-left: 40px; pointer-events: none;\"><div><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"white\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 13a3 3 0 11-6 0 3 3 0 016 0z\"></path></svg></div></div></div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n    <!-- Pricing Section -->\r\n    <section id=\"pricing\" class=\"py-16 bg-gray-50\" data-section-type=\"pricing-1\" data-title=\"מחירים פשוטים ושקופים\" data-index=\"4\" style=\"outline: none;\">\r\n        <div class=\"container mx-auto px-4\">\r\n            <div class=\"text-center mb-12\">\r\n                <h2 class=\"text-3xl font-bold mb-2 text-gray-800\">מחירים פשוטים ושקופים</h2>\r\n                <p class=\"text-xl text-gray-600 max-w-3xl mx-auto\">בחרו את החבילה המתאימה לכם</p>\r\n            </div>\r\n            \r\n            <div class=\"flex flex-wrap justify-center gap-8\">\r\n                <div class=\"bg-white rounded-lg shadow-md overflow-hidden w-full md:w-72 border border-gray-200\">\r\n                    <div class=\"p-6 bg-gray-50 text-center border-b\">\r\n                        <h3 class=\"text-2xl font-bold text-gray-800 mb-1\">בסיסי</h3>\r\n                        <p class=\"text-gray-500 mb-4\">למתחילים</p>\r\n                        <div class=\"text-center\">\r\n                            <span class=\"text-4xl font-bold text-gray-800\">₪99</span>\r\n                            <span class=\"text-gray-500\">/חודש</span>\r\n                        </div>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <ul class=\"space-y-3\">\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה בסיסית 1</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה בסיסית 2</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה בסיסית 3</span>\r\n                            </li>\r\n                            <li class=\"flex items-center text-gray-400\">\r\n                                <i class=\"ri-close-line ml-2\"></i>\r\n                                <span>תכונה מתקדמת 1</span>\r\n                            </li>\r\n                            <li class=\"flex items-center text-gray-400\">\r\n                                <i class=\"ri-close-line ml-2\"></i>\r\n                                <span>תכונה מתקדמת 2</span>\r\n                            </li>\r\n                        </ul>\r\n                        <a href=\"#contact\" class=\"w-full mt-8 block text-center py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-300\">הזמינו עכשיו</a>\r\n                    </div>\r\n                </div>\r\n                \r\n                <div class=\"bg-white rounded-lg shadow-xl overflow-hidden w-full md:w-72 border-2 border-indigo-500 relative\">\r\n                    <div class=\"absolute top-0 left-0 right-0 bg-indigo-500 text-white text-center text-sm py-1\">\r\n                        הכי פופולרי\r\n                    </div>\r\n                    <div class=\"p-6 bg-indigo-50 text-center border-b\">\r\n                        <h3 class=\"text-2xl font-bold text-gray-800 mb-1\">פרו</h3>\r\n                        <p class=\"text-gray-500 mb-4\">לעסקים צומחים</p>\r\n                        <div class=\"text-center\">\r\n                            <span class=\"text-4xl font-bold text-gray-800\">₪199</span>\r\n                            <span class=\"text-gray-500\">/חודש</span>\r\n                        </div>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <ul class=\"space-y-3\">\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">כל התכונות הבסיסיות</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה מתקדמת 1</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה מתקדמת 2</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה נוספת 1</span>\r\n                            </li>\r\n                            <li class=\"flex items-center text-gray-400\">\r\n                                <i class=\"ri-close-line ml-2\"></i>\r\n                                <span>תכונה פרימיום</span>\r\n                            </li>\r\n                        </ul>\r\n                        <a href=\"#contact\" class=\"w-full mt-8 block text-center py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-300\">הזמינו עכשיו</a>\r\n                    </div>\r\n                </div>\r\n                \r\n                <div class=\"bg-white rounded-lg shadow-md overflow-hidden w-full md:w-72 border border-gray-200\">\r\n                    <div class=\"p-6 bg-gray-50 text-center border-b\">\r\n                        <h3 class=\"text-2xl font-bold text-gray-800 mb-1\">פרימיום</h3>\r\n                        <p class=\"text-gray-500 mb-4\">לעסקים מבוססים</p>\r\n                        <div class=\"text-center\">\r\n                            <span class=\"text-4xl font-bold text-gray-800\">₪299</span>\r\n                            <span class=\"text-gray-500\">/חודש</span>\r\n                        </div>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <ul class=\"space-y-3\">\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">כל התכונות של חבילת פרו</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תכונה פרימיום</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">תמיכה VIP</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">הדרכה אישית</span>\r\n                            </li>\r\n                            <li class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 ml-2\"></i>\r\n                                <span class=\"text-gray-700\">עדיפות בפיתוח</span>\r\n                            </li>\r\n                        </ul>\r\n                        <a href=\"#contact\" class=\"w-full mt-8 block text-center py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-300\">הזמינו עכשיו</a>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n    <!-- FAQ Section -->\r\n    <section id=\"faq\" class=\"py-16 bg-white\" data-section-type=\"faq-1\" data-title=\"שאלות נפוצות\" data-index=\"5\" style=\"outline: none;\">\r\n        <div class=\"container mx-auto px-4\">\r\n            <div class=\"max-w-3xl mx-auto\">\r\n                <div class=\"text-center mb-12\">\r\n                    <h2 class=\"text-3xl font-bold mb-2 text-gray-800\">שאלות נפוצות</h2>\r\n                    <p class=\"text-xl text-gray-600\">תשובות לשאלות שנשאלות בתדירות גבוהה</p>\r\n                </div>\r\n                \r\n                <div class=\"space-y-4\">\r\n                    <div class=\"border border-gray-200 rounded-lg overflow-hidden\">\r\n                        <button class=\"flex items-center justify-between w-full p-4 text-right bg-white hover:bg-gray-50 focus:outline-none\">\r\n                            <span class=\"text-lg font-medium text-gray-800\">כמה זמן לוקח להתחיל להשתמש במוצר?</span>\r\n                            <i class=\"ri-arrow-down-s-line text-xl text-gray-400\"></i>\r\n                        </button>\r\n                        <div class=\"px-4 pb-4\">\r\n                            <p class=\"text-gray-600\">תוכלו להתחיל להשתמש במוצר מיד לאחר ההרשמה והתשלום. ההגדרה הראשונית אורכת פחות מ-5 דקות ותוכלו ליהנות מהיתרונות באופן מיידי.</p>\r\n                        </div>\r\n                    </div>\r\n                    \r\n                    <div class=\"border border-gray-200 rounded-lg overflow-hidden\">\r\n                        <button class=\"flex items-center justify-between w-full p-4 text-right bg-white hover:bg-gray-50 focus:outline-none\">\r\n                            <span class=\"text-lg font-medium text-gray-800\">האם יש תקופת ניסיון?</span>\r\n                            <i class=\"ri-arrow-down-s-line text-xl text-gray-400\"></i>\r\n                        </button>\r\n                        <div class=\"px-4 pb-4\">\r\n                            <p class=\"text-gray-600\">כן, אנחנו מציעים תקופת ניסיון של 30 יום ללא התחייבות. תוכלו לבטל בכל עת במהלך תקופת הניסיון ולא תחויבו.</p>\r\n                        </div>\r\n                    </div>\r\n                    \r\n                    <div class=\"border border-gray-200 rounded-lg overflow-hidden\">\r\n                        <button class=\"flex items-center justify-between w-full p-4 text-right bg-white hover:bg-gray-50 focus:outline-none\">\r\n                            <span class=\"text-lg font-medium text-gray-800\">איך מבטלים את המנוי?</span>\r\n                            <i class=\"ri-arrow-down-s-line text-xl text-gray-400\"></i>\r\n                        </button>\r\n                        <div class=\"px-4 pb-4\">\r\n                            <p class=\"text-gray-600\">ביטול המנוי פשוט מאוד. ניתן לבטל בכל עת דרך החשבון האישי באתר, או על ידי פנייה לשירות הלקוחות שלנו. אין קנסות יציאה או התחייבויות.</p>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n    <!-- Call To Action Section -->\r\n    <section id=\"cta\" class=\"py-16 bg-indigo-600 text-white relative\" data-section-type=\"cta-2\" data-title=\"מוכנים להתחיל? הצטרפו עוד היום!\" data-index=\"6\" style=\"outline: none;\">\r\n        <div class=\"absolute inset-0\" style=\"position: relative;\">\r\n            <img src=\"https://images.unsplash.com/photo-1557804506-669a67965ba0?ixlib=rb-4.0.3&amp;ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&amp;auto=format&amp;fit=crop&amp;w=1470&amp;q=80\" alt=\"רקע\" class=\"w-full h-full object-cover opacity-20\" style=\"cursor: pointer;\">\r\n        <div class=\"image-edit-overlay\" style=\"position: absolute; inset: 0px; background-color: rgba(79, 70, 229, 0.2); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; margin-left: 40px; pointer-events: none;\"><div><svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"white\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z\"></path><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M15 13a3 3 0 11-6 0 3 3 0 016 0z\"></path></svg></div></div></div>\r\n        <div class=\"container mx-auto px-4 text-center relative z-10\">\r\n            <h2 class=\"text-3xl md:text-4xl font-bold mb-4\">מוכנים להתחיל? הצטרפו עוד היום!</h2>\r\n            <p class=\"text-xl mb-8 max-w-3xl mx-auto\">אל תחמיצו את המבצע המיוחד שלנו - הנחה של 20% לנרשמים החודש</p>\r\n            <div class=\"flex flex-wrap justify-center gap-4\">\r\n                <a href=\"#contact\" class=\"bg-white text-indigo-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition duration-300 shadow-lg\">התחילו עכשיו</a>\r\n                <a href=\"tel:+972123456789\" class=\"bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:bg-opacity-10 transition duration-300\">\r\n                    <i class=\"ri-phone-line ml-2\"></i>דברו איתנו\r\n                </a>\r\n            </div>\r\n        </div>\r\n    </section>\r\n\r\n<!-- Contact Section -->\r\n<section id=\"contact\" class=\"py-16 bg-white\" data-section-type=\"contact-1\" data-title=\"הרשמו עכשיו וקבלו 30 יום ניסיון בחינם\" data-index=\"7\" style=\"outline: none;\">\r\n    <div class=\"container mx-auto px-4\">\r\n        <div class=\"max-w-3xl mx-auto text-center mb-12\">\r\n            <h2 class=\"text-3xl font-bold mb-2 text-gray-800\">הרשמו עכשיו וקבלו 30 יום ניסיון בחינם</h2>\r\n            <p class=\"text-xl text-gray-600\">השאירו פרטים ונחזור אליכם בהקדם</p>\r\n        </div>\r\n        \r\n        <div class=\"max-w-lg mx-auto bg-white rounded-lg shadow-lg p-8 border border-gray-200\">\r\n            <form action=\"#\" method=\"post\" data-tags=\"לקוח חדש\" data-list-id=\"1\" id=\"form-1745251549285\" data-form-id=\"form-1745251549285\"><div class=\"mb-4\"><label for=\"email\" class=\"block text-gray-700 text-sm font-bold mb-2\">אימייל <span class=\"text-red-500\">*</span></label><input type=\"email\" class=\"shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline\" id=\"email\" name=\"email\" required=\"required\"><input type=\"hidden\" name=\"custom_field\" value=\"{&quot;fieldType&quot;:&quot;email&quot;,&quot;required&quot;:true,&quot;options&quot;:[]}\"></div><div class=\"mb-4\"><label for=\"phone\" class=\"block text-gray-700 text-sm font-bold mb-2\">טלפון</label><input type=\"tel\" class=\"shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline\" id=\"phone\" name=\"phone\"><input type=\"hidden\" name=\"custom_field\" value=\"{&quot;fieldType&quot;:&quot;tel&quot;,&quot;required&quot;:false,&quot;options&quot;:[]}\"></div><div class=\"mb-4\"><label for=\"plan\" class=\"block text-gray-700 text-sm font-bold mb-2\">חבילה מועדפת</label><select class=\"shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline\" id=\"plan\" name=\"plan\"><option value=\"basic\">בסיסי - ₪99/חודש</option><option value=\"pro\">פרו - ₪199/חודש</option><option value=\"premium\">פרימיום - ₪299/חודש</option></select><input type=\"hidden\" name=\"custom_field\" value=\"{&quot;fieldType&quot;:&quot;select&quot;,&quot;required&quot;:false,&quot;options&quot;:[{&quot;value&quot;:&quot;basic&quot;,&quot;text&quot;:&quot;בסיסי - ₪99/חודש&quot;},{&quot;value&quot;:&quot;pro&quot;,&quot;text&quot;:&quot;פרו - ₪199/חודש&quot;},{&quot;value&quot;:&quot;premium&quot;,&quot;text&quot;:&quot;פרימיום - ₪299/חודש&quot;}]}\"></div><div class=\"mb-4\"><label for=\"name\" class=\"block text-gray-700 text-sm font-bold mb-2\">שם מלא <span class=\"text-red-500\">*</span></label><input type=\"text\" class=\"shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline\" id=\"name\" name=\"name\" required=\"required\"><input type=\"hidden\" name=\"custom_field\" value=\"{&quot;fieldType&quot;:&quot;text&quot;,&quot;required&quot;:true,&quot;options&quot;:[]}\"></div><div class=\"mb-4\"><label for=\"field-4\" class=\"block text-gray-700 text-sm font-bold mb-2\">אני מסכים/ה לתנאי השימוש ולמדיניות הפרטיות <span class=\"text-red-500\">*</span></label><div class=\"flex items-center\"><input class=\"mr-2\" type=\"checkbox\" id=\"field-4\" name=\"field-4\" required=\"required\"><span class=\"text-gray-700\">אני מסכים/ה לתנאי השימוש ולמדיניות הפרטיות</span></div><input type=\"hidden\" name=\"custom_field\" value=\"{&quot;fieldType&quot;:&quot;checkbox&quot;,&quot;required&quot;:true,&quot;options&quot;:[]}\"></div><div class=\"mb-4\"><label for=\"date\" class=\"block text-gray-700 text-sm font-bold mb-2\">תאריך לידה</label><input type=\"date\" class=\"shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline\" id=\"date\" name=\"date\"><input type=\"hidden\" name=\"custom_field\" value=\"{&quot;fieldType&quot;:&quot;date&quot;,&quot;required&quot;:false,&quot;options&quot;:[]}\"></div><input type=\"hidden\" name=\"tags\" value=\"לקוח חדש\"><div class=\"flex items-center justify-between\"><input type=\"submit\" value=\"שלח\" class=\"bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline\"></div></form>\r\n        </div>\r\n    </div>\r\n</section>\r\n\r\n<!-- Footer -->\r\n<footer id=\"footer\" class=\"bg-gray-800 text-white py-10\" data-section-type=\"footer-1\" data-title=\"המוצר שלנו\" data-index=\"8\" style=\"outline: none;\">\r\n    <div class=\"container mx-auto px-4\">\r\n        <div class=\"flex flex-wrap justify-between\">\r\n            <div class=\"w-full md:w-1/3 mb-6 md:mb-0\">\r\n                <h3 class=\"text-xl font-bold mb-4\">המוצר שלנו</h3>\r\n                <p class=\"text-gray-400 mb-4\">הפתרון המהפכני שיעזור לכם להגדיל את העסק שלכם, לחסוך זמן ולהגביר את היעילות.</p>\r\n                <div class=\"flex space-x-4 rtl:space-x-reverse\">\r\n                    <a href=\"#\" class=\"text-gray-400 hover:text-white transition duration-300\">\r\n                        <i class=\"ri-facebook-fill text-xl\"></i>\r\n                    </a>\r\n                    <a href=\"#\" class=\"text-gray-400 hover:text-white transition duration-300\">\r\n                        <i class=\"ri-instagram-line text-xl\"></i>\r\n                    </a>\r\n                    <a href=\"#\" class=\"text-gray-400 hover:text-white transition duration-300\">\r\n                        <i class=\"ri-twitter-fill text-xl\"></i>\r\n                    </a>\r\n                    <a href=\"#\" class=\"text-gray-400 hover:text-white transition duration-300\">\r\n                        <i class=\"ri-linkedin-fill text-xl\"></i>\r\n                    </a>\r\n                </div>\r\n            </div>\r\n            \r\n            <div class=\"w-full md:w-1/3 mb-6 md:mb-0\">\r\n                <h3 class=\"text-xl font-bold mb-4\">קישורים מהירים</h3>\r\n                <ul class=\"space-y-2\">\r\n                    <li><a href=\"#features\" class=\"text-gray-400 hover:text-white transition duration-300\">יתרונות</a></li>\r\n                    <li><a href=\"#pricing\" class=\"text-gray-400 hover:text-white transition duration-300\">מחירים</a></li>\r\n                    <li><a href=\"#testimonials\" class=\"text-gray-400 hover:text-white transition duration-300\">המלצות</a></li>\r\n                    <li><a href=\"#faq\" class=\"text-gray-400 hover:text-white transition duration-300\">שאלות נפוצות</a></li>\r\n                    <li><a href=\"#\" class=\"text-gray-400 hover:text-white transition duration-300\">תנאי שימוש</a></li>\r\n                    <li><a href=\"#\" class=\"text-gray-400 hover:text-white transition duration-300\">מדיניות פרטיות</a></li>\r\n                </ul>\r\n            </div>\r\n            \r\n            <div class=\"w-full md:w-1/3\">\r\n                <h3 class=\"text-xl font-bold mb-4\">צור קשר</h3>\r\n                <ul class=\"space-y-2\">\r\n                    <li class=\"flex items-center\">\r\n                        <i class=\"ri-map-pin-line text-indigo-400 ml-2\"></i>\r\n                        <span class=\"text-gray-400\">רחוב האלמוגים 15, תל אביב</span>\r\n                    </li>\r\n                    <li class=\"flex items-center\">\r\n                        <i class=\"ri-phone-line text-indigo-400 ml-2\"></i>\r\n                        <a href=\"tel:+972123456789\" class=\"text-gray-400 hover:text-white transition duration-300\">03-1234567</a>\r\n                    </li>\r\n                    <li class=\"flex items-center\">\r\n                        <i class=\"ri-mail-line text-indigo-400 ml-2\"></i>\r\n                        <a href=\"mailto:info@example.com\" class=\"text-gray-400 hover:text-white transition duration-300\">info@example.com</a>\r\n                    </li>\r\n                </ul>\r\n            </div>\r\n        </div>\r\n        \r\n        <div class=\"border-t border-gray-700 mt-8 pt-8 text-center\">\r\n            <p class=\"text-gray-400 text-sm\">© 2023 המוצר שלנו. כל הזכויות שמורות.</p>\r\n        </div>\r\n    </div>\r\n</footer>\r\n\r\n\r\n</body></html>', NULL, NULL, NULL, NULL, NULL, 0, '{\"font\": \"Heebo, sans-serif\", \"title\": \"דף מכירות\", \"textColor\": \"#111827\", \"accentColor\": \"#ec4899\", \"primaryColor\": \"#6366f1\", \"secondaryColor\": \"#4f46e5\", \"backgroundColor\": \"#ffffff\"}', '[{\"id\": \"section-0\", \"type\": \"header\", \"style\": [], \"content\": {\"headings\": [{\"tag\": \"h1\", \"text\": \"המוצר שישנה את חייך\"}], \"paragraphs\": [\"גלה את הפתרון המושלם שכבר עזר ליותר מ-10,000 אנשים להשיג תוצאות יוצאות דופן\"]}, \"visible\": true}, {\"id\": \"section-1\", \"type\": \"custom\", \"style\": [], \"content\": {\"headings\": [{\"tag\": \"h2\", \"text\": \"למה אלפי לקוחות בוחרים בנו?\"}, {\"tag\": \"h3\", \"text\": \"חוסך זמן\"}, {\"tag\": \"h3\", \"text\": \"חסכון בעלויות\"}, {\"tag\": \"h3\", \"text\": \"איכות מעולה\"}], \"paragraphs\": [\"המוצר שלנו חוסך לך שעות של עבודה בכל שבוע ומאפשר לך להתמקד במה שחשוב באמת\", \"עם המוצר שלנו תוכל לחסוך אלפי שקלים בחודש ולהגדיל את הרווחיות שלך\", \"המוצר שלנו מיוצר בסטנדרטים הגבוהים ביותר ומגיע עם אחריות מלאה לשנתיים\"]}, \"visible\": true}, {\"id\": \"section-2\", \"type\": \"custom\", \"style\": [], \"content\": {\"images\": [{\"alt\": \"Product Image\", \"src\": \"https://via.placeholder.com/600x400\"}], \"headings\": [{\"tag\": \"h2\", \"text\": \"המוצר שכולם מדברים עליו\"}], \"paragraphs\": [\"המוצר שלנו תוכנן במיוחד כדי לפתור את הבעיות היומיומיות שלך. עם יותר מ-10,000 לקוחות מרוצים, המוצר הוכיח את עצמו כפתרון האידיאלי.\"]}, \"visible\": true}, {\"id\": \"section-3\", \"type\": \"custom\", \"style\": [], \"content\": {\"images\": [{\"alt\": \"Customer\", \"src\": \"https://via.placeholder.com/50x50\"}, {\"alt\": \"Customer\", \"src\": \"https://via.placeholder.com/50x50\"}, {\"alt\": \"Customer\", \"src\": \"https://via.placeholder.com/50x50\"}], \"headings\": [{\"tag\": \"h2\", \"text\": \"מה אומרים הלקוחות שלנו\"}], \"paragraphs\": [\"\\\"המוצר הזה שינה את חיי! אני לא יכול לדמיין את חיי בלעדיו. התוצאות היו מעל ומעבר למצופה.\\\"\", \"ירושלים\", \"\\\"השירות לקוחות מעולה והמוצר עובד בדיוק כפי שהובטח. אני ממליצה בחום לכל מי שמתלבט.\\\"\", \"תל אביב\", \"\\\"קניתי את המוצר לפני חצי שנה והוא כבר החזיר את ההשקעה שלי פי כמה. מוצר מעולה עם ערך אדיר.\\\"\", \"חיפה\"]}, \"visible\": true}, {\"id\": \"pricing\", \"type\": \"custom\", \"style\": [], \"content\": {\"buttons\": [{\"url\": \"#\", \"text\": \"\\r\\n                                        הזמן עכשיו - מבצע מוגבל בזמן\\r\\n                                    \", \"type\": \"outline\"}], \"headings\": [{\"tag\": \"h2\", \"text\": \"הצטרף עכשיו במחיר מיוחד\"}, {\"tag\": \"h3\", \"text\": \"חבילה מושלמת\"}], \"paragraphs\": [\"מחיר מבצע - לזמן מוגבל בלבד!\", \"כל מה שאתה צריך במחיר אחד\", \"המבצע בתוקף עד למלאי. לא כולל דמי משלוח. תקנון באתר.\"]}, \"visible\": true}, {\"id\": \"section-5\", \"type\": \"footer\", \"style\": [], \"content\": {\"paragraphs\": [\"© 2023 החברה שלך. כל הזכויות שמורות.\"]}, \"visible\": true}]', 1, '2025-04-20 11:03:49', '2025-04-21 16:35:26');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `landing_page_forms`
--

CREATE TABLE `landing_page_forms` (
  `id` int(11) NOT NULL,
  `landing_page_id` int(11) NOT NULL,
  `form_id` varchar(255) NOT NULL,
  `list_id` int(11) DEFAULT NULL,
  `tags` text,
  `redirect_url` varchar(255) DEFAULT NULL,
  `thank_you_message` text,
  `webhook_url` varchar(255) DEFAULT NULL,
  `notification_emails` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `max_landing_pages` int(11) NOT NULL,
  `max_leads` int(11) NOT NULL,
  `max_emails` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- הוצאת מידע עבור טבלה `plans`
--

INSERT INTO `plans` (`id`, `name`, `description`, `price`, `max_landing_pages`, `max_leads`, `max_emails`, `created_at`) VALUES
(1, 'ניסיון', 'מסלול ניסיון חינם למשך 7 ימים', '0.00', 1, 50, 50, '2025-04-20 09:12:58'),
(2, 'בסיסי', 'מסלול בסיסי עם אפשרויות מוגבלות', '99.00', 1, 300, 300, '2025-04-20 09:12:58'),
(3, 'פרו', 'מסלול מתקדם לעסקים קטנים ובינוניים', '199.00', 5, 1000, 1000, '2025-04-20 09:12:58'),
(4, 'אולטרה', 'מסלול מקצועי לעסקים גדולים', '399.00', 15, 5000, 5000, '2025-04-20 09:12:58');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `landing_page_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `custom_fields` json DEFAULT NULL,
  `is_subscribed` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- הוצאת מידע עבור טבלה `subscribers`
--

INSERT INTO `subscribers` (`id`, `user_id`, `landing_page_id`, `email`, `first_name`, `last_name`, `phone`, `custom_fields`, `is_subscribed`, `created_at`) VALUES
(1, 1, 2, '2@2.com', 'מריה סוחין', '', '0547359759', '{\"plan\": \"pro\"}', 1, '2025-04-21 08:31:12'),
(2, 1, 2, 'nofar@gmail.com', 'נופר דהן', '', '0542284283', '{\"date\": \"2025-04-02\", \"plan\": \"pro\", \"field-4\": \"on\", \"custom_field\": \"{&quot;fieldType&quot;:&quot;date&quot;,&quot;required&quot;:false,&quot;options&quot;:[]}\"}', 1, '2025-04-21 10:14:39'),
(3, 1, 2, 'LaBeauteILBeauty@gmail.com', 'בדיקה', '', '0507759021', '{\"date\": \"\", \"plan\": \"basic\", \"tags\": \"לקוח חדש\", \"field-4\": \"on\", \"_form_tags\": \"לקוח חדש\", \"ajax_submit\": \"1\", \"custom_field\": \"{&quot;fieldType&quot;:&quot;date&quot;,&quot;required&quot;:false,&quot;options&quot;:[]}\"}', 1, '2025-04-21 15:20:30'),
(5, 1, 2, 'ita@g.com', 'אמבר רומניה', '', '0542215554', '{\"date\": \"2025-05-02\", \"plan\": \"pro\", \"tags\": \"לקוח חדש\", \"field-4\": \"on\", \"_form_tags\": \"לקוח חדש\", \"custom_field\": \"{&quot;fieldType&quot;:&quot;date&quot;,&quot;required&quot;:false,&quot;options&quot;:[]}\"}', 1, '2025-04-21 10:32:41'),
(6, 1, 2, 'itadmit@gmail.com', 'יוגב אביטן', '', '0542284283', '{\"date\": \"\", \"plan\": \"basic\", \"tags\": \"לקוח חדש\", \"field-4\": \"on\", \"_form_tags\": \"לקוח חדש\", \"ajax_submit\": \"1\", \"custom_field\": \"{&quot;fieldType&quot;:&quot;date&quot;,&quot;required&quot;:false,&quot;options&quot;:[]}\"}', 1, '2025-04-21 16:08:16');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `subscriber_lists`
--

CREATE TABLE `subscriber_lists` (
  `id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `list_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- הוצאת מידע עבור טבלה `subscriber_lists`
--

INSERT INTO `subscriber_lists` (`id`, `subscriber_id`, `list_id`, `created_at`) VALUES
(1, 5, 1, '2025-04-21 17:14:44'),
(2, 3, 1, '2025-04-21 17:31:20'),
(3, 6, 1, '2025-04-21 19:08:16');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `templates`
--

CREATE TABLE `templates` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `thumbnail` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `html_structure` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- הוצאת מידע עבור טבלה `templates`
--

INSERT INTO `templates` (`id`, `name`, `description`, `thumbnail`, `html_structure`, `is_active`, `created_at`) VALUES
(1, 'בסיסי', 'תבנית בסיסית לדף נחיתה פשוט', '/customizer/templates/basic/thumbnail.svg', '<!DOCTYPE html>\r\n<html lang=\"he\" dir=\"rtl\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>דף נחיתה בסיסי</title>\r\n    <link href=\"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css\" rel=\"stylesheet\">\r\n    <link href=\"https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css\" rel=\"stylesheet\">\r\n    <style>\r\n        @import url(\'https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap\');\r\n        body {\r\n            font-family: \'Heebo\', sans-serif;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <header class=\"bg-indigo-600 text-white py-12\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <h1 class=\"text-4xl font-bold mb-4\">כותרת דף הנחיתה שלך</h1>\r\n            <p class=\"text-xl mb-8\">תיאור קצר של ההצעה או השירות שלך</p>\r\n            <a href=\"#form\" class=\"bg-white text-indigo-600 px-6 py-3 rounded-lg font-bold text-lg hover:bg-gray-100\">להרשמה</a>\r\n        </div>\r\n    </header>\r\n    \r\n    <main>\r\n        <section class=\"py-16 bg-white\">\r\n            <div class=\"container mx-auto px-4 text-center\">\r\n                <h2 class=\"text-3xl font-bold mb-8\">היתרונות שלנו</h2>\r\n                <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                    <div class=\"p-6\">\r\n                        <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                            <i class=\"ri-check-line text-3xl text-indigo-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">יתרון ראשון</h3>\r\n                        <p class=\"text-gray-600\">תיאור קצר של היתרון הראשון שלך</p>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                            <i class=\"ri-check-line text-3xl text-indigo-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">יתרון שני</h3>\r\n                        <p class=\"text-gray-600\">תיאור קצר של היתרון השני שלך</p>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                            <i class=\"ri-check-line text-3xl text-indigo-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">יתרון שלישי</h3>\r\n                        <p class=\"text-gray-600\">תיאור קצר של היתרון השלישי שלך</p>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n        \r\n        <section id=\"form\" class=\"py-16 bg-gray-100\">\r\n            <div class=\"container mx-auto px-4\">\r\n                <div class=\"max-w-md mx-auto bg-white rounded-lg shadow-lg p-8\">\r\n                    <h2 class=\"text-2xl font-bold mb-6 text-center\">השאירו פרטים</h2>\r\n                    <form action=\"/submit-form\" method=\"post\" class=\"space-y-4\">\r\n                        <div>\r\n                            <label for=\"name\" class=\"block text-sm font-medium text-gray-700 mb-1\">שם מלא</label>\r\n                            <input type=\"text\" id=\"name\" name=\"name\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" required>\r\n                        </div>\r\n                        <div>\r\n                            <label for=\"email\" class=\"block text-sm font-medium text-gray-700 mb-1\">אימייל</label>\r\n                            <input type=\"email\" id=\"email\" name=\"email\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" required>\r\n                        </div>\r\n                        <div>\r\n                            <label for=\"phone\" class=\"block text-sm font-medium text-gray-700 mb-1\">טלפון</label>\r\n                            <input type=\"tel\" id=\"phone\" name=\"phone\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\">\r\n                        </div>\r\n                        <div>\r\n                            <button type=\"submit\" class=\"w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500\">שלח</button>\r\n                        </div>\r\n                    </form>\r\n                </div>\r\n            </div>\r\n        </section>\r\n    </main>\r\n    \r\n    <footer class=\"bg-gray-800 text-white py-8\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <p>&copy; 2023 החברה שלך. כל הזכויות שמורות.</p>\r\n        </div>\r\n    </footer>\r\n</body>\r\n</html>', 1, '2025-04-20 09:18:57'),
(2, 'עסקי', 'תבנית מעוצבת לעסקים', '/customizer/templates/business/thumbnail.svg', '<!DOCTYPE html>\r\n<html lang=\"he\" dir=\"rtl\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>דף נחיתה עסקי</title>\r\n    <link href=\"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css\" rel=\"stylesheet\">\r\n    <link href=\"https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css\" rel=\"stylesheet\">\r\n    <style>\r\n        @import url(\'https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap\');\r\n        body {\r\n            font-family: \'Heebo\', sans-serif;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <header class=\"bg-blue-600 text-white py-16\">\r\n        <div class=\"container mx-auto px-4 md:flex md:items-center md:justify-between\">\r\n            <div class=\"md:w-1/2 mb-8 md:mb-0 md:ml-8\">\r\n                <h1 class=\"text-4xl font-bold mb-4\">פתרונות עסקיים מתקדמים</h1>\r\n                <p class=\"text-xl mb-8\">אנו מספקים פתרונות עסקיים מותאמים אישית שעוזרים לעסק שלך לצמוח</p>\r\n                <div class=\"flex space-x-4\">\r\n                    <a href=\"#contact\" class=\"bg-white text-blue-600 px-6 py-3 rounded-lg font-bold text-lg hover:bg-gray-100\">דבר איתנו</a>\r\n                    <a href=\"#services\" class=\"border border-white text-white px-6 py-3 rounded-lg font-bold text-lg hover:bg-blue-700\">לשירותים שלנו</a>\r\n                </div>\r\n            </div>\r\n            <div class=\"md:w-1/2\">\r\n                <img src=\"https://via.placeholder.com/600x400\" alt=\"Business Solutions\" class=\"rounded-lg shadow-xl\">\r\n            </div>\r\n        </div>\r\n    </header>\r\n    \r\n    <main>\r\n        <section id=\"services\" class=\"py-16 bg-white\">\r\n            <div class=\"container mx-auto px-4\">\r\n                <h2 class=\"text-3xl font-bold mb-8 text-center\">השירותים שלנו</h2>\r\n                <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                    <div class=\"bg-gray-50 p-6 rounded-lg shadow-md\">\r\n                        <div class=\"w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4\">\r\n                            <i class=\"ri-line-chart-line text-3xl text-blue-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">ייעוץ עסקי</h3>\r\n                        <p class=\"text-gray-600\">אנו מספקים ייעוץ עסקי מקצועי שעוזר לעסק שלך לצמוח ולהתפתח</p>\r\n                    </div>\r\n                    <div class=\"bg-gray-50 p-6 rounded-lg shadow-md\">\r\n                        <div class=\"w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4\">\r\n                            <i class=\"ri-briefcase-line text-3xl text-blue-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">פיתוח עסקי</h3>\r\n                        <p class=\"text-gray-600\">אנו מסייעים לעסקים לפתח אסטרטגיות צמיחה והתרחבות</p>\r\n                    </div>\r\n                    <div class=\"bg-gray-50 p-6 rounded-lg shadow-md\">\r\n                        <div class=\"w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4\">\r\n                            <i class=\"ri-money-dollar-circle-line text-3xl text-blue-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">ייעוץ פיננסי</h3>\r\n                        <p class=\"text-gray-600\">אנו מספקים ייעוץ פיננסי מקצועי שעוזר לעסק שלך לנהל את הכספים שלו</p>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n        \r\n        <section class=\"py-16 bg-gray-50\">\r\n            <div class=\"container mx-auto px-4\">\r\n                <div class=\"max-w-3xl mx-auto text-center\">\r\n                    <h2 class=\"text-3xl font-bold mb-6\">למה לבחור בנו</h2>\r\n                    <p class=\"text-xl text-gray-600 mb-8\">אנו מספקים פתרונות עסקיים מותאמים אישית עם יותר מ-10 שנות ניסיון בתחום</p>\r\n                    <div class=\"flex flex-wrap justify-center gap-4\">\r\n                        <div class=\"bg-white p-4 rounded-lg shadow-md flex items-center\">\r\n                            <i class=\"ri-user-star-line text-2xl text-blue-600 ml-3\"></i>\r\n                            <span>צוות מקצועי</span>\r\n                        </div>\r\n                        <div class=\"bg-white p-4 rounded-lg shadow-md flex items-center\">\r\n                            <i class=\"ri-customer-service-2-line text-2xl text-blue-600 ml-3\"></i>\r\n                            <span>שירות לקוחות מעולה</span>\r\n                        </div>\r\n                        <div class=\"bg-white p-4 rounded-lg shadow-md flex items-center\">\r\n                            <i class=\"ri-medal-line text-2xl text-blue-600 ml-3\"></i>\r\n                            <span>מובילים בתחום</span>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n        \r\n        <section id=\"contact\" class=\"py-16 bg-blue-600 text-white\">\r\n            <div class=\"container mx-auto px-4\">\r\n                <div class=\"max-w-md mx-auto bg-white text-gray-800 rounded-lg shadow-lg p-8\">\r\n                    <h2 class=\"text-2xl font-bold mb-6 text-center\">צור קשר</h2>\r\n                    <form action=\"/submit-form\" method=\"post\" class=\"space-y-4\">\r\n                        <div>\r\n                            <label for=\"name\" class=\"block text-sm font-medium text-gray-700 mb-1\">שם מלא</label>\r\n                            <input type=\"text\" id=\"name\" name=\"name\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500\" required>\r\n                        </div>\r\n                        <div>\r\n                            <label for=\"company\" class=\"block text-sm font-medium text-gray-700 mb-1\">חברה</label>\r\n                            <input type=\"text\" id=\"company\" name=\"company\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500\">\r\n                        </div>\r\n                        <div>\r\n                            <label for=\"email\" class=\"block text-sm font-medium text-gray-700 mb-1\">אימייל</label>\r\n                            <input type=\"email\" id=\"email\" name=\"email\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500\" required>\r\n                        </div>\r\n                        <div>\r\n                            <label for=\"phone\" class=\"block text-sm font-medium text-gray-700 mb-1\">טלפון</label>\r\n                            <input type=\"tel\" id=\"phone\" name=\"phone\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500\" required>\r\n                        </div>\r\n                        <div>\r\n                            <label for=\"message\" class=\"block text-sm font-medium text-gray-700 mb-1\">הודעה</label>\r\n                            <textarea id=\"message\" name=\"message\" rows=\"4\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500\"></textarea>\r\n                        </div>\r\n                        <div>\r\n                            <button type=\"submit\" class=\"w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500\">שלח הודעה</button>\r\n                        </div>\r\n                    </form>\r\n                </div>\r\n            </div>\r\n        </section>\r\n    </main>\r\n    \r\n    <footer class=\"bg-gray-800 text-white py-8\">\r\n        <div class=\"container mx-auto px-4\">\r\n            <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                <div>\r\n                    <h3 class=\"text-xl font-bold mb-4\">החברה שלנו</h3>\r\n                    <p class=\"text-gray-400\">אנו מספקים פתרונות עסקיים מותאמים אישית שעוזרים לעסק שלך לצמוח</p>\r\n                </div>\r\n                <div>\r\n                    <h3 class=\"text-xl font-bold mb-4\">שירותים</h3>\r\n                    <ul class=\"space-y-2 text-gray-400\">\r\n                        <li>ייעוץ עסקי</li>\r\n                        <li>פיתוח עסקי</li>\r\n                        <li>ייעוץ פיננסי</li>\r\n                    </ul>\r\n                </div>\r\n                <div>\r\n                    <h3 class=\"text-xl font-bold mb-4\">צור קשר</h3>\r\n                    <ul class=\"space-y-2 text-gray-400\">\r\n                        <li>info@example.com</li>\r\n                        <li>03-1234567</li>\r\n                        <li>רחוב הרצל 123, תל אביב</li>\r\n                    </ul>\r\n                </div>\r\n            </div>\r\n            <div class=\"border-t border-gray-700 mt-8 pt-8 text-center\">\r\n                <p>&copy; 2023 החברה שלך. כל הזכויות שמורות.</p>\r\n            </div>\r\n        </div>\r\n    </footer>\r\n</body>\r\n</html>', 1, '2025-04-20 09:18:57'),
(3, 'מכירות', 'תבנית למכירת מוצר או שירות', '/customizer/templates/sales/thumbnail.svg', '<!DOCTYPE html>\r\n<html lang=\"he\" dir=\"rtl\">\r\n<head>\r\n    <meta charset=\"UTF-8\">\r\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\r\n    <title>דף מכירות</title>\r\n    <link href=\"https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css\" rel=\"stylesheet\">\r\n    <link href=\"https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css\" rel=\"stylesheet\">\r\n    <style>\r\n        @import url(\'https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500;700&display=swap\');\r\n        body {\r\n            font-family: \'Heebo\', sans-serif;\r\n        }\r\n    </style>\r\n</head>\r\n<body>\r\n    <header class=\"bg-gradient-to-r from-purple-600 to-indigo-600 text-white py-20\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <span class=\"inline-block bg-white text-indigo-600 px-4 py-1 rounded-full text-sm font-bold mb-4\">מבצע מיוחד - מוגבל בזמן</span>\r\n            <h1 class=\"text-5xl font-bold mb-6\">המוצר שישנה את חייך</h1>\r\n            <p class=\"text-xl mb-10 max-w-2xl mx-auto\">גלה את הפתרון המושלם שכבר עזר ליותר מ-10,000 אנשים להשיג תוצאות יוצאות דופן</p>\r\n            <a href=\"#pricing\" class=\"bg-white text-indigo-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 inline-block\">קבל את ההצעה המיוחדת</a>\r\n        </div>\r\n    </header>\r\n    \r\n    <main>\r\n        <section class=\"py-16 bg-white\">\r\n            <div class=\"container mx-auto px-4 text-center\">\r\n                <h2 class=\"text-3xl font-bold mb-12\">למה אלפי לקוחות בוחרים בנו?</h2>\r\n                <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                    <div class=\"p-6\">\r\n                        <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                            <i class=\"ri-timer-line text-3xl text-indigo-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">חוסך זמן</h3>\r\n                        <p class=\"text-gray-600\">המוצר שלנו חוסך לך שעות של עבודה בכל שבוע ומאפשר לך להתמקד במה שחשוב באמת</p>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                            <i class=\"ri-money-dollar-circle-line text-3xl text-indigo-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">חסכון בעלויות</h3>\r\n                        <p class=\"text-gray-600\">עם המוצר שלנו תוכל לחסוך אלפי שקלים בחודש ולהגדיל את הרווחיות שלך</p>\r\n                    </div>\r\n                    <div class=\"p-6\">\r\n                        <div class=\"w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4\">\r\n                            <i class=\"ri-award-line text-3xl text-indigo-600\"></i>\r\n                        </div>\r\n                        <h3 class=\"text-xl font-bold mb-2\">איכות מעולה</h3>\r\n                        <p class=\"text-gray-600\">המוצר שלנו מיוצר בסטנדרטים הגבוהים ביותר ומגיע עם אחריות מלאה לשנתיים</p>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n        \r\n        <section class=\"py-16 bg-gray-50\">\r\n            <div class=\"container mx-auto px-4\">\r\n                <div class=\"md:flex md:items-center\">\r\n                    <div class=\"md:w-1/2 mb-8 md:mb-0\">\r\n                        <img src=\"https://via.placeholder.com/600x400\" alt=\"Product Image\" class=\"rounded-lg shadow-xl\">\r\n                    </div>\r\n                    <div class=\"md:w-1/2 md:mr-8\">\r\n                        <h2 class=\"text-3xl font-bold mb-6\">המוצר שכולם מדברים עליו</h2>\r\n                        <p class=\"text-lg mb-6\">המוצר שלנו תוכנן במיוחד כדי לפתור את הבעיות היומיומיות שלך. עם יותר מ-10,000 לקוחות מרוצים, המוצר הוכיח את עצמו כפתרון האידיאלי.</p>\r\n                        <ul class=\"space-y-4\">\r\n                            <li class=\"flex items-start\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2 mt-1\"></i>\r\n                                <span>פתרון מלא לכל הצרכים שלך</span>\r\n                            </li>\r\n                            <li class=\"flex items-start\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2 mt-1\"></i>\r\n                                <span>קל לשימוש ולא דורש ידע טכני</span>\r\n                            </li>\r\n                            <li class=\"flex items-start\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2 mt-1\"></i>\r\n                                <span>תמיכה טכנית 24/7</span>\r\n                            </li>\r\n                            <li class=\"flex items-start\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2 mt-1\"></i>\r\n                                <span>עדכונים שוטפים ושיפורים</span>\r\n                            </li>\r\n                        </ul>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n        \r\n        <section class=\"py-16 bg-white\">\r\n            <div class=\"container mx-auto px-4 text-center\">\r\n                <h2 class=\"text-3xl font-bold mb-12\">מה אומרים הלקוחות שלנו</h2>\r\n                <div class=\"grid grid-cols-1 md:grid-cols-3 gap-8\">\r\n                    <div class=\"bg-gray-50 p-6 rounded-lg shadow-md\">\r\n                        <div class=\"flex justify-center mb-4\">\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                        </div>\r\n                        <p class=\"text-gray-600 mb-4\">\"המוצר הזה שינה את חיי! אני לא יכול לדמיין את חיי בלעדיו. התוצאות היו מעל ומעבר למצופה.\"</p>\r\n                        <div class=\"flex items-center justify-center\">\r\n                            <img src=\"https://via.placeholder.com/50x50\" alt=\"Customer\" class=\"w-10 h-10 rounded-full ml-3\">\r\n                            <div class=\"text-right\">\r\n                                <h4 class=\"font-bold\">יוסי כהן</h4>\r\n                                <p class=\"text-sm text-gray-500\">ירושלים</p>\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div class=\"bg-gray-50 p-6 rounded-lg shadow-md\">\r\n                        <div class=\"flex justify-center mb-4\">\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                        </div>\r\n                        <p class=\"text-gray-600 mb-4\">\"השירות לקוחות מעולה והמוצר עובד בדיוק כפי שהובטח. אני ממליצה בחום לכל מי שמתלבט.\"</p>\r\n                        <div class=\"flex items-center justify-center\">\r\n                            <img src=\"https://via.placeholder.com/50x50\" alt=\"Customer\" class=\"w-10 h-10 rounded-full ml-3\">\r\n                            <div class=\"text-right\">\r\n                                <h4 class=\"font-bold\">מיכל לוי</h4>\r\n                                <p class=\"text-sm text-gray-500\">תל אביב</p>\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                    <div class=\"bg-gray-50 p-6 rounded-lg shadow-md\">\r\n                        <div class=\"flex justify-center mb-4\">\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                            <i class=\"ri-star-fill text-yellow-400 text-xl\"></i>\r\n                        </div>\r\n                        <p class=\"text-gray-600 mb-4\">\"קניתי את המוצר לפני חצי שנה והוא כבר החזיר את ההשקעה שלי פי כמה. מוצר מעולה עם ערך אדיר.\"</p>\r\n                        <div class=\"flex items-center justify-center\">\r\n                            <img src=\"https://via.placeholder.com/50x50\" alt=\"Customer\" class=\"w-10 h-10 rounded-full ml-3\">\r\n                            <div class=\"text-right\">\r\n                                <h4 class=\"font-bold\">אבי גולדשטיין</h4>\r\n                                <p class=\"text-sm text-gray-500\">חיפה</p>\r\n                            </div>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n        <section id=\"pricing\" class=\"py-16 bg-indigo-600 text-white\">\r\n            <div class=\"container mx-auto px-4 text-center\">\r\n                <h2 class=\"text-3xl font-bold mb-4\">הצטרף עכשיו במחיר מיוחד</h2>\r\n                <p class=\"text-xl mb-12\">מחיר מבצע - לזמן מוגבל בלבד!</p>\r\n                \r\n                <div class=\"max-w-lg mx-auto bg-white text-gray-800 rounded-lg shadow-xl overflow-hidden\">\r\n                    <div class=\"p-8\">\r\n                        <div class=\"flex justify-between items-center\">\r\n                            <div>\r\n                                <h3 class=\"text-2xl font-bold\">חבילה מושלמת</h3>\r\n                                <p class=\"text-gray-500\">כל מה שאתה צריך במחיר אחד</p>\r\n                            </div>\r\n                            <div>\r\n                                <span class=\"text-sm text-gray-500 line-through\">₪599</span>\r\n                                <span class=\"text-3xl font-bold text-indigo-600\">₪399</span>\r\n                            </div>\r\n                        </div>\r\n                        \r\n                        <div class=\"mt-8 space-y-4\">\r\n                            <div class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2\"></i>\r\n                                <span>גישה מלאה לכל התכונות</span>\r\n                            </div>\r\n                            <div class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2\"></i>\r\n                                <span>תמיכה טכנית 24/7</span>\r\n                            </div>\r\n                            <div class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2\"></i>\r\n                                <span>אחריות לשנתיים</span>\r\n                            </div>\r\n                            <div class=\"flex items-center\">\r\n                                <i class=\"ri-check-line text-green-500 text-xl ml-2\"></i>\r\n                                <span>החזר כספי מלא תוך 30 יום</span>\r\n                            </div>\r\n                        </div>\r\n                        \r\n                        <div class=\"mt-8\">\r\n                            <form action=\"/submit-form\" method=\"post\" class=\"space-y-4\">\r\n                                <div>\r\n                                    <label for=\"name\" class=\"block text-sm font-medium text-gray-700 mb-1\">שם מלא</label>\r\n                                    <input type=\"text\" id=\"name\" name=\"name\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" required>\r\n                                </div>\r\n                                <div>\r\n                                    <label for=\"email\" class=\"block text-sm font-medium text-gray-700 mb-1\">אימייל</label>\r\n                                    <input type=\"email\" id=\"email\" name=\"email\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" required>\r\n                                </div>\r\n                                <div>\r\n                                    <label for=\"phone\" class=\"block text-sm font-medium text-gray-700 mb-1\">טלפון</label>\r\n                                    <input type=\"tel\" id=\"phone\" name=\"phone\" class=\"w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500\" required>\r\n                                </div>\r\n                                <div>\r\n                                    <button type=\"submit\" class=\"w-full bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 font-bold text-lg\">\r\n                                        הזמן עכשיו - מבצע מוגבל בזמן\r\n                                    </button>\r\n                                </div>\r\n                                <p class=\"text-xs text-gray-500 text-center mt-2\">המבצע בתוקף עד למלאי. לא כולל דמי משלוח. תקנון באתר.</p>\r\n                            </form>\r\n                        </div>\r\n                    </div>\r\n                </div>\r\n            </div>\r\n        </section>\r\n    </main>\r\n    \r\n    <footer class=\"bg-gray-800 text-white py-8\">\r\n        <div class=\"container mx-auto px-4 text-center\">\r\n            <p>&copy; 2023 החברה שלך. כל הזכויות שמורות.</p>\r\n            <div class=\"mt-4\">\r\n                <a href=\"#\" class=\"text-gray-400 hover:text-white mx-2\">תקנון</a>\r\n                <a href=\"#\" class=\"text-gray-400 hover:text-white mx-2\">מדיניות פרטיות</a>\r\n                <a href=\"#\" class=\"text-gray-400 hover:text-white mx-2\">צור קשר</a>\r\n            </div>\r\n        </div>\r\n    </footer>\r\n</body>\r\n</html>', 1, '2025-04-20 09:18:57');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `company` varchar(255) DEFAULT NULL,
  `plan_id` int(11) DEFAULT '1',
  `trial_ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `timezone` varchar(100) DEFAULT 'Asia/Jerusalem',
  `language` varchar(10) DEFAULT 'he'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- הוצאת מידע עבור טבלה `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `company`, `plan_id`, `trial_ends_at`, `created_at`, `updated_at`, `timezone`, `language`) VALUES
(1, 'itadmit@gmail.com', '$2y$10$UCXgu76DHkTre7S.SfW7KO.zTzyzUlkDY3hklnhyaYtVyZgPfJoqO', 'יוגב אביטן', 'Tadmit interactive', 1, '2025-04-27 08:54:36', '2025-04-20 08:54:37', '2025-04-21 16:53:23', 'Asia/Jerusalem', 'he');

-- --------------------------------------------------------

--
-- מבנה טבלה עבור טבלה `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- אינדקסים לטבלה `automations`
--
ALTER TABLE `automations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- אינדקסים לטבלה `automation_logs`
--
ALTER TABLE `automation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `automation_id` (`automation_id`),
  ADD KEY `subscriber_id` (`subscriber_id`),
  ADD KEY `created_at` (`created_at`);

--
-- אינדקסים לטבלה `automation_steps`
--
ALTER TABLE `automation_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `automation_id` (`automation_id`),
  ADD KEY `step_order` (`step_order`);

--
-- אינדקסים לטבלה `automation_subscribers`
--
ALTER TABLE `automation_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `automation_subscriber` (`automation_id`,`subscriber_id`),
  ADD KEY `subscriber_id` (`subscriber_id`),
  ADD KEY `next_action_at` (`next_action_at`),
  ADD KEY `status` (`status`);

--
-- אינדקסים לטבלה `campaigns`
--
ALTER TABLE `campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- אינדקסים לטבלה `campaign_stats`
--
ALTER TABLE `campaign_stats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `subscriber_id` (`subscriber_id`);

--
-- אינדקסים לטבלה `contact_lists`
--
ALTER TABLE `contact_lists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- אינדקסים לטבלה `form_fields`
--
ALTER TABLE `form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `landing_page_id` (`landing_page_id`);

--
-- אינדקסים לטבלה `landing_pages`
--
ALTER TABLE `landing_pages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- אינדקסים לטבלה `landing_page_forms`
--
ALTER TABLE `landing_page_forms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `landing_page_id` (`landing_page_id`);

--
-- אינדקסים לטבלה `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- אינדקסים לטבלה `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `landing_page_id` (`landing_page_id`);

--
-- אינדקסים לטבלה `subscriber_lists`
--
ALTER TABLE `subscriber_lists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subscriber_list` (`subscriber_id`,`list_id`),
  ADD KEY `list_id` (`list_id`);

--
-- אינדקסים לטבלה `templates`
--
ALTER TABLE `templates`
  ADD PRIMARY KEY (`id`);

--
-- אינדקסים לטבלה `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- אינדקסים לטבלה `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_setting_unique` (`user_id`,`setting_key`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `automations`
--
ALTER TABLE `automations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `automation_logs`
--
ALTER TABLE `automation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `automation_steps`
--
ALTER TABLE `automation_steps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `automation_subscribers`
--
ALTER TABLE `automation_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaigns`
--
ALTER TABLE `campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `campaign_stats`
--
ALTER TABLE `campaign_stats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_lists`
--
ALTER TABLE `contact_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `form_fields`
--
ALTER TABLE `form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `landing_pages`
--
ALTER TABLE `landing_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `landing_page_forms`
--
ALTER TABLE `landing_page_forms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `subscriber_lists`
--
ALTER TABLE `subscriber_lists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `templates`
--
ALTER TABLE `templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- הגבלות לטבלאות שהוצאו
--

--
-- הגבלות לטבלה `automations`
--
ALTER TABLE `automations`
  ADD CONSTRAINT `fk_automations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `automation_logs`
--
ALTER TABLE `automation_logs`
  ADD CONSTRAINT `fk_automation_logs_automation` FOREIGN KEY (`automation_id`) REFERENCES `automations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_automation_logs_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `automation_steps`
--
ALTER TABLE `automation_steps`
  ADD CONSTRAINT `fk_automation_steps_automation` FOREIGN KEY (`automation_id`) REFERENCES `automations` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `automation_subscribers`
--
ALTER TABLE `automation_subscribers`
  ADD CONSTRAINT `fk_automation_subscribers_automation` FOREIGN KEY (`automation_id`) REFERENCES `automations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_automation_subscribers_subscriber` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `campaigns`
--
ALTER TABLE `campaigns`
  ADD CONSTRAINT `campaigns_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `campaign_stats`
--
ALTER TABLE `campaign_stats`
  ADD CONSTRAINT `campaign_stats_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campaign_stats_ibfk_2` FOREIGN KEY (`subscriber_id`) REFERENCES `subscribers` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `landing_pages`
--
ALTER TABLE `landing_pages`
  ADD CONSTRAINT `landing_pages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `landing_page_forms`
--
ALTER TABLE `landing_page_forms`
  ADD CONSTRAINT `landing_page_forms_ibfk_1` FOREIGN KEY (`landing_page_id`) REFERENCES `landing_pages` (`id`) ON DELETE CASCADE;

--
-- הגבלות לטבלה `subscribers`
--
ALTER TABLE `subscribers`
  ADD CONSTRAINT `subscribers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscribers_ibfk_2` FOREIGN KEY (`landing_page_id`) REFERENCES `landing_pages` (`id`) ON DELETE SET NULL;

--
-- הגבלות לטבלה `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `fk_user_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
