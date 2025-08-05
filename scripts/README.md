# 📥 Импорт данных из IPB (Invision Power Board)

Этот набор скриптов предназначен для импорта данных из старого форума IPB в новую систему CloudMasters.

## 🎯 Что нужно подготовить

### 1. **Данные для импорта:**
- SQL дамп базы данных IPB
- Или доступ к базе данных IPB (хост, пользователь, пароль)
- Информация о версии IPB (3.x, 4.x)

### 2. **Информация о структуре:**
- Какие таблицы самые важные?
- Есть ли кастомные поля?
- Нужно ли сохранить старые URL?

### 3. **Требования к импорту:**
- Нужно ли сохранить все данные или только основные?
- Требуется ли миграция файлов (аватары, вложения)?
- Нужно ли сохранить даты создания/изменения?

### 4. **Технические детали:**
- Кодировка старой базы данных?
- Размер базы данных (примерно)?
- Есть ли ограничения по времени импорта?

## 🛠️ Установка и настройка

### 1. Подготовка базы данных IPB

Если у вас есть SQL дамп:
```bash
# Создаем базу данных для IPB
mysql -u root -p -e "CREATE DATABASE ipb_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Импортируем дамп
mysql -u root -p ipb_database < ipb_dump.sql
```

Если у вас есть доступ к работающей IPB базе:
```bash
# Создаем дамп
mysqldump -u username -p ipb_database > ipb_dump.sql
```

### 2. Настройка конфигурации

Отредактируйте файлы скриптов и укажите правильные параметры подключения:

**В `analyze-ipb-structure.js`:**
```javascript
const ipbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'ipb_database', // Измените на имя вашей IPB базы
  charset: 'utf8mb4'
};
```

**В `import-ipb-data.js`:**
```javascript
const ipbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'ipb_database', // Измените на имя вашей IPB базы
  charset: 'utf8mb4'
};
```

## 🚀 Использование

### 1. Анализ структуры данных

Сначала проанализируйте структуру IPB базы данных:

```bash
cd scripts
node analyze-ipb-structure.js
```

Этот скрипт покажет:
- Список всех таблиц в IPB
- Структуру ключевых таблиц
- Статистику пользователей и форума
- Маппинг полей между IPB и CloudMasters

### 2. Импорт данных

После анализа и настройки запустите импорт:

```bash
node import-ipb-data.js
```

Скрипт импортирует данные в следующем порядке:
1. **Группы пользователей** (member_groups)
2. **Пользователи** (members)
3. **Форумы** (forum_categories)
4. **Темы** (forum_topics)
5. **Сообщения** (forum_posts)
6. **Статистика** (обновление счетчиков)

## 📊 Структура данных

### Маппинг основных таблиц:

| IPB Таблица | CloudMasters Таблица | Описание |
|-------------|---------------------|----------|
| `members` | `members` | Пользователи |
| `member_groups` | `member_groups` | Группы пользователей |
| `forums` | `forum_categories` | Категории форума |
| `topics` | `forum_topics` | Темы |
| `posts` | `forum_posts` | Сообщения |

### Маппинг полей:

#### Пользователи (members):
- `member_id` → `id`
- `name` → `name`
- `members_display_name` → `members_display_name`
- `email` → `email`
- `member_group_id` → `member_group_id`
- `joined` → `joined`
- `last_visit` → `last_visit`
- `posts` → `posts`
- `title` → `title`
- `member_banned` → `member_banned`
- `ip_address` → `ip_address`

#### Группы пользователей (member_groups):
- `id` → `id`
- `name` → `name`
- `description` → `description`
- `permissions` → `permissions`

#### Форумы (forums → forum_categories):
- `id` → `id`
- `name` → `name`
- `description` → `description`
- `parent_id` → `parent_id`
- `position` → `position`

#### Темы (topics → forum_topics):
- `tid` → `id`
- `title` → `title`
- `forum_id` → `forum_id`
- `starter_id` → `author_id`
- `posts` → `posts_count`
- `views` → `views_count`
- `pinned` → `is_pinned`
- `state` → `is_locked`
- `start_date` → `created_at`
- `last_post` → `last_post_date`

#### Сообщения (posts → forum_posts):
- `pid` → `id`
- `topic_id` → `topic_id`
- `author_id` → `author_id`
- `post` → `content`
- `post_date` → `created_at`
- `ip_address` → `ip_address`

## ⚠️ Важные замечания

### 1. **Резервное копирование**
Перед импортом обязательно создайте резервную копию CloudMasters базы данных:

```bash
mysqldump -u root -p cloudmasters > cloudmasters_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. **Проверка данных**
После импорта проверьте:
- Количество импортированных записей
- Целостность связей между таблицами
- Работу сайта

### 3. **Обработка ошибок**
Скрипт обрабатывает ошибки и продолжает работу. Проверьте логи для выявления проблем.

### 4. **Производительность**
Для больших баз данных импорт может занять значительное время. Рекомендуется:
- Запускать импорт в нерабочее время
- Мониторить использование ресурсов
- Делать промежуточные резервные копии

## 🔧 Настройка для специфических случаев

### Импорт аватаров
Если нужно импортировать аватары пользователей, добавьте в скрипт:

```javascript
async importAvatars() {
  // Логика импорта аватаров
}
```

### Импорт вложений
Для импорта файловых вложений:

```javascript
async importAttachments() {
  // Логика импорта вложений
}
```

### Сохранение старых URL
Для сохранения SEO-ссылок создайте таблицу редиректов:

```sql
CREATE TABLE url_redirects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  old_url VARCHAR(500) NOT NULL,
  new_url VARCHAR(500) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 📞 Поддержка

При возникновении проблем:
1. Проверьте логи скриптов
2. Убедитесь в правильности конфигурации
3. Проверьте доступность баз данных
4. Создайте issue в репозитории с описанием проблемы 