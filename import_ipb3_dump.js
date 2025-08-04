const mysql = require('mysql2/promise');
const fs = require('fs');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function importIPB3Dump() {
  let connection;
  
  try {
    connection = await mysql.createConnection(dbConfig);
    console.log('Connected to database');

    // Читаем дамп IPB 3
    console.log('Reading IPB 3 dump...');
    const dumpContent = fs.readFileSync('ipb3_dump.sql', 'utf8');
    
    // Разбиваем на отдельные SQL запросы
    const statements = dumpContent
      .split(';')
      .map(stmt => stmt.trim())
      .filter(stmt => stmt.length > 0 && !stmt.startsWith('--'));

    console.log(`Found ${statements.length} SQL statements`);

    // Выполняем SQL запросы
    let executedCount = 0;
    for (const statement of statements) {
      try {
        if (statement.trim()) {
          await connection.execute(statement);
          executedCount++;
          
          if (executedCount % 100 === 0) {
            console.log(`Executed ${executedCount} statements...`);
          }
        }
      } catch (error) {
        // Игнорируем ошибки создания таблиц, если они уже существуют
        if (!error.message.includes('already exists')) {
          console.error('Error executing statement:', error.message);
        }
      }
    }

    console.log(`Import completed! Executed ${executedCount} statements`);

  } catch (error) {
    console.error('Error importing IPB 3 dump:', error);
  } finally {
    if (connection) {
      await connection.end();
      console.log('Database connection closed');
    }
  }
}

importIPB3Dump(); 