async function testLoginCorrect() {
  console.log('Тестирование авторизации с правильными данными...\n');
  
  try {
    // Тест с тестовым пользователем
    console.log('Тест 1: Авторизация с тестовым пользователем');
    const response1 = await fetch('http://localhost:3000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'testpass123'
      })
    });
    
    console.log('Статус:', response1.status);
    const data1 = await response1.json();
    console.log('Ответ:', data1);
    
    if (response1.status === 200) {
      console.log('✓ Авторизация успешна!');
      console.log('Токен:', data1.token ? data1.token.substring(0, 50) + '...' : 'Нет токена');
    } else {
      console.log('✗ Авторизация не удалась');
    }
    
    console.log('\n==================================================\n');
    
    // Тест с импортированным пользователем
    console.log('Тест 2: Авторизация с импортированным пользователем');
    const response2 = await fetch('http://localhost:3000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'antorlov@mail.ru',
        password: 'password123' // Предполагаемый пароль
      })
    });
    
    console.log('Статус:', response2.status);
    const data2 = await response2.json();
    console.log('Ответ:', data2);
    
    if (response2.status === 200) {
      console.log('✓ Авторизация успешна!');
    } else {
      console.log('✗ Авторизация не удалась');
    }
    
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
}

testLoginCorrect();



