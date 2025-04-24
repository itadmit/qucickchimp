-- הסרת הטבלה אם היא קיימת
DROP TABLE IF EXISTS `email_templates`;

-- יצירת טבלת תבניות מייל
CREATE TABLE `email_templates` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT 'מזהה המשתמש שיצר את התבנית',
    `campaign_id` INT(11) NULL DEFAULT NULL COMMENT 'מזהה הקמפיין המקושר (אם יש)',
    `name` VARCHAR(255) NOT NULL COMMENT 'שם התבנית',
    `html` LONGTEXT NOT NULL COMMENT 'קוד HTML של התבנית',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'תאריך יצירה',
    `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'תאריך עדכון',
    PRIMARY KEY (`id`),
    UNIQUE KEY `name_user` (`name`, `user_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_campaign_id` (`campaign_id`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='טבלת תבניות מייל';

-- הכנסת תבנית ברירת מחדל לדוגמה (עם user_id=1 כברירת מחדל)
INSERT INTO `email_templates` (`user_id`, `name`, `html`, `created_at`) VALUES
(1, 'תבנית ברירת מחדל', '<!DOCTYPE html><html dir="rtl" lang="he"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Hebrew:wght@100..900&display=swap" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet"><style>body{font-family:\'Noto Sans Hebrew\',Arial,sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;padding:20px;}section{margin-bottom:20px;}img{max-width:100%;height:auto;}</style></head><body><section><h2 style="font-family: \'Noto Sans Hebrew\', sans-serif; font-size: 24px; font-weight: bold; color: #333; text-align: center;">ברוכים הבאים!</h2></section></body></html>', NOW());

-- הצגת הטבלה החדשה
DESCRIBE `email_templates`; 