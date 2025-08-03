const { getUserByEmail, getUserByUsername, verifyPassword } = require('./src/lib/database.ts');

async function testLoginAPI() {
  try {
    const emailOrUsername = 'testuser7';
    const password = 'testpass123';
    
    console.log('Testing login API logic...');
    console.log('Searching for user:', emailOrUsername);
    
    // Поиск пользователя
    let user = await getUserByEmail(emailOrUsername);
    console.log('User by email:', user ? 'found' : 'not found');
    
    if (!user) {
      user = await getUserByUsername(emailOrUsername);
      console.log('User by username:', user ? 'found' : 'not found');
    }
    
    if (!user) {
      console.log('User not found');
      return;
    }
    
    console.log('User found:', user.member_id, user.name, user.email);
    console.log('User salt:', user.members_pass_salt);
    console.log('User hash:', user.members_pass_hash);
    
    // Проверка пароля
    const isPasswordValid = verifyPassword(password, user.members_pass_salt, user.members_pass_hash);
    console.log('Password is valid:', isPasswordValid);
    
    if (!isPasswordValid) {
      console.log('Invalid password');
      return;
    }
    
    console.log('Login successful!');
    
  } catch (error) {
    console.error('Error in login test:', error);
  }
}

testLoginAPI(); 