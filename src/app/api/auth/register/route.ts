import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";
import bcrypt from "bcryptjs";

// В реальном приложении здесь была бы база данных
// Для демонстрации используем простой объект
const users: any[] = [];

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { username, firstName, lastName, email, password, securityAnswer, agreeToTerms } = body;

    // Валидация
    if (!username || !firstName || !lastName || !email || !password || !securityAnswer) {
      return NextResponse.json(
        { message: "Все поля обязательны для заполнения" },
        { status: 400 }
      );
    }

    if (!agreeToTerms) {
      return NextResponse.json(
        { message: "Необходимо согласиться с правилами портала" },
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

    // Проверка длины пароля
    if (password.length < 8) {
      return NextResponse.json(
        { message: "Пароль должен содержать минимум 8 символов" },
        { status: 400 }
      );
    }

    // Проверка существования пользователя по email
    const existingUserByEmail = users.find(user => user.email === email);
    if (existingUserByEmail) {
      return NextResponse.json(
        { message: "Пользователь с таким email уже существует" },
        { status: 409 }
      );
    }

    // Проверка существования пользователя по username
    const existingUserByUsername = users.find(user => user.username === username);
    if (existingUserByUsername) {
      return NextResponse.json(
        { message: "Пользователь с таким именем уже существует" },
        { status: 409 }
      );
    }

    // Хеширование пароля
    const hashedPassword = await bcrypt.hash(password, 12);

    // Создание пользователя
    const newUser = {
      id: Date.now().toString(),
      username,
      firstName,
      lastName,
      email,
      password: hashedPassword,
      securityAnswer,
      createdAt: new Date().toISOString(),
      role: "user"
    };

    users.push(newUser);

    // В реальном приложении здесь было бы сохранение в базу данных
    console.log("Новый пользователь зарегистрирован:", {
      id: newUser.id,
      username: newUser.username,
      email: newUser.email,
      firstName: newUser.firstName,
      lastName: newUser.lastName
    });

    return NextResponse.json(
      { 
        message: "Пользователь успешно зарегистрирован",
        user: {
          id: newUser.id,
          username: newUser.username,
          firstName: newUser.firstName,
          lastName: newUser.lastName,
          email: newUser.email
        }
      },
      { status: 201 }
    );

  } catch (error) {
    console.error("Ошибка регистрации:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 