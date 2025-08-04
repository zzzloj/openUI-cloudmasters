# Настройка Email системы

## Переменные окружения

Создайте файл `.env.local` в корне проекта со следующими переменными:

```env
# SMTP Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=admin@cloudmasters.ru
SMTP_PASS=your-app-password

# JWT Secret
JWT_SECRET=your-secret-key-here

# Base URL
NEXT_PUBLIC_BASE_URL=http://89.111.170.207
```

## Настройка Gmail

1. Включите двухфакторную аутентификацию в Google аккаунте
2. Создайте пароль приложения:
   - Перейдите в настройки безопасности Google
   - Выберите "Пароли приложений"
   - Создайте новый пароль для "Почта"
   - Используйте этот пароль в `SMTP_PASS`

## Альтернативные SMTP провайдеры

### Yandex
```env
SMTP_HOST=smtp.yandex.ru
SMTP_PORT=587
SMTP_USER=admin@cloudmasters.ru
SMTP_PASS=your-app-password
```

### Mail.ru
```env
SMTP_HOST=smtp.mail.ru
SMTP_PORT=587
SMTP_USER=admin@cloudmasters.ru
SMTP_PASS=your-app-password
```

## Функциональность

### Активация аккаунта
- При регистрации отправляется email с кодом активации
- Код действителен 24 часа
- Ссылка для активации: `/auth/activate?code=XXX&email=user@example.com`

### Восстановление пароля
- При запросе восстановления отправляется email с кодом
- Код действителен 1 час
- Ссылка для восстановления: `/auth/reset-password?code=XXX&email=user@example.com`

## Безопасность

- Коды генерируются случайно (6 символов)
- Время истечения хранится в базе данных
- Неактивированные аккаунты не могут войти в систему
- Пароли хешируются с солью 