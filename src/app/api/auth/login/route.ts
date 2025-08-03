import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";
import bcrypt from "bcryptjs";
import jwt from "jsonwebtoken";

// В реальном приложении здесь была бы база данных
// Для демонстрации используем простой объект
const users: any[] = [
  // Добавим тестового пользователя
  {
    id: "1",
    firstName: "Admin",
    lastName: "User",
    email: "admin@example.com",
    password: "$2a$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj4J/8JZqKGi", // "password123"
    role: "admin"
  }
];

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { emailOrUsername, password } = body;

    // Валидация
    if (!emailOrUsername || !password) {
      return NextResponse.json(
        { message: "Email/имя пользователя и пароль обязательны" },
        { status: 400 }
      );
    }

    // Поиск пользователя по email или username
    const user = users.find(u => u.email === emailOrUsername || u.username === emailOrUsername);
    if (!user) {
      return NextResponse.json(
        { message: "Неверный email/имя пользователя или пароль" },
        { status: 401 }
      );
    }

    // Проверка пароля
    const isPasswordValid = await bcrypt.compare(password, user.password);
    if (!isPasswordValid) {
      return NextResponse.json(
        { message: "Неверный email/имя пользователя или пароль" },
        { status: 401 }
      );
    }

    // Создание JWT токена
    const token = jwt.sign(
      {
        userId: user.id,
        email: user.email,
        role: user.role
      },
      JWT_SECRET,
      { expiresIn: "24h" }
    );

    // Создание ответа с куки
    const response = NextResponse.json(
      {
        message: "Вход выполнен успешно",
        user: {
          id: user.id,
          firstName: user.firstName,
          lastName: user.lastName,
          email: user.email,
          role: user.role
        }
      },
      { status: 200 }
    );

    // Установка куки
    response.headers.set(
      "Set-Cookie",
      cookie.serialize("authToken", token, {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        maxAge: 60 * 60 * 24, // 24 часа
        sameSite: "strict",
        path: "/",
      })
    );

    return response;

  } catch (error) {
    console.error("Ошибка входа:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 