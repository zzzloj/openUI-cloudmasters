// Используем встроенный fetch в Node.js 18+

async function testAdminAPI() {
  try {
    // Сначала получим токен авторизации
    const loginResponse = await fetch('http://localhost:3000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        username: 'test_admin',
        password: 'password'
      })
    });

    if (!loginResponse.ok) {
      console.log('Ошибка авторизации:', await loginResponse.text());
      return;
    }

    const loginData = await loginResponse.json();
    const token = loginData.token;

    console.log('Токен получен:', token ? 'Да' : 'Нет');

    // Теперь проверим API пользователей
    const usersResponse = await fetch('http://localhost:3000/api/admin/forum/users', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    if (usersResponse.ok) {
      const usersData = await usersResponse.json();
      console.log('Пользователи получены:', usersData.users ? usersData.users.length : 'Ошибка');
      
      // Проверим группы пользователей
      if (usersData.users && usersData.users.length > 0) {
        console.log('Примеры групп пользователей:');
        usersData.users.slice(0, 5).forEach(user => {
          console.log(`- ${user.display_name}: группа ${user.member_group_id}`);
        });
      }
    } else {
      console.log('Ошибка получения пользователей:', await usersResponse.text());
    }

    // Проверим API категорий
    const categoriesResponse = await fetch('http://localhost:3000/api/admin/forum/categories', {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });

    if (categoriesResponse.ok) {
      const categoriesData = await categoriesResponse.json();
      console.log('Категории получены:', categoriesData.categories ? categoriesData.categories.length : 'Ошибка');
    } else {
      console.log('Ошибка получения категорий:', await categoriesResponse.text());
    }

  } catch (error) {
    console.error('Ошибка тестирования:', error);
  }
}

testAdminAPI();
