-- Скрипт миграции данных из IPB 3 в форум CloudMasters
-- Выполнять после создания таблиц форума

-- 1. Миграция категорий форума (cldforums -> forum_categories)
INSERT INTO forum_categories (
  id, name, description, parent_id, position, 
  topics_count, posts_count, last_post_date, last_poster_name
)
SELECT 
  id,
  name,
  description,
  CASE 
    WHEN parent_id = -1 THEN NULL 
    ELSE parent_id 
  END,
  position,
  topics,
  posts,
  last_post,
  last_poster_name
FROM cldforums 
WHERE id > 0;

-- 2. Миграция тем (cldtopics -> forum_topics)
INSERT INTO forum_topics (
  id, title, forum_id, author_id, author_name,
  posts_count, views_count, is_pinned, is_approved,
  created_at, last_post_date, last_poster_id, last_poster_name
)
SELECT 
  tid,
  title,
  forum_id,
  starter_id,
  starter_name,
  posts,
  views,
  pinned,
  approved,
  start_date,
  last_post,
  last_poster_id,
  last_poster_name
FROM cldtopics 
WHERE tid > 0;

-- 3. Миграция постов (cldposts -> forum_posts)
INSERT INTO forum_posts (
  id, topic_id, author_id, author_name, content,
  ip_address, is_approved, is_first_post, created_at
)
SELECT 
  pid,
  topic_id,
  author_id,
  author_name,
  post,
  ip_address,
  queued = 0, -- 0 = approved, 1 = queued
  new_topic = 1,
  post_date
FROM cldposts 
WHERE pid > 0;

-- 4. Обновление счетчиков в категориях
UPDATE forum_categories fc
SET 
  topics_count = (
    SELECT COUNT(*) 
    FROM forum_topics ft 
    WHERE ft.forum_id = fc.id
  ),
  posts_count = (
    SELECT COUNT(*) 
    FROM forum_posts fp 
    JOIN forum_topics ft ON fp.topic_id = ft.id 
    WHERE ft.forum_id = fc.id
  );

-- 5. Обновление последних постов в категориях
UPDATE forum_categories fc
SET 
  last_post_id = (
    SELECT fp.id 
    FROM forum_posts fp 
    JOIN forum_topics ft ON fp.topic_id = ft.id 
    WHERE ft.forum_id = fc.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  ),
  last_post_date = (
    SELECT fp.created_at 
    FROM forum_posts fp 
    JOIN forum_topics ft ON fp.topic_id = ft.id 
    WHERE ft.forum_id = fc.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  ),
  last_poster_id = (
    SELECT fp.author_id 
    FROM forum_posts fp 
    JOIN forum_topics ft ON fp.topic_id = ft.id 
    WHERE ft.forum_id = fc.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  ),
  last_poster_name = (
    SELECT fp.author_name 
    FROM forum_posts fp 
    JOIN forum_topics ft ON fp.topic_id = ft.id 
    WHERE ft.forum_id = fc.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  );

-- 6. Обновление счетчиков в темах
UPDATE forum_topics ft
SET 
  posts_count = (
    SELECT COUNT(*) 
    FROM forum_posts fp 
    WHERE fp.topic_id = ft.id
  );

-- 7. Обновление последних постов в темах
UPDATE forum_topics ft
SET 
  last_post_id = (
    SELECT id 
    FROM forum_posts fp 
    WHERE fp.topic_id = ft.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  ),
  last_post_date = (
    SELECT created_at 
    FROM forum_posts fp 
    WHERE fp.topic_id = ft.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  ),
  last_poster_id = (
    SELECT author_id 
    FROM forum_posts fp 
    WHERE fp.topic_id = ft.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  ),
  last_poster_name = (
    SELECT author_name 
    FROM forum_posts fp 
    WHERE fp.topic_id = ft.id 
    ORDER BY fp.created_at DESC 
    LIMIT 1
  );

-- 8. Обновление первого поста в темах
UPDATE forum_topics ft
SET 
  first_post_id = (
    SELECT id 
    FROM forum_posts fp 
    WHERE fp.topic_id = ft.id 
    ORDER BY fp.created_at ASC 
    LIMIT 1
  );

-- 9. Конвертация HTML контента (если нужно)
UPDATE forum_posts 
SET content_html = content 
WHERE content_html IS NULL; 