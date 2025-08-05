const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function addIPB3TestData() {
  let connection;
  
  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connected to database');

    // Добавляем тестовые категории IPB 3
    console.log('Adding IPB 3 test categories...');
    
    const testCategories = [
      {
        id: 1,
        name: 'Общие обсуждения',
        description: 'Общие темы и обсуждения',
        parent_id: -1,
        position: 1,
        topics: 2,
        posts: 4,
        last_post: Math.floor(Date.now() / 1000),
        last_poster_name: 'Admin'
      },
      {
        id: 2,
        name: 'Техническая поддержка',
        description: 'Вопросы по работе сайта и технические проблемы',
        parent_id: -1,
        position: 2,
        topics: 1,
        posts: 2,
        last_post: Math.floor(Date.now() / 1000),
        last_poster_name: 'Admin'
      },
      {
        id: 3,
        name: 'Новости и анонсы',
        description: 'Новости проекта и важные объявления',
        parent_id: -1,
        position: 3,
        topics: 1,
        posts: 1,
        last_post: Math.floor(Date.now() / 1000),
        last_poster_name: 'Admin'
      },
      {
        id: 4,
        name: 'Магистериум',
        description: 'Обсуждения проекта Магистериум',
        parent_id: -1,
        position: 4,
        topics: 0,
        posts: 0,
        last_post: null,
        last_poster_name: ''
      },
      {
        id: 5,
        name: 'Практики и медитации',
        description: 'Обмен опытом по практикам',
        parent_id: -1,
        position: 5,
        topics: 1,
        posts: 1,
        last_post: Math.floor(Date.now() / 1000),
        last_poster_name: 'Admin'
      }
    ];

    for (const category of testCategories) {
      await connection.execute(`
        INSERT INTO cldforums (id, name, description, parent_id, position, topics, posts, last_post, last_poster_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          name = VALUES(name),
          description = VALUES(description),
          parent_id = VALUES(parent_id),
          position = VALUES(position),
          topics = VALUES(topics),
          posts = VALUES(posts),
          last_post = VALUES(last_post),
          last_poster_name = VALUES(last_poster_name)
      `, [
        category.id,
        category.name,
        category.description,
        category.parent_id,
        category.position,
        category.topics,
        category.posts,
        category.last_post,
        category.last_poster_name
      ]);
    }
    console.log(`Added ${testCategories.length} IPB 3 categories`);

    // Добавляем тестовые темы IPB 3
    console.log('Adding IPB 3 test topics...');
    
    const testTopics = [
      {
        tid: 1,
        title: 'Добро пожаловать на форум CloudMasters!',
        forum_id: 1,
        starter_id: 1,
        starter_name: 'Admin',
        posts: 3,
        views: 15,
        pinned: 1,
        approved: 1,
        start_date: Math.floor(Date.now() / 1000),
        last_post: Math.floor(Date.now() / 1000),
        last_poster_id: 2,
        last_poster_name: 'User1'
      },
      {
        tid: 2,
        title: 'Как работает система регистрации?',
        forum_id: 2,
        starter_id: 1,
        starter_name: 'Admin',
        posts: 2,
        views: 8,
        pinned: 0,
        approved: 1,
        start_date: Math.floor(Date.now() / 1000),
        last_post: Math.floor(Date.now() / 1000),
        last_poster_id: 2,
        last_poster_name: 'User1'
      },
      {
        tid: 3,
        title: 'Новые возможности форума',
        forum_id: 3,
        starter_id: 1,
        starter_name: 'Admin',
        posts: 1,
        views: 5,
        pinned: 0,
        approved: 1,
        start_date: Math.floor(Date.now() / 1000),
        last_post: Math.floor(Date.now() / 1000),
        last_poster_id: 1,
        last_poster_name: 'Admin'
      },
      {
        tid: 4,
        title: 'Практики медитации',
        forum_id: 5,
        starter_id: 1,
        starter_name: 'Admin',
        posts: 1,
        views: 3,
        pinned: 0,
        approved: 1,
        start_date: Math.floor(Date.now() / 1000),
        last_post: Math.floor(Date.now() / 1000),
        last_poster_id: 1,
        last_poster_name: 'Admin'
      }
    ];

    for (const topic of testTopics) {
      await connection.execute(`
        INSERT INTO cldtopics (tid, title, forum_id, starter_id, starter_name, posts, views, pinned, approved, start_date, last_post, last_poster_id, last_poster_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          title = VALUES(title),
          forum_id = VALUES(forum_id),
          starter_id = VALUES(starter_id),
          starter_name = VALUES(starter_name),
          posts = VALUES(posts),
          views = VALUES(views),
          pinned = VALUES(pinned),
          approved = VALUES(approved),
          start_date = VALUES(start_date),
          last_post = VALUES(last_post),
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
    console.log(`Added ${testTopics.length} IPB 3 topics`);

    // Добавляем тестовые посты IPB 3
    console.log('Adding IPB 3 test posts...');
    
    const testPosts = [
      {
        pid: 1,
        topic_id: 1,
        author_id: 1,
        author_name: 'Admin',
        post: 'Добро пожаловать на наш форум! Здесь вы можете обсуждать различные темы, задавать вопросы и делиться опытом.',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 1,
        post_date: Math.floor(Date.now() / 1000)
      },
      {
        pid: 2,
        topic_id: 1,
        author_id: 2,
        author_name: 'User1',
        post: 'Спасибо за приветствие! Очень рад быть здесь.',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 0,
        post_date: Math.floor(Date.now() / 1000)
      },
      {
        pid: 3,
        topic_id: 1,
        author_id: 3,
        author_name: 'User2',
        post: 'Отличный форум! Много полезной информации.',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 0,
        post_date: Math.floor(Date.now() / 1000)
      },
      {
        pid: 4,
        topic_id: 2,
        author_id: 1,
        author_name: 'Admin',
        post: 'Система регистрации позволяет пользователям создавать аккаунты и участвовать в обсуждениях на форуме.',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 1,
        post_date: Math.floor(Date.now() / 1000)
      },
      {
        pid: 5,
        topic_id: 2,
        author_id: 2,
        author_name: 'User1',
        post: 'Регистрация работает отлично, спасибо!',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 0,
        post_date: Math.floor(Date.now() / 1000)
      },
      {
        pid: 6,
        topic_id: 3,
        author_id: 1,
        author_name: 'Admin',
        post: 'Мы добавили новые возможности для форума, включая поддержку IPB 3 и современный интерфейс.',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 1,
        post_date: Math.floor(Date.now() / 1000)
      },
      {
        pid: 7,
        topic_id: 4,
        author_id: 1,
        author_name: 'Admin',
        post: 'Обсуждение различных практик медитации и их эффективности.',
        ip_address: '127.0.0.1',
        queued: 0,
        new_topic: 1,
        post_date: Math.floor(Date.now() / 1000)
      }
    ];

    for (const post of testPosts) {
      await connection.execute(`
        INSERT INTO cldposts (pid, topic_id, author_id, author_name, post, ip_address, queued, new_topic, post_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          topic_id = VALUES(topic_id),
          author_id = VALUES(author_id),
          author_name = VALUES(author_name),
          post = VALUES(post),
          ip_address = VALUES(ip_address),
          queued = VALUES(queued),
          new_topic = VALUES(new_topic),
          post_date = VALUES(post_date)
      `, [
        post.pid,
        post.topic_id,
        post.author_id,
        post.author_name,
        post.post,
        post.ip_address,
        post.queued,
        post.new_topic,
        post.post_date
      ]);
    }
    console.log(`Added ${testPosts.length} IPB 3 posts`);

    console.log('IPB 3 test data added successfully!');

  } catch (error) {
    console.error('Error adding IPB 3 test data:', error);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database connection closed');
    }
  }
}

addIPB3TestData(); 