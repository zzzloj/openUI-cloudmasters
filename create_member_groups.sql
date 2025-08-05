-- Создание таблицы member_groups
CREATE TABLE IF NOT EXISTS `member_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `permissions` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка базовых групп пользователей
INSERT INTO `member_groups` (`id`, `name`, `description`, `permissions`) VALUES
(1, 'Гости', 'Неавторизованные пользователи', '{"view_forum": true}'),
(2, 'VIP Пользователи', 'Привилегированные пользователи', '{"view_forum": true, "create_topics": true, "reply_posts": true, "edit_own_posts": true}'),
(3, 'Модераторы', 'Модераторы форума', '{"view_forum": true, "create_topics": true, "reply_posts": true, "edit_own_posts": true, "moderate_posts": true, "delete_posts": true, "pin_topics": true, "lock_topics": true}'),
(4, 'Администраторы', 'Администраторы сайта', '{"view_forum": true, "create_topics": true, "reply_posts": true, "edit_own_posts": true, "moderate_posts": true, "delete_posts": true, "pin_topics": true, "lock_topics": true, "manage_users": true, "manage_forum": true, "admin_panel": true}'); 