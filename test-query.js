const { query } = require('./src/lib/database');

async function testQuery() {
  console.log('Тестирование функции query...');
  
  try {
    // Тест 1: Простой запрос
    console.log('\nТест 1: Подсчет пользователей');
    const countResult = await query('SELECT COUNT(*) as count FROM cldmembers');
    console.log('Результат:', countResult);
    
    // Тест 2: Поиск пользователя
    console.log('\nТест 2: Поиск тестового пользователя');
    const userResult = await query('SELECT * FROM cldmembers WHERE email = ?', ['test@example.com']);
    console.log('Результат:', userResult);
    
    if (userResult.length > 0) {
      console.log('Пользователь найден:', userResult[0].name);
    } else {
      console.log('Пользователь не найден');
    }
    
    // Тест 3: Проверка пароля
    console.log('\nТест 3: Проверка пароля');
    const crypto = require('crypto');
    const user = userResult[0];
    if (user) {
      const salt = user.members_pass_salt;
      const password = 'testpass123';
      const hashedPassword = crypto.createHash('md5').update(password + salt).digest('hex');
      console.log('Соль:', salt);
      console.log('Хеш в БД:', user.members_pass_hash);
      console.log('Вычисленный хеш:', hashedPassword);
      console.log('Совпадают:', hashedPassword === user.members_pass_hash);
    }
    
  } catch (error) {
    console.error('Ошибка:', error);
  }
}

testQuery(); 