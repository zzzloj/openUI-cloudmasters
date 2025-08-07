const crypto = require('crypto');

// Новые данные для расшифровки
const expectedHash = '29a31eda064feed822db8795775192ab';
const salt = '"(0+q';

console.log('=== Расшифровка хеша ===');
console.log('Хеш:', expectedHash);
console.log('Соль:', salt);
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

// Попробуем простые пароли
const testPasswords = [
    'password', '123456', 'admin', 'test', 'user', 'oleg', 'oleg_b', 'olegb', 
    'oleg123', 'oleg_b123', 'password123', 'admin123', 'test123', 'user123',
    'qwerty', '123456789', 'abc123', 'password1', 'admin1', 'test1',
    'GbaDMc8DXG5azEg', // оригинальный пароль
    'password', 'admin', 'test', 'user', 'oleg', 'oleg_b', 'olegb',
    'oleg123', 'oleg_b123', 'password123', 'admin123', 'test123', 'user123',
    'qwerty', '123456789', 'abc123', 'password1', 'admin1', 'test1'
];

console.log('Тестирование простых паролей:');
testPasswords.forEach((password, index) => {
    const processedPassword = processPassword(password);
    
    // Вариант 1: MD5(password + salt)
    const hash1 = crypto.createHash('md5').update(processedPassword + salt).digest('hex');
    if (hash1 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (MD5(password + salt))`);
        return;
    }
    
    // Вариант 2: MD5(salt + password)
    const hash2 = crypto.createHash('md5').update(salt + processedPassword).digest('hex');
    if (hash2 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (MD5(salt + password))`);
        return;
    }
    
    // Вариант 3: MD5(password)
    const hash3 = crypto.createHash('md5').update(processedPassword).digest('hex');
    if (hash3 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (MD5(password))`);
        return;
    }
    
    // Вариант 4: SHA1(password + salt)
    const hash4 = crypto.createHash('sha1').update(processedPassword + salt).digest('hex');
    if (hash4 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (SHA1(password + salt))`);
        return;
    }
    
    // Вариант 5: SHA1(salt + password)
    const hash5 = crypto.createHash('sha1').update(salt + processedPassword).digest('hex');
    if (hash5 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (SHA1(salt + password))`);
        return;
    }
    
    // Вариант 6: SHA1(password)
    const hash6 = crypto.createHash('sha1').update(processedPassword).digest('hex');
    if (hash6 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (SHA1(password))`);
        return;
    }
    
    // Вариант 7: MD5(MD5(password + salt))
    const hash7 = crypto.createHash('md5').update(
        crypto.createHash('md5').update(processedPassword + salt).digest('hex')
    ).digest('hex');
    if (hash7 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (MD5(MD5(password + salt)))`);
        return;
    }
    
    // Вариант 8: SHA1(MD5(password + salt))
    const hash8 = crypto.createHash('sha1').update(
        crypto.createHash('md5').update(processedPassword + salt).digest('hex')
    ).digest('hex');
    if (hash8 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (SHA1(MD5(password + salt)))`);
        return;
    }
    
    // Вариант 9: MD5(SHA1(password + salt))
    const hash9 = crypto.createHash('md5').update(
        crypto.createHash('sha1').update(processedPassword + salt).digest('hex')
    ).digest('hex');
    if (hash9 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (MD5(SHA1(password + salt)))`);
        return;
    }
    
    // Вариант 10: HMAC-MD5(password, salt)
    const hash10 = crypto.createHmac('md5', salt).update(processedPassword).digest('hex');
    if (hash10 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (HMAC-MD5(password, salt))`);
        return;
    }
    
    // Вариант 11: HMAC-SHA1(password, salt)
    const hash11 = crypto.createHmac('sha1', salt).update(processedPassword).digest('hex');
    if (hash11 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (HMAC-SHA1(password, salt))`);
        return;
    }
    
    // Вариант 12: PBKDF2(password, salt, 1000)
    const hash12 = crypto.pbkdf2Sync(processedPassword, salt, 1000, 16, 'md5').toString('hex');
    if (hash12 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2(password, salt, 1000))`);
        return;
    }
    
    // Вариант 13: PBKDF2-SHA1(password, salt, 1000)
    const hash13 = crypto.pbkdf2Sync(processedPassword, salt, 1000, 16, 'sha1').toString('hex');
    if (hash13 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2-SHA1(password, salt, 1000))`);
        return;
    }
    
    // Вариант 14: PBKDF2-SHA256(password, salt, 1000)
    const hash14 = crypto.pbkdf2Sync(processedPassword, salt, 1000, 16, 'sha256').toString('hex');
    if (hash14 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2-SHA256(password, salt, 1000))`);
        return;
    }
    
    // Вариант 15: PBKDF2(password, salt, 10000)
    const hash15 = crypto.pbkdf2Sync(processedPassword, salt, 10000, 16, 'md5').toString('hex');
    if (hash15 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2(password, salt, 10000))`);
        return;
    }
    
    // Вариант 16: PBKDF2-SHA1(password, salt, 10000)
    const hash16 = crypto.pbkdf2Sync(processedPassword, salt, 10000, 16, 'sha1').toString('hex');
    if (hash16 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2-SHA1(password, salt, 10000))`);
        return;
    }
    
    // Вариант 17: PBKDF2-SHA256(password, salt, 10000)
    const hash17 = crypto.pbkdf2Sync(processedPassword, salt, 10000, 16, 'sha256').toString('hex');
    if (hash17 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2-SHA256(password, salt, 10000))`);
        return;
    }
    
    // Вариант 18: PBKDF2(password, salt, 100000)
    const hash18 = crypto.pbkdf2Sync(processedPassword, salt, 100000, 16, 'md5').toString('hex');
    if (hash18 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2(password, salt, 100000))`);
        return;
    }
    
    // Вариант 19: PBKDF2-SHA1(password, salt, 100000)
    const hash19 = crypto.pbkdf2Sync(processedPassword, salt, 100000, 16, 'sha1').toString('hex');
    if (hash19 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2-SHA1(password, salt, 100000))`);
        return;
    }
    
    // Вариант 20: PBKDF2-SHA256(password, salt, 100000)
    const hash20 = crypto.pbkdf2Sync(processedPassword, salt, 100000, 16, 'sha256').toString('hex');
    if (hash20 === expectedHash) {
        console.log(`✅ Найден пароль: "${password}" (PBKDF2-SHA256(password, salt, 100000))`);
        return;
    }
    
    if (index % 10 === 0) {
        console.log(`Проверено ${index + 1} паролей...`);
    }
});

console.log('');
console.log('Проверка завершена.');

