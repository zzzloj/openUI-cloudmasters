// Используем встроенный fetch (Node.js 18+)

async function testLogin() {
  const baseUrl = 'http://localhost:3000';
  
  console.log('Тестирование API авторизации...\n');
  
  // Тест 1: Успешная авторизация с тестовым пользователем
  console.log('Тест 1: Авторизация с тестовым пользователем');
  try {
    const response = await fetch(`${baseUrl}/api/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'testpass123'
      })
    });
    
    const data = await response.json();
    console.log('Статус:', response.status);
    console.log('Ответ:', JSON.stringify(data, null, 2));
    
    if (data.success) {
      console.log('✓ Авторизация успешна!');
      console.log('Токен:', data.token ? 'получен' : 'отсутствует');
      console.log('Пользователь:', data.user?.members_display_name);
    } else {
      console.log('✗ Авторизация не удалась:', data.error);
    }
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
  
  console.log('\n' + '='.repeat(50) + '\n');
  
  // Тест 2: Неверный пароль
  console.log('Тест 2: Неверный пароль');
  try {
    const response = await fetch(`${baseUrl}/api/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'test@example.com',
        password: 'wrongpassword'
      })
    });
    
    const data = await response.json();
    console.log('Статус:', response.status);
    console.log('Ответ:', JSON.stringify(data, null, 2));
    
    if (!data.success) {
      console.log('✓ Ожидаемая ошибка получена');
    } else {
      console.log('✗ Неожиданный успех');
    }
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
  
  console.log('\n' + '='.repeat(50) + '\n');
  
  // Тест 3: Несуществующий пользователь
  console.log('Тест 3: Несуществующий пользователь');
  try {
    const response = await fetch(`${baseUrl}/api/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'nonexistent@example.com',
        password: 'anypassword'
      })
    });
    
    const data = await response.json();
    console.log('Статус:', response.status);
    console.log('Ответ:', JSON.stringify(data, null, 2));
    
    if (!data.success) {
      console.log('✓ Ожидаемая ошибка получена');
    } else {
      console.log('✗ Неожиданный успех');
    }
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
  
  console.log('\n' + '='.repeat(50) + '\n');
  
  // Тест 4: Попытка авторизации с импортированным пользователем
  console.log('Тест 4: Попытка авторизации с импортированным пользователем');
  try {
    const response = await fetch(`${baseUrl}/api/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email: 'antorlov@mail.ru',
        password: 'password'
      })
    });
    
    const data = await response.json();
    console.log('Статус:', response.status);
    console.log('Ответ:', JSON.stringify(data, null, 2));
    
    if (data.success) {
      console.log('✓ Авторизация с импортированным пользователем успешна!');
    } else {
      console.log('✗ Авторизация не удалась:', data.error);
    }
  } catch (error) {
    console.error('Ошибка запроса:', error.message);
  }
}

// Ждем немного, чтобы сервер запустился
setTimeout(testLogin, 5000); 