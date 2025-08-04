const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function addTestData() {
  let connection;
  
  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connected to database');

    // Добавляем тестовые темы
    console.log('Adding test topics...');
    
    const testTopics = [
      {
        title: 'Добро пожаловать на форум CloudMasters!',
        forum_id: 1, // Общие обсуждения
        author_id: 1,
        author_name: 'Admin',
        content: 'Добро пожаловать на наш форум! Здесь вы можете обсуждать различные темы, задавать вопросы и делиться опытом.'
      },
      {
        title: 'Как работает система регистрации?',
        forum_id: 2, // Техническая поддержка
        author_id: 1,
        author_name: 'Admin',
        content: 'Система регистрации позволяет пользователям создавать аккаунты и участвовать в обсуждениях на форуме.'
      },
      {
        title: 'Новые возможности форума',
        forum_id: 3, // Новости и анонсы
        author_id: 1,
        author_name: 'Admin',
        content: 'Мы добавили новые возможности для форума, включая поддержку IPB 3 и современный интерфейс.'
      },
      {
        title: 'Практики медитации',
        forum_id: 5, // Практики и медитации
        author_id: 1,
        author_name: 'Admin',
        content: 'Обсуждение различных практик медитации и их эффективности.'
      }
    ];

    for (const topic of testTopics) {
      // Создаем тему
      const topicResult = await connection.execute(`
        INSERT INTO forum_topics (title, forum_id, author_id, author_name, created_at)
        VALUES (?, ?, ?, ?, ?)
      `, [topic.title, topic.forum_id, topic.author_id, topic.author_name, Math.floor(Date.now() / 1000)]);

      const topicId = topicResult[0].insertId;

      // Создаем первый пост
      await connection.execute(`
        INSERT INTO forum_posts (topic_id, author_id, author_name, content, is_first_post, created_at)
        VALUES (?, ?, ?, ?, 1, ?)
      `, [topicId, topic.author_id, topic.author_name, topic.content, Math.floor(Date.now() / 1000)]);

      console.log(`Created topic: ${topic.title}`);
    }

    // Добавляем несколько ответов
    console.log('Adding test replies...');
    
    const testReplies = [
      {
        topic_id: 1,
        author_id: 2,
        author_name: 'User1',
        content: 'Спасибо за приветствие! Очень рад быть здесь.'
      },
      {
        topic_id: 1,
        author_id: 3,
        author_name: 'User2',
        content: 'Отличный форум! Много полезной информации.'
      },
      {
        topic_id: 2,
        author_id: 2,
        author_name: 'User1',
        content: 'Регистрация работает отлично, спасибо!'
      }
    ];

    for (const reply of testReplies) {
      await connection.execute(`
        INSERT INTO forum_posts (topic_id, author_id, author_name, content, created_at)
        VALUES (?, ?, ?, ?, ?)
      `, [reply.topic_id, reply.author_id, reply.author_name, reply.content, Math.floor(Date.now() / 1000)]);

      console.log(`Added reply to topic ${reply.topic_id}`);
    }

    // Обновляем счетчики
    console.log('Updating counters...');
    
    // Обновляем счетчики в темах
    await connection.execute(`
      UPDATE forum_topics ft
      SET posts_count = (
        SELECT COUNT(*) 
        FROM forum_posts fp 
        WHERE fp.topic_id = ft.id
      )
    `);

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

    // Обновляем последние посты
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

    console.log('Test data added successfully!');

  } catch (error) {
    console.error('Error adding test data:', error);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database connection closed');
    }
  }
}

addTestData(); 