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

class EmailImporter {
  constructor() {
    this.connection = null;
    this.stats = {
      users: { imported: 0, errors: 0 }
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
      
      // Ищем все INSERT INTO для members
      const insertMatches = dumpContent.match(/INSERT INTO `cldmembers`[^;]+;/gs);
      
      if (insertMatches) {
        console.log(`📋 Найдено ${insertMatches.length} INSERT запросов для пользователей`);
        
        for (const insertQuery of insertMatches) {
          await this.processInsertQuery(insertQuery);
        }
      }
      
    } catch (error) {
      console.error('❌ Ошибка чтения дампа:', error.message);
      throw error;
    }
  }

  async processInsertQuery(insertQuery) {
    try {
      // Извлекаем все значения из INSERT запроса
      const valuesMatch = insertQuery.match(/VALUES\s*\(([^)]+)\)/);
      if (!valuesMatch) return;
      
      const valuesString = valuesMatch[1];
      
      // Разбиваем на отдельные значения, учитывая кавычки
      const values = this.parseValues(valuesString);
      
      if (values.length >= 4) {
        const memberId = values[0];
        const name = values[1];
        const groupId = values[2];
        const email = values[3];
        const joined = values[4];
        const posts = values[6] || 0;
        const title = values[7] || '';
        const lastVisit = values[27] || joined;
        const memberBanned = values[42] || 0;
        const displayName = values[35] || name;
        
        // Проверяем, что email валидный
        if (email && email.includes('@') && email !== 'NULL') {
          await this.connection.query(`
            INSERT IGNORE INTO members (
              id, name, display_name, email, 
              joined_at, last_visit_at, group_id, is_banned,
              posts_count, title
            ) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), ?, ?, ?, ?)
          `, [
            memberId, 
            name, 
            displayName,
            email,
            joined,
            lastVisit,
            groupId || 2,
            memberBanned ? 1 : 0,
            posts,
            title
          ]);
          this.stats.users.imported++;
        }
      }
    } catch (error) {
      this.stats.users.errors++;
      console.log(`    ❌ Ошибка обработки запроса: ${error.message}`);
    }
  }

  parseValues(valuesString) {
    const values = [];
    let currentValue = '';
    let inQuotes = false;
    let quoteChar = '';
    
    for (let i = 0; i < valuesString.length; i++) {
      const char = valuesString[i];
      
      if (!inQuotes && (char === "'" || char === '"')) {
        inQuotes = true;
        quoteChar = char;
        currentValue += char;
      } else if (inQuotes && char === quoteChar) {
        inQuotes = false;
        currentValue += char;
      } else if (!inQuotes && char === ',') {
        values.push(currentValue.trim());
        currentValue = '';
      } else {
        currentValue += char;
      }
    }
    
    // Добавляем последнее значение
    if (currentValue.trim()) {
      values.push(currentValue.trim());
    }
    
    return values;
  }

  printStats() {
    console.log('\n📊 Статистика импорта:');
    console.log(`👥 Пользователи: ${this.stats.users.imported} импортировано, ${this.stats.users.errors} ошибок`);
  }

  async run(dumpPath) {
    console.log('🚀 Запуск импорта по email...\n');
    
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
    console.log('Использование: node email-import.js [путь_к_файлу]');
    process.exit(1);
  }
  
  const importer = new EmailImporter();
  await importer.run(dumpPath);
}

if (require.main === module) {
  main().catch(console.error);
}

module.exports = EmailImporter;
