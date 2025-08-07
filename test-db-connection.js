const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function testConnection() {
  console.log('Тестирование подключения к базе данных...');
  
  try {
    const connection = await mysql.createConnection(dbConfig);
    console.log('✓ Подключение к БД успешно');
    
    // Проверяем таблицу cldmembers
    const [members] = await connection.execute('SELECT COUNT(*) as count FROM cldmembers');
    console.log('Количество пользователей в cldmembers:', members[0].count);
    
    // Проверяем тестового пользователя
    const [testUser] = await connection.execute(
      'SELECT member_id, name, email, members_pass_hash, members_pass_salt FROM cldmembers WHERE email = ?',
      ['test@example.com']
    );
    
    if (testUser.length > 0) {
      console.log('✓ Тестовый пользователь найден:');
      console.log('  ID:', testUser[0].member_id);
      console.log('  Имя:', testUser[0].name);
      console.log('  Email:', testUser[0].email);
      console.log('  Хеш:', testUser[0].members_pass_hash);
      console.log('  Соль:', testUser[0].members_pass_salt);
    } else {
      console.log('✗ Тестовый пользователь не найден');
    }
    
    // Проверяем импортированного пользователя
    const [importedUser] = await connection.execute(
      'SELECT member_id, name, email, members_pass_hash, members_pass_salt FROM cldmembers WHERE email = ?',
      ['antorlov@mail.ru']
    );
    
    if (importedUser.length > 0) {
      console.log('✓ Импортированный пользователь найден:');
      console.log('  ID:', importedUser[0].member_id);
      console.log('  Имя:', importedUser[0].name);
      console.log('  Email:', importedUser[0].email);
      console.log('  Хеш:', importedUser[0].members_pass_hash);
      console.log('  Соль:', importedUser[0].members_pass_salt);
    } else {
      console.log('✗ Импортированный пользователь не найден');
    }
    
    await connection.end();
    console.log('✓ Соединение закрыто');
    
  } catch (error) {
    console.error('✗ Ошибка подключения к БД:', error.message);
  }
}

testConnection(); 