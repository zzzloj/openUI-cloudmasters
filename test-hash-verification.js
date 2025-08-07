const mysql = require('mysql2/promise');
const crypto = require('crypto');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function testHashVerification() {
  console.log('Тестирование хеширования паролей...\n');
  
  try {
    const connection = await mysql.createConnection(dbConfig);
    console.log('✓ Подключение к БД установлено');
    
    // Получаем тестового пользователя
    const [users] = await connection.execute(`
      SELECT member_id, name, email, members_pass_hash, members_pass_salt 
      FROM cldmembers WHERE email = ?
    `, ['test@example.com']);
    
    if (users.length === 0) {
      console.log('❌ Тестовый пользователь не найден');
      return;
    }
    
    const user = users[0];
    console.log('✓ Тестовый пользователь найден:');
    console.log(`  ID: ${user.member_id}`);
    console.log(`  Имя: ${user.name}`);
    console.log(`  Email: ${user.email}`);
    console.log(`  Хеш в БД: ${user.members_pass_hash}`);
    console.log(`  Соль: ${user.members_pass_salt}`);
    
    // Тестируем разные пароли
    const testPasswords = [
      'testpass123',
      'password123',
      'test',
      '123456',
      'admin'
    ];
    
    console.log('\n🔍 Тестирование хеширования:');
    
    for (const password of testPasswords) {
      const hashedPassword = crypto.createHash('md5').update(password + user.members_pass_salt).digest('hex');
      const matches = hashedPassword === user.members_pass_hash;
      
      console.log(`\nПароль: "${password}"`);
      console.log(`  Вычисленный хеш: ${hashedPassword}`);
      console.log(`  Совпадает: ${matches ? '✓' : '✗'}`);
      
      if (matches) {
        console.log('🎉 Найден правильный пароль!');
        break;
      }
    }
    
    // Тестируем с импортированным пользователем
    console.log('\n🔍 Тестирование импортированного пользователя:');
    const [importedUsers] = await connection.execute(`
      SELECT member_id, name, email, members_pass_hash, members_pass_salt 
      FROM cldmembers WHERE email = ?
    `, ['antorlov@mail.ru']);
    
    if (importedUsers.length > 0) {
      const importedUser = importedUsers[0];
      console.log(`\nИмпортированный пользователь: ${importedUser.name}`);
      console.log(`Хеш: ${importedUser.members_pass_hash}`);
      console.log(`Соль: ${importedUser.members_pass_salt}`);
      
      for (const password of testPasswords) {
        const hashedPassword = crypto.createHash('md5').update(password + importedUser.members_pass_salt).digest('hex');
        const matches = hashedPassword === importedUser.members_pass_hash;
        
        console.log(`\nПароль: "${password}"`);
        console.log(`  Вычисленный хеш: ${hashedPassword}`);
        console.log(`  Совпадает: ${matches ? '✓' : '✗'}`);
        
        if (matches) {
          console.log('🎉 Найден правильный пароль!');
          break;
        }
      }
    }
    
    await connection.end();
    console.log('\n✓ Соединение закрыто');
    
  } catch (error) {
    console.error('❌ Ошибка:', error.message);
  }
}

testHashVerification();



