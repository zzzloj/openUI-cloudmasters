# Настройка базы данных CloudMasters

## Требования

- MySQL 8.0+
- Node.js 18+
- npm или yarn

## Установка зависимостей

```bash
npm install mysql2 @types/mysql
```

## Настройка базы данных

### 1. Создание базы данных

```sql
CREATE DATABASE cloudmasters CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Создание таблицы members

Выполните SQL скрипт `create_members_table.sql` в вашей базе данных.

### 3. Настройка конфигурации

1. Скопируйте файл примера:
```bash
cp src/lib/database.example.ts src/lib/database.ts
```

2. Отредактируйте `src/lib/database.ts` и замените:
   - `YOUR_MYSQL_PASSWORD` на ваш пароль MySQL
   - При необходимости измените `host`, `user`, `database`

### 4. Настройка пользователя MySQL

```sql
-- Создание пользователя (опционально)
CREATE USER 'cloudmasters'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON cloudmasters.* TO 'cloudmasters'@'localhost';
FLUSH PRIVILEGES;

-- Или изменение пароля root (не рекомендуется для продакшена)
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';
FLUSH PRIVILEGES;
```

## Тестирование подключения

Создайте файл `test-db-connection.js`:

```javascript
const mysql = require('mysql2/promise');

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'your_password',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

async function testConnection() {
  try {
    const connection = await mysql.createConnection(dbConfig);
    console.log('✅ Database connection successful!');
    await connection.end();
  } catch (error) {
    console.error('❌ Database connection failed:', error.message);
  }
}

testConnection();
```

Запустите:
```bash
node test-db-connection.js
```

## Безопасность

### Важные моменты:

1. **Никогда не коммитьте** файл `src/lib/database.ts` в Git
2. Файл уже добавлен в `.gitignore`
3. Используйте переменные окружения для продакшена
4. Регулярно меняйте пароли
5. Ограничьте доступ к базе данных только с localhost

### Для продакшена:

Создайте файл `.env`:
```
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=your_secure_password
DB_NAME=cloudmasters
```

И измените `database.ts`:
```typescript
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'cloudmasters',
  charset: 'utf8mb4'
};
```

## Устранение неполадок

### Ошибка "Access denied"
- Проверьте правильность пароля
- Убедитесь, что пользователь имеет права на базу данных
- Проверьте, что MySQL слушает на localhost

### Ошибка "Connection refused"
- Убедитесь, что MySQL запущен
- Проверьте порт (по умолчанию 3306)

### Ошибка "Database doesn't exist"
- Создайте базу данных `cloudmasters`
- Проверьте правильность имени базы данных в конфигурации

## Структура таблицы members

Основные поля:
- `member_id` - уникальный ID пользователя
- `name` - имя пользователя (логин)
- `email` - email пользователя
- `members_pass_hash` - хеш пароля
- `members_pass_salt` - соль для пароля
- `members_display_name` - отображаемое имя
- `joined` - дата регистрации (timestamp)
- `last_visit` - последний визит
- `member_banned` - статус бана (0/1)

Полная структура доступна в файле `create_members_table.sql`. 