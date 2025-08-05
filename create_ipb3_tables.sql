-- Создание таблиц IPB 3 для импорта данных
-- База данных для форума CloudMasters

-- Таблица категорий форума IPB 3 (cldforums)
CREATE TABLE IF NOT EXISTS `cldforums` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL DEFAULT '',
  `description` text,
  `parent_id` int DEFAULT -1,
  `position` int UNSIGNED DEFAULT 0,
  `topics` int DEFAULT 0,
  `posts` int DEFAULT 0,
  `last_post` int DEFAULT NULL,
  `last_poster_name` varchar(255) DEFAULT '',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица тем IPB 3 (cldtopics)
CREATE TABLE IF NOT EXISTS `cldtopics` (
  `tid` int NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `forum_id` int NOT NULL DEFAULT 0,
  `starter_id` int NOT NULL DEFAULT 0,
  `starter_name` varchar(255) DEFAULT NULL,
  `posts` int DEFAULT 0,
  `views` int DEFAULT 0,
  `pinned` tinyint(1) DEFAULT 0,
  `locked` tinyint(1) DEFAULT 0,
  `approved` tinyint(1) DEFAULT 1,
  `start_date` int DEFAULT NULL,
  `last_post` int DEFAULT NULL,
  `last_poster_id` int DEFAULT NULL,
  `last_poster_name` varchar(255) DEFAULT NULL,
  `created_at` int DEFAULT NULL,
  `updated_at` int DEFAULT NULL,
  PRIMARY KEY (`tid`),
  KEY `forum_id` (`forum_id`),
  KEY `starter_id` (`starter_id`),
  KEY `start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица постов IPB 3 (cldposts)
CREATE TABLE IF NOT EXISTS `cldposts` (
  `pid` int NOT NULL AUTO_INCREMENT,
  `topic_id` int NOT NULL DEFAULT 0,
  `author_id` int NOT NULL DEFAULT 0,
  `author_name` varchar(255) DEFAULT NULL,
  `post` mediumtext,
  `ip_address` varchar(46) DEFAULT '',
  `queued` tinyint(1) DEFAULT 0,
  `new_topic` tinyint(1) DEFAULT 0,
  `post_date` int DEFAULT NULL,
  `created_at` int DEFAULT NULL,
  `updated_at` int DEFAULT NULL,
  PRIMARY KEY (`pid`),
  KEY `topic_id` (`topic_id`),
  KEY `author_id` (`author_id`),
  KEY `post_date` (`post_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Индексы для оптимизации
CREATE INDEX idx_cldtopics_forum_start ON cldtopics(forum_id, start_date);
CREATE INDEX idx_cldposts_topic_date ON cldposts(topic_id, post_date);
CREATE INDEX idx_cldforums_parent_pos ON cldforums(parent_id, position); 