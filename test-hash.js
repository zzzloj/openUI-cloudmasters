const crypto = require('crypto');

const password = 'testpass123';
const salt = 'fzbq5';
const expectedHash = '91ecf110fff8147bb91cd138309d8c9f';

console.log('Тестирование хеширования пароля:');
console.log('Пароль:', password);
console.log('Соль:', salt);
console.log('Ожидаемый хеш:', expectedHash);

const hashedPassword = crypto.createHash('md5').update(password + salt).digest('hex');
console.log('Вычисленный хеш:', hashedPassword);
console.log('Хеши совпадают:', hashedPassword === expectedHash);

// Проверим также другие варианты
console.log('\nПроверка других вариантов:');
console.log('password + salt:', crypto.createHash('md5').update(password + salt).digest('hex'));
console.log('salt + password:', crypto.createHash('md5').update(salt + password).digest('hex'));
console.log('password:', crypto.createHash('md5').update(password).digest('hex'));

