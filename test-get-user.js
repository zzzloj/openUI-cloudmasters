const { getUserByEmail, getUserByUsername } = require('./src/lib/database.ts');

async function testGetUser() {
  try {
    console.log('Testing getUserByUsername...');
    const userByUsername = await getUserByUsername('testuser7');
    console.log('User by username:', userByUsername);
    
    console.log('Testing getUserByEmail...');
    const userByEmail = await getUserByEmail('test7@example.com');
    console.log('User by email:', userByEmail);
    
  } catch (error) {
    console.error('Error getting user:', error);
  }
}

testGetUser(); 