#!/usr/bin/env node

const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const path = require('path');

// Конфигурация для нашей базы данных
const cloudmastersConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

class DumpImporter {
  constructor() {
    this.cmConnection = null;
    this.stats = {
      users: { imported: 0, errors: 0 },
      groups: { imported: 0, errors: 0 },
      forums: { imported: 0, errors: 0 },
      topics: { imported: 0, errors: 0 },
      posts: { imported: 0, errors: 0 }
    };
  }

  async connect() {
    console.log('🔌 Подключение к CloudMasters базе данных...');
    
    try {
      this.cmConnection = await mysql.createConnection(cloudmastersConfig);
      console.log('✅ Подключение установлено');
    } catch (error) {
      console.error('❌ Ошибка подключения:', error.message);
      throw error;
    }
  }

  async disconnect() {
    if (this.cmConnection) await this.cmConnection.end();
    console.log('🔌 Соединение закрыто');
  }

  async createTempDatabase() {
    console.log('\n🗄️ Создание временной базы данных...');
    
    try {
      // Создаем временную базу данных
      await this.cmConnection.execute('CREATE DATABASE IF NOT EXISTS ipb_temp CHARACTER SET utf8 COLLATE utf8_general_ci');
      console.log('✅ Временная база данных создана');
    } catch (error) {
      console.error('❌ Ошибка создания временной базы:', error.message);
      throw error;
    }
  }

  async importDump(dumpPath) {
    console.log(`\n📥 Импорт дампа из файла: ${dumpPath}`);
    
    try {
      // Читаем содержимое дампа
      const dumpContent = await fs.readFile(dumpPath, 'utf8');
      
      // Разбиваем на отдельные запросы
      const queries = dumpContent
        .split(';')
        .map(query => query.trim())
        .filter(query => query.length > 0 && !query.startsWith('--'));
      
      console.log(`📊 Найдено ${queries.length} запросов в дампе`);
      
      // Выполняем запросы
      for (let i = 0; i < queries.length; i++) {
        const query = queries[i];
        if (query.trim()) {
          try {
            await this.cmConnection.execute(query);
            if (i % 100 === 0) {
              console.log(`  ✅ Выполнено ${i + 1}/${queries.length} запросов`);
            }
          } catch (error) {
            console.error(`  ❌ Ошибка в запросе ${i + 1}:`, error.message);
          }
        }
      }
      
      console.log('✅ Дамп импортирован во временную базу');
    } catch (error) {
      console.error('❌ Ошибка импорта дампа:', error.message);
      throw error;
    }
  }

  async analyzeImportedData() {
    console.log('\n🔍 Анализ импортированных данных...');
    
    try {
      // Переключаемся на временную базу
      await this.cmConnection.execute('USE ipb_temp');
      
      // Получаем список таблиц
      const [tables] = await this.cmConnection.execute('SHOW TABLES');
      console.log('📋 Найденные таблицы:');
      tables.forEach(table => {
        const tableName = Object.values(table)[0];
        console.log(`  - ${tableName}`);
      });
      
      // Анализируем ключевые таблицы
      const keyTables = ['cldmembers', 'cldmember_groups', 'cldforums', 'cldtopics', 'cldposts'];
      
      for (const tableName of keyTables) {
        try {
          const [count] = await this.cmConnection.execute(`SELECT COUNT(*) as count FROM ${tableName}`);
          console.log(`  📊 ${tableName}: ${count[0].count} записей`);
        } catch (error) {
          console.log(`  ❌ Таблица ${tableName} не найдена`);
        }
      }
      
    } catch (error) {
      console.error('❌ Ошибка анализа данных:', error.message);
    }
  }

  async migrateData() {
    console.log('\n🔄 Миграция данных в CloudMasters...');
    
    try {
      // Переключаемся между базами
      await this.cmConnection.execute('USE ipb_temp');
      
      // Мигрируем группы пользователей
      console.log('👥 Миграция групп пользователей...');
      const [groups] = await this.cmConnection.execute('SELECT * FROM cldmember_groups');
      
      await this.cmConnection.execute('USE cloudmasters');
      for (const group of groups) {
        try {
          await this.cmConnection.execute(`
            INSERT IGNORE INTO member_groups (id, name, description, permissions)
            VALUES (?, ?, ?, ?)
          `, [group.id, group.name, group.description, group.permissions]);
          
          this.stats.groups.imported++;
        } catch (error) {
          this.stats.groups.errors++;
        }
      }
      
      // Мигрируем пользователей
      console.log('👤 Миграция пользователей...');
      await this.cmConnection.execute('USE ipb_temp');
      const [members] = await this.cmConnection.execute('SELECT * FROM cldmembers');
      
      await this.cmConnection.execute('USE cloudmasters');
      for (const member of members) {
        try {
          await this.cmConnection.execute(`
            INSERT IGNORE INTO members (
              id, name, members_display_name, members_l_username,
              email, member_group_id, joined, last_visit, posts,
              title, member_banned, ip_address, is_activated
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
          `, [
            member.member_id, member.name, member.members_display_name,
            member.members_l_username, member.email, member.member_group_id,
            member.joined, member.last_visit, member.posts,
            member.title, member.member_banned, member.ip_address
          ]);
          
          this.stats.users.imported++;
        } catch (error) {
          this.stats.users.errors++;
        }
      }
      
      // Мигрируем форумы
      console.log('💬 Миграция форумов...');
      await this.cmConnection.execute('USE ipb_temp');
      const [forums] = await this.cmConnection.execute('SELECT * FROM cldforums');
      
      await this.cmConnection.execute('USE cloudmasters');
      for (const forum of forums) {
        try {
          await this.cmConnection.execute(`
            INSERT IGNORE INTO forum_categories (id, name, description, parent_id, position)
            VALUES (?, ?, ?, ?, ?)
          `, [forum.id, forum.name, forum.description, forum.parent_id, forum.position]);
          
          this.stats.forums.imported++;
        } catch (error) {
          this.stats.forums.errors++;
        }
      }
      
      // Мигрируем темы
      console.log('📝 Миграция тем...');
      await this.cmConnection.execute('USE ipb_temp');
      const [topics] = await this.cmConnection.execute('SELECT * FROM cldtopics');
      
      await this.cmConnection.execute('USE cloudmasters');
      for (const topic of topics) {
        try {
          await this.cmConnection.execute(`
            INSERT IGNORE INTO forum_topics (
              id, title, forum_id, author_id, posts_count, views_count,
              is_pinned, is_locked, created_at, last_post_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
          `, [
            topic.tid, topic.title, topic.forum_id, topic.starter_id,
            topic.posts, topic.views, topic.pinned, topic.state,
            topic.start_date, topic.last_post
          ]);
          
          this.stats.topics.imported++;
        } catch (error) {
          this.stats.topics.errors++;
        }
      }
      
      // Мигрируем сообщения
      console.log('💭 Миграция сообщений...');
      await this.cmConnection.execute('USE ipb_temp');
      const [posts] = await this.cmConnection.execute('SELECT * FROM cldposts');
      
      await this.cmConnection.execute('USE cloudmasters');
      for (const post of posts) {
        try {
          await this.cmConnection.execute(`
            INSERT IGNORE INTO forum_posts (
              id, topic_id, author_id, content, created_at, ip_address
            ) VALUES (?, ?, ?, ?, ?, ?)
          `, [
            post.pid, post.topic_id, post.author_id,
            post.post, post.post_date, post.ip_address
          ]);
          
          this.stats.posts.imported++;
          if (this.stats.posts.imported % 100 === 0) {
            console.log(`  ✅ Импортировано ${this.stats.posts.imported} сообщений`);
          }
        } catch (error) {
          this.stats.posts.errors++;
        }
      }
      
    } catch (error) {
      console.error('❌ Ошибка миграции данных:', error.message);
    }
  }

  async updateStatistics() {
    console.log('\n📊 Обновление статистики...');
    
    try {
      await this.cmConnection.execute('USE cloudmasters');
      
      // Обновляем статистику форумов
      await this.cmConnection.execute(`
        UPDATE forum_categories fc
        SET 
          topics_count = (SELECT COUNT(*) FROM forum_topics WHERE forum_id = fc.id),
          posts_count = (
            SELECT COUNT(*) FROM forum_posts fp
            JOIN forum_topics ft ON fp.topic_id = ft.id
            WHERE ft.forum_id = fc.id
          )
      `);

      // Обновляем статистику пользователей
      await this.cmConnection.execute(`
        UPDATE members m
        SET posts = (
          SELECT COUNT(*) FROM forum_posts WHERE author_id = m.id
        )
      `);

      console.log('✅ Статистика обновлена');
    } catch (error) {
      console.error('❌ Ошибка обновления статистики:', error.message);
    }
  }

  async cleanup() {
    console.log('\n🧹 Очистка временных данных...');
    
    try {
      await this.cmConnection.execute('DROP DATABASE IF EXISTS ipb_temp');
      console.log('✅ Временная база данных удалена');
    } catch (error) {
      console.error('❌ Ошибка очистки:', error.message);
    }
  }

  printStats() {
    console.log('\n📊 Статистика импорта:');
    console.log('👥 Пользователи:', this.stats.users.imported, 'импортировано,', this.stats.users.errors, 'ошибок');
    console.log('👤 Группы:', this.stats.groups.imported, 'импортировано,', this.stats.groups.errors, 'ошибок');
    console.log('💬 Форумы:', this.stats.forums.imported, 'импортировано,', this.stats.forums.errors, 'ошибок');
    console.log('📝 Темы:', this.stats.topics.imported, 'импортировано,', this.stats.topics.errors, 'ошибок');
    console.log('💭 Сообщения:', this.stats.posts.imported, 'импортировано,', this.stats.posts.errors, 'ошибок');
  }

  async run(dumpPath) {
    console.log('🚀 Запуск импорта из SQL дампа...\n');
    
    try {
      await this.connect();
      await this.createTempDatabase();
      await this.importDump(dumpPath);
      await this.analyzeImportedData();
      await this.migrateData();
      await this.updateStatistics();
      await this.cleanup();
      
      this.printStats();
      
      console.log('\n✅ Импорт завершен успешно!');
      
    } catch (error) {
      console.error('❌ Критическая ошибка:', error.message);
    } finally {
      await this.disconnect();
    }
  }
}

// Запуск импорта
async function main() {
  const dumpPath = process.argv[2] || 'imports/ipb_dump.sql';
  
  if (!dumpPath) {
    console.error('❌ Укажите путь к файлу дампа');
    console.log('Использование: node import-from-dump.js [путь_к_файлу]');
    process.exit(1);
  }
  
  const importer = new DumpImporter();
  await importer.run(dumpPath);
}

if (require.main === module) {
  main().catch(console.error);
}

module.exports = DumpImporter; 