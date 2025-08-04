const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function addTestCategories() {
  let connection;
  
  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connected to database');

    // Добавляем тестовые категории
    const categories = [
      {
        name: 'Общие обсуждения',
        description: 'Общие темы и обсуждения',
        parent_id: null,
        position: 1
      },
      {
        name: 'Техническая поддержка',
        description: 'Вопросы по работе сайта и технические проблемы',
        parent_id: null,
        position: 2
      },
      {
        name: 'Новости и анонсы',
        description: 'Новости проекта и важные объявления',
        parent_id: null,
        position: 3
      },
      {
        name: 'Магистериум',
        description: 'Обсуждения проекта Магистериум',
        parent_id: null,
        position: 4
      },
      {
        name: 'Практики и медитации',
        description: 'Обмен опытом по практикам',
        parent_id: null,
        position: 5
      }
    ];

    for (const category of categories) {
      await connection.execute(`
        INSERT INTO forum_categories (name, description, parent_id, position)
        VALUES (?, ?, ?, ?)
      `, [category.name, category.description, category.parent_id, category.position]);
      
      console.log(`Added category: ${category.name}`);
    }

    console.log('Test categories added successfully!');

  } catch (error) {
    console.error('Error adding test categories:', error);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database connection closed');
    }
  }
}

addTestCategories(); 