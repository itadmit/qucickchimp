-- עדכון סכמת בסיס הנתונים להוספת תמיכה ב-Mailgun

-- יצירת טבלת דומיינים למשתמשים
CREATE TABLE IF NOT EXISTS `user_domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_domain` (`user_id`, `domain`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_domains_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- עדכון המודול setting_key בטבלת user_settings להוספת הגדרות Mailgun
ALTER TABLE `user_settings` 
MODIFY COLUMN `setting_key` enum(
  'smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_security', 
  'smtp_username', 'smtp_password', 'sender_name', 'sender_email',
  'mail_service', 'mailgun_api_key', 'mailgun_domain', 'mailgun_from_name', 'mailgun_from_email'
) NOT NULL;

-- הוספת אינדקס להגדרות משתמש לשירות דואר
CREATE INDEX IF NOT EXISTS `idx_user_mail_settings` ON `user_settings` (`user_id`, `setting_key`); 