const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function importIPB3Data() {
  let connection;
  
  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connected to database');

    // Проверяем, есть ли данные IPB 3
    const [ipbForums] = await connection.execute('SELECT COUNT(*) as count FROM cldforums');
    const [ipbTopics] = await connection.execute('SELECT COUNT(*) as count FROM cldtopics');
    const [ipbPosts] = await connection.execute('SELECT COUNT(*) as count FROM cldposts');

    console.log(`Found IPB 3 data:`);
    console.log(`- Forums: ${ipbForums[0].count}`);
    console.log(`- Topics: ${ipbTopics[0].count}`);
    console.log(`- Posts: ${ipbPosts[0].count}`);

    if (ipbForums[0].count === 0) {
      console.log('No IPB 3 data found. Skipping import.');
      return;
    }

    // 1. Импорт категорий форума
    console.log('\n1. Importing forum categories...');
    const [forums] = await connection.execute(`
      SELECT id, name, description, parent_id, position, topics, posts, last_post, last_poster_name
      FROM cldforums WHERE id > 0
    `);

    for (const forum of forums) {
      await connection.execute(`
        INSERT INTO forum_categories (
          id, name, description, parent_id, position, 
          topics_count, posts_count, last_post_date, last_poster_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          name = VALUES(name),
          description = VALUES(description),
          parent_id = VALUES(parent_id),
          position = VALUES(position),
          topics_count = VALUES(topics_count),
          posts_count = VALUES(posts_count),
          last_post_date = VALUES(last_post_date),
          last_poster_name = VALUES(last_poster_name)
      `, [
        forum.id,
        forum.name,
        forum.description,
        forum.parent_id === -1 ? null : forum.parent_id,
        forum.position,
        forum.topics,
        forum.posts,
        forum.last_post,
        forum.last_poster_name
      ]);
    }
    console.log(`Imported ${forums.length} categories`);

    // 2. Импорт тем
    console.log('\n2. Importing topics...');
    const [topics] = await connection.execute(`
      SELECT tid, title, forum_id, starter_id, starter_name, posts, views, pinned, approved,
             start_date, last_post, last_poster_id, last_poster_name
      FROM cldtopics WHERE tid > 0
    `);

    for (const topic of topics) {
      await connection.execute(`
        INSERT INTO forum_topics (
          id, title, forum_id, author_id, author_name,
          posts_count, views_count, is_pinned, is_approved,
          created_at, last_post_date, last_poster_id, last_poster_name
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          title = VALUES(title),
          forum_id = VALUES(forum_id),
          author_id = VALUES(author_id),
          author_name = VALUES(author_name),
          posts_count = VALUES(posts_count),
          views_count = VALUES(views_count),
          is_pinned = VALUES(is_pinned),
          is_approved = VALUES(is_approved),
          created_at = VALUES(created_at),
          last_post_date = VALUES(last_post_date),
          last_poster_id = VALUES(last_poster_id),
          last_poster_name = VALUES(last_poster_name)
      `, [
        topic.tid,
        topic.title,
        topic.forum_id,
        topic.starter_id,
        topic.starter_name,
        topic.posts,
        topic.views,
        topic.pinned,
        topic.approved,
        topic.start_date,
        topic.last_post,
        topic.last_poster_id,
        topic.last_poster_name
      ]);
    }
    console.log(`Imported ${topics.length} topics`);

    // 3. Импорт постов
    console.log('\n3. Importing posts...');
    const [posts] = await connection.execute(`
      SELECT pid, topic_id, author_id, author_name, post, ip_address, queued, new_topic, post_date
      FROM cldposts WHERE pid > 0
    `);

    for (const post of posts) {
      await connection.execute(`
        INSERT INTO forum_posts (
          id, topic_id, author_id, author_name, content,
          ip_address, is_approved, is_first_post, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          topic_id = VALUES(topic_id),
          author_id = VALUES(author_id),
          author_name = VALUES(author_name),
          content = VALUES(content),
          ip_address = VALUES(ip_address),
          is_approved = VALUES(is_approved),
          is_first_post = VALUES(is_first_post),
          created_at = VALUES(created_at)
      `, [
        post.pid,
        post.topic_id,
        post.author_id,
        post.author_name,
        post.post,
        post.ip_address,
        post.queued === 0, // 0 = approved, 1 = queued
        post.new_topic === 1,
        post.post_date
      ]);
    }
    console.log(`Imported ${posts.length} posts`);

    // 4. Обновление счетчиков
    console.log('\n4. Updating counters...');
    
    // Обновляем счетчики в категориях
    await connection.execute(`
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
        )
    `);

    // Обновляем счетчики в темах
    await connection.execute(`
      UPDATE forum_topics ft
      SET posts_count = (
        SELECT COUNT(*) 
        FROM forum_posts fp 
        WHERE fp.topic_id = ft.id
      )
    `);

    // Обновляем последние посты в категориях
    await connection.execute(`
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
        )
    `);

    // Обновляем последние посты в темах
    await connection.execute(`
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
        )
    `);

    // Обновляем первый пост в темах
    await connection.execute(`
      UPDATE forum_topics ft
      SET first_post_id = (
        SELECT id 
        FROM forum_posts fp 
        WHERE fp.topic_id = ft.id 
        ORDER BY fp.created_at ASC 
        LIMIT 1
      )
    `);

    console.log('Import completed successfully!');

  } catch (error) {
    console.error('Error importing IPB 3 data:', error);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database connection closed');
    }
  }
}

importIPB3Data(); 