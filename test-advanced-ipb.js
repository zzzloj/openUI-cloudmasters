const crypto = require('crypto');

// Данные пользователя Oleg_B
const password = 'GbaDMc8DXG5azEg';
const salt = 'Qc2eW';
const expectedHash = '21ebe693a078adf2a72d3b10e2a70582';

console.log('=== Тестирование сложных алгоритмов IPB 3.4 ===');
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

const processedPassword = processPassword(password);

// Вариант 1: IPB может использовать соль как ключ для HMAC
const hash1 = crypto.createHmac('md5', salt).update(processedPassword).digest('hex');
console.log('1. HMAC-MD5(password, salt):', hash1, hash1 === expectedHash ? '✅' : '❌');

// Вариант 2: IPB может использовать соль как ключ для HMAC с SHA1
const hash2 = crypto.createHmac('sha1', salt).update(processedPassword).digest('hex');
console.log('2. HMAC-SHA1(password, salt):', hash2, hash2 === expectedHash ? '✅' : '❌');

// Вариант 3: IPB может использовать двойное хеширование с HMAC
const hash3 = crypto.createHmac('md5', salt).update(
    crypto.createHmac('md5', salt).update(processedPassword).digest('hex')
).digest('hex');
console.log('3. HMAC-MD5(HMAC-MD5(password, salt), salt):', hash3, hash3 === expectedHash ? '✅' : '❌');

// Вариант 4: IPB может использовать соль как ключ для PBKDF2
const hash4 = crypto.pbkdf2Sync(processedPassword, salt, 1000, 16, 'md5').toString('hex');
console.log('4. PBKDF2(password, salt, 1000):', hash4, hash4 === expectedHash ? '✅' : '❌');

// Вариант 5: IPB может использовать соль как ключ для PBKDF2 с SHA1
const hash5 = crypto.pbkdf2Sync(processedPassword, salt, 1000, 16, 'sha1').toString('hex');
console.log('5. PBKDF2-SHA1(password, salt, 1000):', hash5, hash5 === expectedHash ? '✅' : '❌');

// Вариант 6: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций
const hash6 = crypto.pbkdf2Sync(processedPassword, salt, 10000, 16, 'md5').toString('hex');
console.log('6. PBKDF2(password, salt, 10000):', hash6, hash6 === expectedHash ? '✅' : '❌');

// Вариант 7: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций и SHA1
const hash7 = crypto.pbkdf2Sync(processedPassword, salt, 10000, 16, 'sha1').toString('hex');
console.log('7. PBKDF2-SHA1(password, salt, 10000):', hash7, hash7 === expectedHash ? '✅' : '❌');

// Вариант 8: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций и SHA256
const hash8 = crypto.pbkdf2Sync(processedPassword, salt, 1000, 16, 'sha256').toString('hex');
console.log('8. PBKDF2-SHA256(password, salt, 1000):', hash8, hash8 === expectedHash ? '✅' : '❌');

// Вариант 9: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций и SHA256
const hash9 = crypto.pbkdf2Sync(processedPassword, salt, 10000, 16, 'sha256').toString('hex');
console.log('9. PBKDF2-SHA256(password, salt, 10000):', hash9, hash9 === expectedHash ? '✅' : '❌');

// Вариант 10: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций и SHA256
const hash10 = crypto.pbkdf2Sync(processedPassword, salt, 100000, 16, 'sha256').toString('hex');
console.log('10. PBKDF2-SHA256(password, salt, 100000):', hash10, hash10 === expectedHash ? '✅' : '❌');

// Вариант 11: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций и SHA256
const hash11 = crypto.pbkdf2Sync(processedPassword, salt, 1000000, 16, 'sha256').toString('hex');
console.log('11. PBKDF2-SHA256(password, salt, 1000000):', hash11, hash11 === expectedHash ? '✅' : '❌');

// Вариант 12: IPB может использовать соль как ключ для PBKDF2 с большим количеством итераций и SHA256
const hash12 = crypto.pbkdf2Sync(processedPassword, salt, 10000000, 16, 'sha256').toString('hex');
console.log('12. PBKDF2-SHA256(password, salt, 10000000):', hash12, hash12 === expectedHash ? '✅' : '❌');

console.log('');
console.log('Проверка завершена.');

