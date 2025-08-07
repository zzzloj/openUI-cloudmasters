const crypto = require('crypto');

// Данные пользователя Oleg_B
const expectedHash = '21ebe693a078adf2a72d3b10e2a70582';
const salt = 'Qc2eW';

// Проверим конкретный пароль
const password = 'GbaDMc8DXG5azEg';
const hashedPassword = crypto.createHash('md5').update(password + salt).digest('hex');
const matches = hashedPassword === expectedHash;

console.log('Проверяем пароль для пользователя Oleg_B:');
console.log('Email: oy.bogatyrev@gmail.com');
console.log('Хеш в БД:', expectedHash);
console.log('Соль:', salt);
console.log('');
console.log(`Пароль: "${password}"`);
console.log(`Хеш: ${hashedPassword}`);
console.log(`Совпадает: ${matches ? '✅' : '❌'}`);

if (matches) {
  console.log(`🎉 ПАРОЛЬ ПРАВИЛЬНЫЙ!`);
} else {
  console.log(`❌ Пароль неверный`);
}

console.log('');
console.log('Проверка завершена.');
