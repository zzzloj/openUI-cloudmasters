async function testSimple() {
  console.log('Простой тест API...');
  
  try {
    // Тест 1: Проверяем, что сервер отвечает
    console.log('\nТест 1: Проверка доступности сервера');
    const response1 = await fetch('http://localhost:3000');
    console.log('Главная страница статус:', response1.status);
    
    // Тест 2: Проверяем API профиля
    console.log('\nТест 2: Проверка API профиля');
    const response2 = await fetch('http://localhost:3000/api/profile/1');
    console.log('API профиля статус:', response2.status);
    
    // Тест 3: Проверяем API авторизации
    console.log('\nТест 3: Проверка API авторизации');
    const response3 = await fetch('http://localhost:3000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'testpass123'
      })
    });
    console.log('API авторизации статус:', response3.status);
    
    const data = await response3.json();
    console.log('Ответ API авторизации:', data);
    
  } catch (error) {
    console.error('Ошибка:', error.message);
  }
}

testSimple();








