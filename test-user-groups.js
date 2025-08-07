const mysql = require('mysql2/promise');

async function testUserGroups() {
  try {
    const connection = await mysql.createConnection({
      host: 'localhost',
      user: 'root',
      password: 'Admin2024@',
      database: 'cloudmasters'
    });

    // Получаем пользователей с их группами
    const [users] = await connection.execute(`
      SELECT member_id, members_display_name, member_group_id, posts, joined
      FROM cldmembers 
      ORDER BY member_group_id, members_display_name 
      LIMIT 20
    `);

    console.log('Проверка групп пользователей:');
    console.log('='.repeat(60));
    
    users.forEach(user => {
      let groupName;
      let groupColor;
      
      switch (user.member_group_id) {
        case 1:
          groupName = 'Пользователь';
          groupColor = 'neutral';
          break;
        case 2:
          groupName = 'VIP Пользователь';
          groupColor = 'success';
          break;
        case 3:
          groupName = 'Модератор';
          groupColor = 'warning';
          break;
        case 4:
          groupName = 'Администратор';
          groupColor = 'danger';
          break;
        default:
          groupName = 'Неизвестная группа';
          groupColor = 'neutral';
      }
      
      console.log(`${user.members_display_name} (ID: ${user.member_id})`);
      console.log(`  Группа ID: ${user.member_group_id} -> ${groupName} (${groupColor})`);
      console.log(`  Сообщений: ${user.posts}`);
      console.log(`  Регистрация: ${new Date(user.joined * 1000).toLocaleDateString()}`);
      console.log('');
    });

    await connection.end();
  } catch (error) {
    console.error('Ошибка:', error);
  }
}

testUserGroups();




