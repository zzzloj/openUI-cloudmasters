const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function createForumTables() {
  let connection;
  
  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connected to database');

    // SQL для создания таблиц форума
    const createTablesSQL = `
      -- Таблица категорий форума
      CREATE TABLE IF NOT EXISTS \`forum_categories\` (
        \`id\` int NOT NULL AUTO_INCREMENT,
        \`name\` varchar(128) NOT NULL DEFAULT '',
        \`description\` text,
        \`parent_id\` int DEFAULT -1,
        \`position\` int UNSIGNED DEFAULT 0,
        \`topics_count\` int DEFAULT 0,
        \`posts_count\` int DEFAULT 0,
        \`last_post_id\` int DEFAULT NULL,
        \`last_post_date\` int DEFAULT NULL,
        \`last_poster_id\` int DEFAULT NULL,
        \`last_poster_name\` varchar(255) DEFAULT '',
        \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
        \`updated_at\` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (\`id\`),
        KEY \`parent_id\` (\`parent_id\`),
        KEY \`position\` (\`position\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

      -- Таблица тем
      CREATE TABLE IF NOT EXISTS \`forum_topics\` (
        \`id\` int NOT NULL AUTO_INCREMENT,
        \`title\` varchar(250) NOT NULL DEFAULT '',
        \`forum_id\` int NOT NULL DEFAULT 0,
        \`author_id\` int NOT NULL DEFAULT 0,
        \`author_name\` varchar(255) DEFAULT NULL,
        \`posts_count\` int DEFAULT 0,
        \`views_count\` int DEFAULT 0,
        \`is_pinned\` tinyint(1) DEFAULT 0,
        \`is_locked\` tinyint(1) DEFAULT 0,
        \`is_approved\` tinyint(1) DEFAULT 1,
        \`first_post_id\` int DEFAULT NULL,
        \`last_post_id\` int DEFAULT NULL,
        \`last_post_date\` int DEFAULT NULL,
        \`last_poster_id\` int DEFAULT NULL,
        \`last_poster_name\` varchar(255) DEFAULT NULL,
        \`created_at\` int DEFAULT NULL,
        \`updated_at\` int DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`forum_id\` (\`forum_id\`),
        KEY \`author_id\` (\`author_id\`),
        KEY \`created_at\` (\`created_at\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

      -- Таблица постов
      CREATE TABLE IF NOT EXISTS \`forum_posts\` (
        \`id\` int NOT NULL AUTO_INCREMENT,
        \`topic_id\` int NOT NULL DEFAULT 0,
        \`author_id\` int NOT NULL DEFAULT 0,
        \`author_name\` varchar(255) DEFAULT NULL,
        \`content\` mediumtext,
        \`content_html\` mediumtext,
        \`ip_address\` varchar(46) DEFAULT '',
        \`is_approved\` tinyint(1) DEFAULT 1,
        \`is_first_post\` tinyint(1) DEFAULT 0,
        \`created_at\` int DEFAULT NULL,
        \`updated_at\` int DEFAULT NULL,
        PRIMARY KEY (\`id\`),
        KEY \`topic_id\` (\`topic_id\`),
        KEY \`author_id\` (\`author_id\`),
        KEY \`created_at\` (\`created_at\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

      -- Таблица прикрепленных файлов
      CREATE TABLE IF NOT EXISTS \`forum_attachments\` (
        \`id\` int NOT NULL AUTO_INCREMENT,
        \`post_id\` int NOT NULL DEFAULT 0,
        \`filename\` varchar(255) NOT NULL DEFAULT '',
        \`file_path\` varchar(500) NOT NULL DEFAULT '',
        \`file_size\` int DEFAULT 0,
        \`mime_type\` varchar(100) DEFAULT '',
        \`downloads\` int DEFAULT 0,
        \`created_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (\`id\`),
        KEY \`post_id\` (\`post_id\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

      -- Таблица просмотров тем
      CREATE TABLE IF NOT EXISTS \`forum_topic_views\` (
        \`topic_id\` int NOT NULL,
        \`user_id\` int NOT NULL,
        \`viewed_at\` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (\`topic_id\`, \`user_id\`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    `;

    // Выполняем SQL запросы
    const statements = createTablesSQL.split(';').filter(stmt => stmt.trim());
    
    for (const statement of statements) {
      if (statement.trim()) {
        await connection.execute(statement);
        console.log('Executed:', statement.substring(0, 50) + '...');
      }
    }

    // Создаем индексы
    const indexesSQL = `
      CREATE INDEX idx_forum_topics_forum_created ON forum_topics(forum_id, created_at);
      CREATE INDEX idx_forum_posts_topic_created ON forum_posts(topic_id, created_at);
      CREATE INDEX idx_forum_categories_parent_position ON forum_categories(parent_id, position);
    `;

    const indexStatements = indexesSQL.split(';').filter(stmt => stmt.trim());
    
    for (const statement of indexStatements) {
      if (statement.trim()) {
        await connection.execute(statement);
        console.log('Created index:', statement.substring(0, 50) + '...');
      }
    }

    console.log('Forum tables created successfully!');

  } catch (error) {
    console.error('Error creating forum tables:', error);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database connection closed');
    }
  }
}

createForumTables(); 