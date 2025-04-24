CREATE TABLE IF NOT EXISTS `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `landing_page_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(512) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `file_size` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `landing_page_id` (`landing_page_id`),
  CONSTRAINT `media_landing_page_id_fk` FOREIGN KEY (`landing_page_id`) REFERENCES `landing_pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `media_user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 