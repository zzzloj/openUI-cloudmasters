async function testServer() {
  console.log('Проверка доступности сервера...');
  
  try {
    const response = await fetch('http://localhost:3000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'testpass123'
      })
    });
    
    console.log('Статус ответа:', response.status);
    console.log('Заголовки:', Object.fromEntries(response.headers.entries()));
    
    const text = await response.text();
    console.log('Тело ответа:', text);
    
  } catch (error) {
    console.error('Ошибка подключения к серверу:', error.message);
  }
}

testServer(); 