#!/usr/bin/env node

const mysql = require('mysql2/promise');
const fs = require('fs').promises;

// Конфигурация для нашей базы данных
const cloudmastersConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

class SimpleImporter {
  constructor() {
    this.connection = null;
    this.stats = {
      users: { imported: 0, errors: 0 },
      groups: { imported: 0, errors: 0 },
      forums: { imported: 0, errors: 0 },
      topics: { imported: 0, errors: 0 },
      posts: { imported: 0, errors: 0 }
    };
  }

  async connect() {
    console.log('🔌 Подключение к базе данных...');
    this.connection = await mysql.createConnection(cloudmastersConfig);
    console.log('✅ Подключение установлено');
  }

  async disconnect() {
    if (this.connection) {
      await this.connection.end();
    }
    console.log('🔌 Соединение закрыто');
  }

  async importFromDump(dumpPath) {
    console.log('📥 Чтение дампа...');
    
    try {
      const dumpContent = await fs.readFile(dumpPath, 'utf8');
      
      // Извлекаем данные из дампа
      await this.extractMemberGroups(dumpContent);
      await this.extractMembers(dumpContent);
      await this.extractForums(dumpContent);
      await this.extractTopics(dumpContent);
      await this.extractPosts(dumpContent);
      
    } catch (error) {
      console.error('❌ Ошибка чтения дампа:', error.message);
      throw error;
    }
  }

  async extractMemberGroups(dumpContent) {
    console.log('👤 Извлечение групп пользователей...');
    
    try {
      // Ищем INSERT INTO для member_groups
      const groupMatches = dumpContent.match(/INSERT INTO `cldmembers` VALUES[^;]+;/g);
      
      if (groupMatches) {
        for (const match of groupMatches) {
          try {
            // Очищаем от PHP сериализованных данных
            const cleanedMatch = match.replace(/a:\d+:\{[^}]*\}/g, 'NULL');
            await this.connection.query(cleanedMatch.replace('cldmembers', 'member_groups'));
            this.stats.groups.imported++;
          } catch (error) {
            this.stats.groups.errors++;
          }
        }
      }
      
      console.log(`  ✅ Группы: ${this.stats.groups.imported} импортировано`);
      
    } catch (error) {
      console.log('  ⚠️ Группы: не найдены в дампе');
    }
  }

  async extractMembers(dumpContent) {
    console.log('👥 Извлечение пользователей...');
    
    try {
      // Ищем INSERT INTO для members
      const memberMatches = dumpContent.match(/INSERT INTO `cldmembers` VALUES[^;]+;/g);
      
      if (memberMatches) {
        for (const match of memberMatches) {
          try {
            // Очищаем от PHP сериализованных данных
            const cleanedMatch = match.replace(/a:\d+:\{[^}]*\}/g, 'NULL');
            await this.connection.query(cleanedMatch.replace('cldmembers', 'members'));
            this.stats.users.imported++;
          } catch (error) {
            this.stats.users.errors++;
          }
        }
      }
      
      console.log(`  ✅ Пользователи: ${this.stats.users.imported} импортировано`);
      
    } catch (error) {
      console.log('  ⚠️ Пользователи: не найдены в дампе');
    }
  }

  async extractForums(dumpContent) {
    console.log('💬 Извлечение форумов...');
    
    try {
      // Ищем INSERT INTO для forums
      const forumMatches = dumpContent.match(/INSERT INTO `cldforums` VALUES[^;]+;/g);
      
      if (forumMatches) {
        for (const match of forumMatches) {
          try {
            // Очищаем от PHP сериализованных данных
            const cleanedMatch = match.replace(/a:\d+:\{[^}]*\}/g, 'NULL');
            await this.connection.query(cleanedMatch.replace('cldforums', 'forums'));
            this.stats.forums.imported++;
          } catch (error) {
            this.stats.forums.errors++;
          }
        }
      }
      
      console.log(`  ✅ Форумы: ${this.stats.forums.imported} импортировано`);
      
    } catch (error) {
      console.log('  ⚠️ Форумы: не найдены в дампе');
    }
  }

  async extractTopics(dumpContent) {
    console.log('📝 Извлечение тем...');
    
    try {
      // Ищем INSERT INTO для topics
      const topicMatches = dumpContent.match(/INSERT INTO `cldtopics` VALUES[^;]+;/g);
      
      if (topicMatches) {
        for (const match of topicMatches) {
          try {
            // Очищаем от PHP сериализованных данных
            const cleanedMatch = match.replace(/a:\d+:\{[^}]*\}/g, 'NULL');
            await this.connection.query(cleanedMatch.replace('cldtopics', 'topics'));
            this.stats.topics.imported++;
          } catch (error) {
            this.stats.topics.errors++;
          }
        }
      }
      
      console.log(`  ✅ Темы: ${this.stats.topics.imported} импортировано`);
      
    } catch (error) {
      console.log('  ⚠️ Темы: не найдены в дампе');
    }
  }

  async extractPosts(dumpContent) {
    console.log('💭 Извлечение сообщений...');
    
    try {
      // Ищем INSERT INTO для posts
      const postMatches = dumpContent.match(/INSERT INTO `cldposts` VALUES[^;]+;/g);
      
      if (postMatches) {
        for (const match of postMatches) {
          try {
            // Очищаем от PHP сериализованных данных
            const cleanedMatch = match.replace(/a:\d+:\{[^}]*\}/g, 'NULL');
            await this.connection.query(cleanedMatch.replace('cldposts', 'posts'));
            this.stats.posts.imported++;
          } catch (error) {
            this.stats.posts.errors++;
          }
        }
      }
      
      console.log(`  ✅ Сообщения: ${this.stats.posts.imported} импортировано`);
      
    } catch (error) {
      console.log('  ⚠️ Сообщения: не найдены в дампе');
    }
  }

  printStats() {
    console.log('\n📊 Статистика импорта:');
    console.log(`👥 Пользователи: ${this.stats.users.imported} импортировано, ${this.stats.users.errors} ошибок`);
    console.log(`👤 Группы: ${this.stats.groups.imported} импортировано, ${this.stats.groups.errors} ошибок`);
    console.log(`💬 Форумы: ${this.stats.forums.imported} импортировано, ${this.stats.forums.errors} ошибок`);
    console.log(`📝 Темы: ${this.stats.topics.imported} импортировано, ${this.stats.topics.errors} ошибок`);
    console.log(`💭 Сообщения: ${this.stats.posts.imported} импортировано, ${this.stats.posts.errors} ошибок`);
  }

  async run(dumpPath) {
    console.log('🚀 Запуск упрощенного импорта...\n');
    
    try {
      await this.connect();
      await this.importFromDump(dumpPath);
      
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
  const dumpPath = process.argv[2] || '../imports/u3186919_test1_tower.sql';
  
  if (!dumpPath) {
    console.error('❌ Укажите путь к файлу дампа');
    console.log('Использование: node simple-import.js [путь_к_файлу]');
    process.exit(1);
  }
  
  const importer = new SimpleImporter();
  await importer.run(dumpPath);
}

if (require.main === module) {
  main().catch(console.error);
}

module.exports = SimpleImporter;
