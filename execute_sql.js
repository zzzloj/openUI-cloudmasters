const mysql = require('mysql2/promise');
const fs = require('fs');

async function executeSQL() {
  const connection = await mysql.createConnection({
    host: 'localhost',
    user: 'root',
    password: 'Admin2024@',
    database: 'cloudmasters'
  });

  try {
    console.log('Подключение к базе данных установлено');
    
    // Читаем SQL файл
    const sqlContent = fs.readFileSync('create_admin_logs_table.sql', 'utf8');
    
    // Разделяем на отдельные запросы
    const queries = sqlContent.split(';').filter(query => query.trim());
    
    for (const query of queries) {
      if (query.trim()) {
        console.log('Выполняем запрос:', query.trim());
        await connection.execute(query.trim());
        console.log('Запрос выполнен успешно');
      }
    }
    
    console.log('Все запросы выполнены успешно');
    
    // Проверяем, что таблица создана
    const [tables] = await connection.execute("SHOW TABLES LIKE 'admin_logs'");
    if (tables.length > 0) {
      console.log('Таблица admin_logs создана успешно');
      
      // Проверяем содержимое
      const [logs] = await connection.execute("SELECT * FROM admin_logs LIMIT 5");
      console.log('Тестовые записи:', logs);
    } else {
      console.log('Таблица admin_logs не найдена');
    }
    
  } catch (error) {
    console.error('Ошибка:', error);
  } finally {
    await connection.end();
  }
}

executeSQL();
