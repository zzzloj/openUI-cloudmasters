const mysql = require('mysql2/promise');
const crypto = require('crypto');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function testAuthDirect() {
  console.log('Тестирование авторизации напрямую...');
  
  const connection = await mysql.createConnection(dbConfig);
  
  try {
    // Тест 1: Поиск тестового пользователя
    console.log('\nТест 1: Поиск тестового пользователя');
    const [users] = await connection.execute(
      'SELECT * FROM cldmembers WHERE email = ?',
      ['test@example.com']
    );
    
    console.log('Найдено пользователей:', users.length);
    
    if (users.length > 0) {
      const user = users[0];
      console.log('Пользователь:', user.name);
      console.log('Email:', user.email);
      console.log('Соль:', user.members_pass_salt);
      console.log('Хеш:', user.members_pass_hash);
      
      // Тест 2: Проверка пароля
      console.log('\nТест 2: Проверка пароля');
      const password = 'testpass123';
      const salt = user.members_pass_salt;
      const hashedPassword = crypto.createHash('md5').update(password + salt).digest('hex');
      
      console.log('Введенный пароль:', password);
      console.log('Соль:', salt);
      console.log('Вычисленный хеш:', hashedPassword);
      console.log('Хеш в БД:', user.members_pass_hash);
      console.log('Совпадают:', hashedPassword === user.members_pass_hash);
      
      if (hashedPassword === user.members_pass_hash) {
        console.log('✓ Пароль верный!');
        
        // Тест 3: Обновление активности
        console.log('\nТест 3: Обновление активности');
        const now = Math.floor(Date.now() / 1000);
        await connection.execute(
          'UPDATE cldmembers SET last_activity = ?, last_visit = ? WHERE member_id = ?',
          [now, now, user.member_id]
        );
        console.log('✓ Активность обновлена');
        
        // Тест 4: Генерация токена
        console.log('\nТест 4: Генерация токена');
        const jwt = require('jsonwebtoken');
        const token = jwt.sign(
          { 
            id: user.member_id, 
            email: user.email, 
            display_name: user.members_display_name,
            group_id: user.member_group_id 
          },
          'cloudmasters-secret-key-2024',
          { expiresIn: '7d' }
        );
        console.log('✓ Токен сгенерирован:', token.substring(0, 20) + '...');
        
        return {
          success: true,
          user: user,
          token: token
        };
      } else {
        console.log('✗ Пароль неверный');
        return { success: false, error: 'Неверный пароль' };
      }
    } else {
      console.log('✗ Пользователь не найден');
      return { success: false, error: 'Пользователь не найден' };
    }
    
  } catch (error) {
    console.error('Ошибка:', error);
    return { success: false, error: 'Ошибка авторизации' };
  } finally {
    await connection.end();
  }
}

testAuthDirect().then(result => {
  console.log('\nИтоговый результат:', result);
}); 