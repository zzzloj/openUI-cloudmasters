const { verifyPassword, hashPassword } = require('./src/lib/database.ts');

function testVerifyPassword() {
  try {
    const password = 'testpass123';
    const salt = 'BFAKr';
    const hash = '9e4a93df3f1d2d8efa9fa3b0cf1fbcce';
    
    console.log('Testing password verification...');
    console.log('Password:', password);
    console.log('Salt:', salt);
    console.log('Hash:', hash);
    
    const isValid = verifyPassword(password, salt, hash);
    console.log('Password is valid:', isValid);
    
    // Тестируем хеширование
    const newHash = hashPassword(password, salt);
    console.log('New hash:', newHash);
    console.log('Hashes match:', newHash === hash);
    
  } catch (error) {
    console.error('Error verifying password:', error);
  }
}

testVerifyPassword(); 