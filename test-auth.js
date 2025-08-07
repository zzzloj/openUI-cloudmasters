const crypto = require('crypto');

// Функция для хеширования пароля в стиле IPB
function hashPassword(password, salt) {
  return crypto.createHash('md5').update(password + salt).digest('hex');
}

// Тестовые данные пользователей из базы
const testUsers = [
  {
    name: 'phoenix',
    email: 'antorlov@mail.ru',
    salt: '9Z+KT',
    hash: '285cc12355adaf0e1bb6cde03e22d85b'
  },
  {
    name: 'Ora',
    email: 'ora-h@yandex.ru',
    salt: '-p#AU',
    hash: 'e23c47cbdd07a1e85c4d2c212a3bcde2'
  },
  {
    name: 'Wierd',
    email: 'grannadda@gmail.com',
    salt: ')22&h',
    hash: '260f2dc644ebc5bb9d44b0faa5820212'
  }
];

// Тестируем разные пароли
const testPasswords = [
  'password',
  '123456',
  'admin',
  'user',
  'test',
  'qwerty',
  '12345',
  'password123',
  'admin123',
  'user123'
];

console.log('Тестирование авторизации для импортированных пользователей IPB\n');

testUsers.forEach((user, index) => {
  console.log(`Пользователь ${index + 1}: ${user.name} (${user.email})`);
  console.log(`Соль: ${user.salt}`);
  console.log(`Хеш: ${user.hash}`);
  console.log('Тестируем пароли:');
  
  testPasswords.forEach(password => {
    const testHash = hashPassword(password, user.salt);
    const isMatch = testHash === user.hash;
    console.log(`  "${password}" -> ${testHash} ${isMatch ? '✓' : '✗'}`);
  });
  
  console.log('');
});

// Попробуем угадать пароль для первого пользователя
console.log('Попробуем найти пароль для phoenix:');
const commonPasswords = [
  'phoenix',
  'antorlov',
  'mail',
  'ru',
  'admin',
  'user',
  'password',
  '123456',
  'qwerty',
  'test',
  'demo',
  'guest',
  'login',
  'pass',
  'secret',
  'private',
  'personal',
  'account',
  'profile',
  'member'
];

commonPasswords.forEach(password => {
  const testHash = hashPassword(password, '9Z+KT');
  const isMatch = testHash === '285cc12355adaf0e1bb6cde03e22d85b';
  if (isMatch) {
    console.log(`✓ Найден пароль для phoenix: "${password}"`);
  }
}); 