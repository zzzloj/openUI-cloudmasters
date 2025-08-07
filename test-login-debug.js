const crypto = require('crypto');

// Данные пользователя Oleg_B
const password = 'GbaDMc8DXG5azEg';
const salt = 'Qc2eW';
const expectedHash = '21ebe693a078adf2a72d3b10e2a70582';

console.log('=== Детальная отладка авторизации ===');
console.log('Пароль:', password);
console.log('Соль:', salt);
console.log('Ожидаемый хеш:', expectedHash);
console.log('');

// Функция для обработки HTML entities как в IPB 3.4
function processPassword(plainPassword) {
    let processedPassword = plainPassword;
    
    // HTML entities replacement как в IPB 3.4
    const htmlEntities = ["&#33;", "&#036;", "&#092;"];
    const replacementChar = ["!", "$", "\\"];
    
    for (let i = 0; i < htmlEntities.length; i++) {
        processedPassword = processedPassword.replace(new RegExp(htmlEntities[i], 'g'), replacementChar[i]);
    }
    
    return processedPassword;
}

// Обрабатываем пароль как в IPB 3.4
const processedPassword = processPassword(password);
console.log('Обработанный пароль:', processedPassword);

// Тестируем различные варианты хеширования
const variants = [
    { name: 'MD5(password + salt)', hash: crypto.createHash('md5').update(processedPassword + salt).digest('hex') },
    { name: 'MD5(salt + password)', hash: crypto.createHash('md5').update(salt + processedPassword).digest('hex') },
    { name: 'MD5(password)', hash: crypto.createHash('md5').update(processedPassword).digest('hex') },
    { name: 'SHA1(password + salt)', hash: crypto.createHash('sha1').update(processedPassword + salt).digest('hex') },
    { name: 'SHA1(salt + password)', hash: crypto.createHash('sha1').update(salt + processedPassword).digest('hex') },
    { name: 'SHA1(password)', hash: crypto.createHash('sha1').update(processedPassword).digest('hex') },
    { name: 'MD5(MD5(password + salt))', hash: crypto.createHash('md5').update(crypto.createHash('md5').update(processedPassword + salt).digest('hex')).digest('hex') },
    { name: 'SHA1(MD5(password + salt))', hash: crypto.createHash('sha1').update(crypto.createHash('md5').update(processedPassword + salt).digest('hex')).digest('hex') },
    { name: 'MD5(SHA1(password + salt))', hash: crypto.createHash('md5').update(crypto.createHash('sha1').update(processedPassword + salt).digest('hex')).digest('hex') },
];

console.log('Результаты тестирования:');
variants.forEach((variant, index) => {
    const matches = variant.hash === expectedHash;
    console.log(`${index + 1}. ${variant.name}:`);
    console.log(`   Хеш: ${variant.hash}`);
    console.log(`   Совпадает: ${matches ? '✅' : '❌'}`);
    console.log('');
});

// Проверим, может ли быть проблема в кодировке
console.log('=== Проверка кодировки ===');
console.log('Пароль в UTF-8:', Buffer.from(password, 'utf8').toString('hex'));
console.log('Соль в UTF-8:', Buffer.from(salt, 'utf8').toString('hex'));
console.log('Пароль + соль в UTF-8:', Buffer.from(password + salt, 'utf8').toString('hex'));

// Проверим, может ли быть проблема в регистре
console.log('');
console.log('=== Проверка регистра ===');
const lowerPassword = password.toLowerCase();
const upperPassword = password.toUpperCase();
console.log('Пароль в нижнем регистре:', lowerPassword);
console.log('MD5(lower + salt):', crypto.createHash('md5').update(lowerPassword + salt).digest('hex'));
console.log('Пароль в верхнем регистре:', upperPassword);
console.log('MD5(upper + salt):', crypto.createHash('md5').update(upperPassword + salt).digest('hex'));

console.log('');
console.log('=== Проверка завершена ===');

