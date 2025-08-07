const crypto = require('crypto');

// Тестируем правильный алгоритм IPB 3.4
function testIPBHash(password, salt) {
    const md5Password = crypto.createHash('md5').update(password).digest('hex');
    const md5Salt = crypto.createHash('md5').update(salt).digest('hex');
    const finalHash = crypto.createHash('md5').update(md5Salt + md5Password).digest('hex');
    
    return {
        md5Password,
        md5Salt,
        finalHash
    };
}

// Тестируем с данными пользователя Oleg_B
const password = 'GbaDMc8DXG5azEg';
const salt = 'Qc2eW';
const expectedHash = '21ebe693a078adf2a72d3b10e2a70582';

console.log('=== Тест правильного алгоритма IPB 3.4 ===');
console.log('Пароль:', password);
console.log('Соль:', salt);
console.log('Ожидаемый хеш:', expectedHash);
console.log('');

const result = testIPBHash(password, salt);
console.log('MD5 пароля:', result.md5Password);
console.log('MD5 соли:', result.md5Salt);
console.log('Финальный хеш:', result.finalHash);
console.log('Хеши совпадают:', result.finalHash === expectedHash);

if (result.finalHash === expectedHash) {
    console.log('✅ Алгоритм работает правильно!');
} else {
    console.log('❌ Алгоритм не работает. Проверяем другие варианты...');
    
    // Попробуем другие варианты
    console.log('\n=== Проверка других вариантов ===');
    
    // Вариант 1: MD5(password + salt)
    const hash1 = crypto.createHash('md5').update(password + salt).digest('hex');
    console.log('MD5(password + salt):', hash1, hash1 === expectedHash ? '✅' : '❌');
    
    // Вариант 2: MD5(salt + password)
    const hash2 = crypto.createHash('md5').update(salt + password).digest('hex');
    console.log('MD5(salt + password):', hash2, hash2 === expectedHash ? '✅' : '❌');
    
    // Вариант 3: MD5(password)
    const hash3 = crypto.createHash('md5').update(password).digest('hex');
    console.log('MD5(password):', hash3, hash3 === expectedHash ? '✅' : '❌');
    
    // Вариант 4: SHA1(password + salt)
    const hash4 = crypto.createHash('sha1').update(password + salt).digest('hex');
    console.log('SHA1(password + salt):', hash4, hash4 === expectedHash ? '✅' : '❌');
    
    // Вариант 5: SHA1(salt + password)
    const hash5 = crypto.createHash('sha1').update(salt + password).digest('hex');
    console.log('SHA1(salt + password):', hash5, hash5 === expectedHash ? '✅' : '❌');
}

