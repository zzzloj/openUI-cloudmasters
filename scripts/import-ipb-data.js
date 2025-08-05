#!/usr/bin/env node

const mysql = require('mysql2/promise');
const fs = require('fs').promises;
const path = require('path');

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

class IPBImporter {
  constructor() {
    this.ipbConnection = null;
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
    console.log('🔌 Подключение к базам данных...');
    
    try {
      this.ipbConnection = await mysql.createConnection(ipbConfig);
      this.cmConnection = await mysql.createConnection(cloudmastersConfig);
      console.log('✅ Подключение установлено');
    } catch (error) {
      console.error('❌ Ошибка подключения:', error.message);
      throw error;
    }
  }

  async disconnect() {
    if (this.ipbConnection) await this.ipbConnection.end();
    if (this.cmConnection) await this.cmConnection.end();
    console.log('🔌 Соединения закрыты');
  }

  async importMemberGroups() {
    console.log('\n👥 Импорт групп пользователей...');
    
    try {
      // Получаем группы из IPB
      const [ipbGroups] = await this.ipbConnection.execute(`
        SELECT id, name, description, permissions
        FROM member_groups
        ORDER BY id
      `);

      for (const group of ipbGroups) {
        try {
          // Проверяем, существует ли группа
          const [existing] = await this.cmConnection.execute(
            'SELECT id FROM member_groups WHERE id = ?',
            [group.id]
          );

          if (existing.length === 0) {
            // Создаем новую группу
            await this.cmConnection.execute(`
              INSERT INTO member_groups (id, name, description, permissions)
              VALUES (?, ?, ?, ?)
            `, [group.id, group.name, group.description, group.permissions]);
            
            this.stats.groups.imported++;
            console.log(`  ✅ Группа "${group.name}" импортирована`);
          } else {
            console.log(`  ⚠️ Группа "${group.name}" уже существует`);
          }
        } catch (error) {
          this.stats.groups.errors++;
          console.error(`  ❌ Ошибка импорта группы "${group.name}":`, error.message);
        }
      }
    } catch (error) {
      console.error('❌ Ошибка при импорте групп:', error.message);
    }
  }

  async importMembers() {
    console.log('\n👤 Импорт пользователей...');
    
    try {
      // Получаем пользователей из IPB
      const [ipbMembers] = await this.ipbConnection.execute(`
        SELECT 
          member_id, name, members_display_name, members_l_username,
          email, member_group_id, joined, last_visit, posts,
          title, member_banned, ip_address
        FROM members
        ORDER BY member_id
      `);

      for (const member of ipbMembers) {
        try {
          // Проверяем, существует ли пользователь
          const [existing] = await this.cmConnection.execute(
            'SELECT id FROM members WHERE id = ?',
            [member.member_id]
          );

          if (existing.length === 0) {
            // Создаем нового пользователя
            await this.cmConnection.execute(`
              INSERT INTO members (
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
            console.log(`  ✅ Пользователь "${member.name}" импортирован`);
          } else {
            console.log(`  ⚠️ Пользователь "${member.name}" уже существует`);
          }
        } catch (error) {
          this.stats.users.errors++;
          console.error(`  ❌ Ошибка импорта пользователя "${member.name}":`, error.message);
        }
      }
    } catch (error) {
      console.error('❌ Ошибка при импорте пользователей:', error.message);
    }
  }

  async importForums() {
    console.log('\n💬 Импорт форумов...');
    
    try {
      // Получаем форумы из IPB
      const [ipbForums] = await this.ipbConnection.execute(`
        SELECT id, name, description, parent_id, position
        FROM forums
        ORDER BY position, id
      `);

      for (const forum of ipbForums) {
        try {
          // Проверяем, существует ли форум
          const [existing] = await this.cmConnection.execute(
            'SELECT id FROM forum_categories WHERE id = ?',
            [forum.id]
          );

          if (existing.length === 0) {
            // Создаем новый форум
            await this.cmConnection.execute(`
              INSERT INTO forum_categories (id, name, description, parent_id, position)
              VALUES (?, ?, ?, ?, ?)
            `, [forum.id, forum.name, forum.description, forum.parent_id, forum.position]);
            
            this.stats.forums.imported++;
            console.log(`  ✅ Форум "${forum.name}" импортирован`);
          } else {
            console.log(`  ⚠️ Форум "${forum.name}" уже существует`);
          }
        } catch (error) {
          this.stats.forums.errors++;
          console.error(`  ❌ Ошибка импорта форума "${forum.name}":`, error.message);
        }
      }
    } catch (error) {
      console.error('❌ Ошибка при импорте форумов:', error.message);
    }
  }

  async importTopics() {
    console.log('\n📝 Импорт тем...');
    
    try {
      // Получаем темы из IPB
      const [ipbTopics] = await this.ipbConnection.execute(`
        SELECT 
          tid, title, forum_id, starter_id, posts, views,
          pinned, state, start_date, last_post
        FROM topics
        ORDER BY tid
      `);

      for (const topic of ipbTopics) {
        try {
          // Проверяем, существует ли тема
          const [existing] = await this.cmConnection.execute(
            'SELECT id FROM forum_topics WHERE id = ?',
            [topic.tid]
          );

          if (existing.length === 0) {
            // Создаем новую тему
            await this.cmConnection.execute(`
              INSERT INTO forum_topics (
                id, title, forum_id, author_id, posts_count, views_count,
                is_pinned, is_locked, created_at, last_post_date
              ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            `, [
              topic.tid, topic.title, topic.forum_id, topic.starter_id,
              topic.posts, topic.views, topic.pinned, topic.state,
              topic.start_date, topic.last_post
            ]);
            
            this.stats.topics.imported++;
            console.log(`  ✅ Тема "${topic.title}" импортирована`);
          } else {
            console.log(`  ⚠️ Тема "${topic.title}" уже существует`);
          }
        } catch (error) {
          this.stats.topics.errors++;
          console.error(`  ❌ Ошибка импорта темы "${topic.title}":`, error.message);
        }
      }
    } catch (error) {
      console.error('❌ Ошибка при импорте тем:', error.message);
    }
  }

  async importPosts() {
    console.log('\n💭 Импорт сообщений...');
    
    try {
      // Получаем сообщения из IPB
      const [ipbPosts] = await this.ipbConnection.execute(`
        SELECT 
          pid, topic_id, author_id, post, post_date, ip_address
        FROM posts
        ORDER BY pid
      `);

      for (const post of ipbPosts) {
        try {
          // Проверяем, существует ли сообщение
          const [existing] = await this.cmConnection.execute(
            'SELECT id FROM forum_posts WHERE id = ?',
            [post.pid]
          );

          if (existing.length === 0) {
            // Создаем новое сообщение
            await this.cmConnection.execute(`
              INSERT INTO forum_posts (
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
          }
        } catch (error) {
          this.stats.posts.errors++;
          if (this.stats.posts.errors % 10 === 0) {
            console.error(`  ❌ Ошибок импорта сообщений: ${this.stats.posts.errors}`);
          }
        }
      }
    } catch (error) {
      console.error('❌ Ошибка при импорте сообщений:', error.message);
    }
  }

  async updateStatistics() {
    console.log('\n📊 Обновление статистики...');
    
    try {
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

  printStats() {
    console.log('\n📊 Статистика импорта:');
    console.log('👥 Пользователи:', this.stats.users.imported, 'импортировано,', this.stats.users.errors, 'ошибок');
    console.log('👤 Группы:', this.stats.groups.imported, 'импортировано,', this.stats.groups.errors, 'ошибок');
    console.log('💬 Форумы:', this.stats.forums.imported, 'импортировано,', this.stats.forums.errors, 'ошибок');
    console.log('📝 Темы:', this.stats.topics.imported, 'импортировано,', this.stats.topics.errors, 'ошибок');
    console.log('💭 Сообщения:', this.stats.posts.imported, 'импортировано,', this.stats.posts.errors, 'ошибок');
  }

  async run() {
    console.log('🚀 Запуск импорта данных из IPB...\n');
    
    try {
      await this.connect();
      
      await this.importMemberGroups();
      await this.importMembers();
      await this.importForums();
      await this.importTopics();
      await this.importPosts();
      await this.updateStatistics();
      
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
  const importer = new IPBImporter();
  await importer.run();
}

if (require.main === module) {
  main().catch(console.error);
}

module.exports = IPBImporter; 