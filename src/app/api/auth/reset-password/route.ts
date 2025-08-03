import { NextRequest, NextResponse } from "next/server";

// В реальном приложении здесь была бы база данных
// Для демонстрации используем простой объект
const users: any[] = [
  {
    id: "1",
    firstName: "Admin",
    lastName: "User",
    email: "admin@example.com",
    password: "$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/8JZqKGi",
    role: "admin"
  }
];

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { email } = body;

    // Валидация
    if (!email) {
      return NextResponse.json(
        { message: "Email обязателен" },
        { status: 400 }
      );
    }

    // Проверка email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return NextResponse.json(
        { message: "Неверный формат email" },
        { status: 400 }
      );
    }

    // Поиск пользователя
    const user = users.find(u => u.email === email);
    if (!user) {
      // Для безопасности не сообщаем, что пользователь не найден
      return NextResponse.json(
        { message: "Если пользователь с таким email существует, вы получите инструкции" },
        { status: 200 }
      );
    }

    // В реальном приложении здесь было бы:
    // 1. Генерация токена для сброса пароля
    // 2. Сохранение токена в базу данных с временем истечения
    // 3. Отправка email с ссылкой для сброса пароля

    console.log("Запрос на сброс пароля для:", email);

    // Имитация отправки email
    setTimeout(() => {
      console.log(`Email с инструкциями по сбросу пароля отправлен на: ${email}`);
    }, 1000);

    return NextResponse.json(
      { message: "Если пользователь с таким email существует, вы получите инструкции" },
      { status: 200 }
    );

  } catch (error) {
    console.error("Ошибка сброса пароля:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 