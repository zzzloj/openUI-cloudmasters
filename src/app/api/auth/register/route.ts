import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";
import bcrypt from "bcryptjs";
import { 
  getUserByEmail, 
  getUserByUsername, 
  createUser, 
  generateSalt, 
  hashPassword 
} from "@/lib/database";

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
    const existingUserByEmail = await getUserByEmail(email);
    if (existingUserByEmail) {
      return NextResponse.json(
        { message: "Пользователь с таким email уже существует" },
        { status: 409 }
      );
    }

    // Проверка существования пользователя по username
    const existingUserByUsername = await getUserByUsername(username);
    if (existingUserByUsername) {
      return NextResponse.json(
        { message: "Пользователь с таким именем уже существует" },
        { status: 409 }
      );
    }

    // Генерация соли и хеширование пароля
    const salt = generateSalt();
    const hashedPassword = hashPassword(password, salt);

    // Получение IP адреса
    const ipAddress = request.headers.get('x-forwarded-for') || 
                     request.headers.get('x-real-ip') || 
                     '127.0.0.1';

    // Текущее время в Unix timestamp
    const joined = Math.floor(Date.now() / 1000);

    // Создание пользователя в базе данных
    const userData = {
      name: username,
      email,
      members_pass_hash: hashedPassword,
      members_pass_salt: salt,
      ip_address: ipAddress,
      joined,
      members_display_name: `${firstName} ${lastName}`,
      members_seo_name: username.toLowerCase().replace(/[^a-z0-9]/g, '-'),
      members_l_display_name: `${firstName} ${lastName}`,
      members_l_username: username
    };

    console.log("Попытка создания пользователя:", userData);
    
    const result = await createUser(userData);
    console.log("Результат создания пользователя:", result);

    console.log("Новый пользователь зарегистрирован:", {
      username: userData.name,
      email: userData.email,
      displayName: userData.members_display_name
    });

    return NextResponse.json(
      { 
        message: "Пользователь успешно зарегистрирован",
        user: {
          username: userData.name,
          firstName,
          lastName,
          email: userData.email,
          displayName: userData.members_display_name
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