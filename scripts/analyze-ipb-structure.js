#!/usr/bin/env node

const mysql = require('mysql2/promise');

// Конфигурация для подключения к IPB базе данных
const ipbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'ipb_database', // Измените на имя вашей IPB базы
  charset: 'utf8mb4'
};

// Конфигурация для нашей базы данных
const cloudmastersConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function analyzeIPBStructure() {
  console.log('🔍 Анализ структуры базы данных IPB...\n');
  
  try {
    // Подключение к IPB базе данных
    const ipbConnection = await mysql.createConnection(ipbConfig);
    
    // Получаем список всех таблиц
    const [tables] = await ipbConnection.execute('SHOW TABLES');
    console.log('📋 Найденные таблицы в IPB:');
    tables.forEach(table => {
      const tableName = Object.values(table)[0];
      console.log(`  - ${tableName}`);
    });
    
    // Анализируем ключевые таблицы
    const keyTables = [
      'members', 'member_groups', 'forums', 'topics', 'posts',
      'forum_perms', 'forum_tracker', 'profile_portal'
    ];
    
    console.log('\n🔍 Анализ структуры ключевых таблиц:');
    
    for (const tableName of keyTables) {
      try {
        // Получаем структуру таблицы
        const [columns] = await ipbConnection.execute(`DESCRIBE ${tableName}`);
        
        if (columns.length > 0) {
          console.log(`\n📊 Таблица: ${tableName}`);
          console.log('  Структура:');
          columns.forEach(col => {
            console.log(`    - ${col.Field}: ${col.Type} ${col.Null === 'YES' ? 'NULL' : 'NOT NULL'} ${col.Key ? `(${col.Key})` : ''}`);
          });
          
          // Получаем количество записей
          const [count] = await ipbConnection.execute(`SELECT COUNT(*) as count FROM ${tableName}`);
          console.log(`  Записей: ${count[0].count}`);
        }
      } catch (error) {
        console.log(`  ❌ Таблица ${tableName} не найдена`);
      }
    }
    
    // Анализ данных пользователей
    console.log('\n👥 Анализ данных пользователей:');
    try {
      const [members] = await ipbConnection.execute(`
        SELECT 
          COUNT(*) as total_users,
          COUNT(CASE WHEN member_group_id = 4 THEN 1 END) as admins,
          COUNT(CASE WHEN member_group_id = 3 THEN 1 END) as moderators,
          COUNT(CASE WHEN joined > 0 THEN 1 END) as active_users
        FROM members
      `);
      
      if (members.length > 0) {
        const stats = members[0];
        console.log(`  Всего пользователей: ${stats.total_users}`);
        console.log(`  Администраторов: ${stats.admins}`);
        console.log(`  Модераторов: ${stats.moderators}`);
        console.log(`  Активных пользователей: ${stats.active_users}`);
      }
    } catch (error) {
      console.log('  ❌ Не удалось проанализировать пользователей');
    }
    
    // Анализ форума
    console.log('\n💬 Анализ данных форума:');
    try {
      const [forumStats] = await ipbConnection.execute(`
        SELECT 
          (SELECT COUNT(*) FROM topics) as total_topics,
          (SELECT COUNT(*) FROM posts) as total_posts,
          (SELECT COUNT(*) FROM forums) as total_forums
      `);
      
      if (forumStats.length > 0) {
        const stats = forumStats[0];
        console.log(`  Всего тем: ${stats.total_topics}`);
        console.log(`  Всего сообщений: ${stats.total_posts}`);
        console.log(`  Всего форумов: ${stats.total_forums}`);
      }
    } catch (error) {
      console.log('  ❌ Не удалось проанализировать форум');
    }
    
    await ipbConnection.end();
    
  } catch (error) {
    console.error('❌ Ошибка подключения к IPB базе данных:', error.message);
    console.log('\n💡 Убедитесь, что:');
    console.log('  - IPB база данных доступна');
    console.log('  - Правильно указаны параметры подключения');
    console.log('  - База данных содержит данные IPB');
  }
}

async function analyzeCloudmastersStructure() {
  console.log('\n🔍 Анализ структуры CloudMasters базы данных...\n');
  
  try {
    const cmConnection = await mysql.createConnection(cloudmastersConfig);
    
    // Получаем список всех таблиц
    const [tables] = await cmConnection.execute('SHOW TABLES');
    console.log('📋 Существующие таблицы в CloudMasters:');
    tables.forEach(table => {
      const tableName = Object.values(table)[0];
      console.log(`  - ${tableName}`);
    });
    
    // Анализ данных пользователей
    console.log('\n👥 Анализ пользователей CloudMasters:');
    const [members] = await cmConnection.execute(`
      SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN member_group_id = 4 THEN 1 END) as admins,
        COUNT(CASE WHEN member_group_id = 3 THEN 1 END) as moderators,
        COUNT(CASE WHEN member_group_id = 2 THEN 1 END) as vip_users
      FROM members
    `);
    
    if (members.length > 0) {
      const stats = members[0];
      console.log(`  Всего пользователей: ${stats.total_users}`);
      console.log(`  Администраторов: ${stats.admins}`);
      console.log(`  Модераторов: ${stats.moderators}`);
      console.log(`  VIP пользователей: ${stats.vip_users}`);
    }
    
    await cmConnection.end();
    
  } catch (error) {
    console.error('❌ Ошибка подключения к CloudMasters базе данных:', error.message);
  }
}

async function generateMappingReport() {
  console.log('\n📋 Отчет о маппинге полей:\n');
  
  const fieldMapping = {
    'members': {
      'member_id': 'id',
      'name': 'name',
      'members_display_name': 'display_name',
      'members_l_username': 'username',
      'email': 'email',
      'member_group_id': 'member_group_id',
      'joined': 'joined',
      'last_visit': 'last_visit',
      'posts': 'posts',
      'title': 'title',
      'member_banned': 'member_banned',
      'ip_address': 'ip_address'
    },
    'member_groups': {
      'id': 'id',
      'name': 'name',
      'description': 'description',
      'permissions': 'permissions'
    },
    'forums': {
      'id': 'id',
      'name': 'name',
      'description': 'description',
      'parent_id': 'parent_id',
      'position': 'position'
    },
    'topics': {
      'tid': 'id',
      'title': 'title',
      'forum_id': 'forum_id',
      'starter_id': 'author_id',
      'posts': 'posts_count',
      'views': 'views_count',
      'pinned': 'is_pinned',
      'state': 'is_locked',
      'start_date': 'created_at',
      'last_post': 'last_post_date'
    },
    'posts': {
      'pid': 'id',
      'topic_id': 'topic_id',
      'author_id': 'author_id',
      'post': 'content',
      'post_date': 'created_at',
      'ip_address': 'ip_address'
    }
  };
  
  console.log('🔄 Маппинг полей между IPB и CloudMasters:');
  Object.entries(fieldMapping).forEach(([table, fields]) => {
    console.log(`\n📊 Таблица: ${table}`);
    Object.entries(fields).forEach(([ipbField, cmField]) => {
      console.log(`  ${ipbField} → ${cmField}`);
    });
  });
}

// Запуск анализа
async function main() {
  console.log('🚀 Запуск анализа структуры базы данных...\n');
  
  await analyzeIPBStructure();
  await analyzeCloudmastersStructure();
  await generateMappingReport();
  
  console.log('\n✅ Анализ завершен!');
  console.log('\n📝 Следующие шаги:');
  console.log('  1. Проверьте результаты анализа выше');
  console.log('  2. Подготовьте дамп IPB базы данных');
  console.log('  3. Запустите скрипт импорта данных');
}

if (require.main === module) {
  main().catch(console.error);
}

module.exports = {
  analyzeIPBStructure,
  analyzeCloudmastersStructure,
  generateMappingReport
}; 