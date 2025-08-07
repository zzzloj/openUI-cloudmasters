const mysql = require('mysql2/promise');

async function checkTables() {
  const connection = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'Admin2024@',
    database: 'cloudmasters'
  });

  try {
    console.log('Подключение к базе данных установлено');
    
    // Проверяем все таблицы
    const [tables] = await connection.execute("SHOW TABLES");
    console.log('Все таблицы в базе данных:');
    tables.forEach(table => {
      console.log('-', Object.values(table)[0]);
    });
    
    // Проверяем таблицы с похожими именами
    const [membersTables] = await connection.execute("SHOW TABLES LIKE '%member%'");
    console.log('\nТаблицы с "member" в названии:');
    membersTables.forEach(table => {
      console.log('-', Object.values(table)[0]);
    });
    
    const [userTables] = await connection.execute("SHOW TABLES LIKE '%user%'");
    console.log('\nТаблицы с "user" в названии:');
    userTables.forEach(table => {
      console.log('-', Object.values(table)[0]);
    });
    
  } catch (error) {
    console.error('Ошибка:', error);
  } finally {
    await connection.end();
  }
}

checkTables();
