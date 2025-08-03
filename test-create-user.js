const { createUser, generateSalt, hashPassword } = require('./src/lib/database.ts');

async function testCreateUser() {
  try {
    const salt = generateSalt();
    const hashedPassword = hashPassword('testpass123', salt);
    
    const userData = {
      name: 'testuser7',
      email: 'test7@example.com',
      members_pass_hash: hashedPassword,
      members_pass_salt: salt,
      ip_address: '127.0.0.1',
      joined: Math.floor(Date.now() / 1000),
      members_display_name: 'Test User',
      members_seo_name: 'testuser7',
      members_l_display_name: 'Test User',
      members_l_username: 'testuser7'
    };
    
    console.log('Attempting to create user:', userData);
    const result = await createUser(userData);
    console.log('Create user result:', result);
    
  } catch (error) {
    console.error('Error creating user:', error);
  }
}

testCreateUser(); 