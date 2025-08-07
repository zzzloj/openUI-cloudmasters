const crypto = require('crypto');

// Данные пользователя Oleg_B
const password = 'GbaDMc8DXG5azEg';
const salt = 'Qc2eW';
const expectedHash = '21ebe693a078adf2a72d3b10e2a70582';

console.log('=== Тестирование кастомных алгоритмов IPB 3.4 ===');
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

// Вариант 1: IPB может использовать соль как байты
const saltBytes = Buffer.from(salt, 'utf8');
const hash1 = crypto.createHash('md5').update(processedPassword + saltBytes).digest('hex');
console.log('1. MD5(password + salt_bytes):', hash1, hash1 === expectedHash ? '✅' : '❌');

// Вариант 2: IPB может использовать соль как байты в обратном порядке
const saltBytesReverse = Buffer.from(salt, 'utf8').reverse();
const hash2 = crypto.createHash('md5').update(processedPassword + saltBytesReverse).digest('hex');
console.log('2. MD5(password + salt_bytes_reverse):', hash2, hash2 === expectedHash ? '✅' : '❌');

// Вариант 3: IPB может использовать соль как байты в начале
const hash3 = crypto.createHash('md5').update(saltBytes + processedPassword).digest('hex');
console.log('3. MD5(salt_bytes + password):', hash3, hash3 === expectedHash ? '✅' : '❌');

// Вариант 4: IPB может использовать соль как байты в обратном порядке в начале
const hash4 = crypto.createHash('md5').update(saltBytesReverse + processedPassword).digest('hex');
console.log('4. MD5(salt_bytes_reverse + password):', hash4, hash4 === expectedHash ? '✅' : '❌');

// Вариант 5: IPB может использовать соль как байты в середине
const mid = Math.floor(processedPassword.length / 2);
const hash5 = crypto.createHash('md5').update(
    processedPassword.substring(0, mid) + saltBytes + processedPassword.substring(mid)
).digest('hex');
console.log('5. MD5(password_half + salt_bytes + password_half):', hash5, hash5 === expectedHash ? '✅' : '❌');

// Вариант 6: IPB может использовать соль как байты в обратном порядке в середине
const hash6 = crypto.createHash('md5').update(
    processedPassword.substring(0, mid) + saltBytesReverse + processedPassword.substring(mid)
).digest('hex');
console.log('6. MD5(password_half + salt_bytes_reverse + password_half):', hash6, hash6 === expectedHash ? '✅' : '❌');

// Вариант 7: IPB может использовать соль как байты в конце
const hash7 = crypto.createHash('md5').update(processedPassword + saltBytes).digest('hex');
console.log('7. MD5(password + salt_bytes):', hash7, hash7 === expectedHash ? '✅' : '❌');

// Вариант 8: IPB может использовать соль как байты в обратном порядке в конце
const hash8 = crypto.createHash('md5').update(processedPassword + saltBytesReverse).digest('hex');
console.log('8. MD5(password + salt_bytes_reverse):', hash8, hash8 === expectedHash ? '✅' : '❌');

// Вариант 9: IPB может использовать соль как байты в начале и конце
const hash9 = crypto.createHash('md5').update(saltBytes + processedPassword + saltBytes).digest('hex');
console.log('9. MD5(salt_bytes + password + salt_bytes):', hash9, hash9 === expectedHash ? '✅' : '❌');

// Вариант 10: IPB может использовать соль как байты в обратном порядке в начале и конце
const hash10 = crypto.createHash('md5').update(saltBytesReverse + processedPassword + saltBytesReverse).digest('hex');
console.log('10. MD5(salt_bytes_reverse + password + salt_bytes_reverse):', hash10, hash10 === expectedHash ? '✅' : '❌');

// Вариант 11: IPB может использовать соль как байты в начале и обратном порядке в конце
const hash11 = crypto.createHash('md5').update(saltBytes + processedPassword + saltBytesReverse).digest('hex');
console.log('11. MD5(salt_bytes + password + salt_bytes_reverse):', hash11, hash11 === expectedHash ? '✅' : '❌');

// Вариант 12: IPB может использовать соль как байты в обратном порядке в начале и обычном порядке в конце
const hash12 = crypto.createHash('md5').update(saltBytesReverse + processedPassword + saltBytes).digest('hex');
console.log('12. MD5(salt_bytes_reverse + password + salt_bytes):', hash12, hash12 === expectedHash ? '✅' : '❌');

console.log('');
console.log('Проверка завершена.');

