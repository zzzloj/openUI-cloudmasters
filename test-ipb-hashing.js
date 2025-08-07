const crypto = require('crypto');

// Данные пользователя Oleg_B
const expectedHash = '21ebe693a078adf2a72d3b10e2a70582';
const salt = 'Qc2eW';
const password = 'GbaDMc8DXG5azEg';

console.log('Тестирование алгоритмов хеширования IPB 3.4:');
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

// Вариант 1: MD5(password + salt) - стандартный
const hash1 = crypto.createHash('md5').update(processedPassword + salt).digest('hex');
console.log('1. MD5(password + salt):', hash1, hash1 === expectedHash ? '✅' : '❌');

// Вариант 2: MD5(salt + password) - обратный порядок
const hash2 = crypto.createHash('md5').update(salt + processedPassword).digest('hex');
console.log('2. MD5(salt + password):', hash2, hash2 === expectedHash ? '✅' : '❌');

// Вариант 3: MD5(password) - без соли
const hash3 = crypto.createHash('md5').update(processedPassword).digest('hex');
console.log('3. MD5(password):', hash3, hash3 === expectedHash ? '✅' : '❌');

// Вариант 4: SHA1(password + salt)
const hash4 = crypto.createHash('sha1').update(processedPassword + salt).digest('hex');
console.log('4. SHA1(password + salt):', hash4, hash4 === expectedHash ? '✅' : '❌');

// Вариант 5: MD5 с двойным хешированием
const hash5 = crypto.createHash('md5').update(
  crypto.createHash('md5').update(processedPassword + salt).digest('hex')
).digest('hex');
console.log('5. MD5(MD5(password + salt)):', hash5, hash5 === expectedHash ? '✅' : '❌');

// Вариант 6: IPB 3.4 может использовать другой формат соли
// Попробуем соль как hex
const saltHex = Buffer.from(salt, 'utf8').toString('hex');
const hash6 = crypto.createHash('md5').update(processedPassword + saltHex).digest('hex');
console.log('6. MD5(password + salt_hex):', hash6, hash6 === expectedHash ? '✅' : '❌');

// Вариант 7: IPB 3.4 может использовать base64 соли
const saltBase64 = Buffer.from(salt, 'utf8').toString('base64');
const hash7 = crypto.createHash('md5').update(processedPassword + saltBase64).digest('hex');
console.log('7. MD5(password + salt_base64):', hash7, hash7 === expectedHash ? '✅' : '❌');

// Вариант 8: IPB 3.4 может использовать другой алгоритм
const hash8 = crypto.createHash('sha256').update(processedPassword + salt).digest('hex');
console.log('8. SHA256(password + salt):', hash8, hash8 === expectedHash ? '✅' : '❌');

// Вариант 9: IPB 3.4 может использовать соль в середине
const hash9 = crypto.createHash('md5').update(processedPassword.substring(0, Math.floor(processedPassword.length/2)) + salt + processedPassword.substring(Math.floor(processedPassword.length/2))).digest('hex');
console.log('9. MD5(password_half + salt + password_half):', hash9, hash9 === expectedHash ? '✅' : '❌');

// Вариант 10: IPB 3.4 может использовать соль как префикс и суффикс
const hash10 = crypto.createHash('md5').update(salt + processedPassword + salt).digest('hex');
console.log('10. MD5(salt + password + salt):', hash10, hash10 === expectedHash ? '✅' : '❌');

// Вариант 11: IPB 3.4 может использовать соль как XOR
const passwordBuffer = Buffer.from(processedPassword, 'utf8');
const saltBuffer = Buffer.from(salt, 'utf8');
const xorBuffer = Buffer.alloc(Math.max(passwordBuffer.length, saltBuffer.length));
for (let i = 0; i < xorBuffer.length; i++) {
    xorBuffer[i] = (passwordBuffer[i] || 0) ^ (saltBuffer[i % saltBuffer.length] || 0);
}
const hash11 = crypto.createHash('md5').update(xorBuffer).digest('hex');
console.log('11. MD5(password XOR salt):', hash11, hash11 === expectedHash ? '✅' : '❌');

// Вариант 12: IPB 3.4 может использовать соль как ключ для HMAC
const hash12 = crypto.createHmac('md5', salt).update(processedPassword).digest('hex');
console.log('12. HMAC-MD5(password, salt):', hash12, hash12 === expectedHash ? '✅' : '❌');

console.log('');
console.log('Проверка завершена.');
