async function testAPISimple() {
  console.log('Простой тест API...\n');
  
  try {
    // Тест 1: Проверяем, что сервер отвечает
    console.log('Тест 1: Проверка доступности сервера');
    const response1 = await fetch('http://localhost:3000');
    console.log('Главная страница статус:', response1.status);
    
    // Тест 2: Проверяем debug API
    console.log('\nТест 2: Проверка debug API');
    const response2 = await fetch('http://localhost:3000/api/debug', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ test: 'data' })
    });
    console.log('Debug API статус:', response2.status);
    
    if (response2.ok) {
      const data2 = await response2.json();
      console.log('Debug API ответ:', data2);
    }
    
    // Тест 3: Проверяем API авторизации с минимальными данными
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
    
    if (response3.ok) {
      const data3 = await response3.json();
      console.log('API авторизации ответ:', data3);
    } else {
      const errorData = await response3.json();
      console.log('API авторизации ошибка:', errorData);
    }
    
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
}

testAPISimple();



