async function testNewAPI() {
  console.log('Тестирование нового API авторизации...\n');
  
  try {
    const response = await fetch('http://localhost:3000/api/test-auth', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'testpass123'
      })
    });
    
    console.log('Статус:', response.status);
    const data = await response.json();
    console.log('Ответ:', JSON.stringify(data, null, 2));
    
    if (data.success) {
      console.log('✓ Авторизация успешна!');
      console.log('Пользователь:', data.user?.name);
    } else {
      console.log('✗ Авторизация не удалась:', data.error);
    }
    
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
}

testNewAPI(); 