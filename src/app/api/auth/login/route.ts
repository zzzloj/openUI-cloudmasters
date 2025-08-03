import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";
import jwt from "jsonwebtoken";
import { 
  getUserByEmail, 
  getUserByUsername, 
  verifyPassword, 
  updateLastVisit, 
  updateFailedLogins, 
  resetFailedLogins,
  Member
} from "@/lib/database";

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    console.log('Login request body:', body);
    const { emailOrUsername, password } = body;
    console.log('Extracted data:', { emailOrUsername, password });

    // Валидация
    if (!emailOrUsername || !password) {
      return NextResponse.json(
        { message: "Email/имя пользователя и пароль обязательны" },
        { status: 400 }
      );
    }

    // Поиск пользователя по email или username
    console.log('Searching for user:', emailOrUsername);
    let user: Member | null = await getUserByEmail(emailOrUsername);
    console.log('User by email:', user ? 'found' : 'not found');
    
    if (!user) {
      user = await getUserByUsername(emailOrUsername);
      console.log('User by username:', user ? 'found' : 'not found');
    }

    if (!user) {
      console.log('User not found');
      return NextResponse.json(
        { message: "Неверный email/имя пользователя или пароль" },
        { status: 401 }
      );
    }
    
    console.log('User found:', user.member_id, user.name, user.email);

    // Проверка пароля
    const isPasswordValid = verifyPassword(password, user.members_pass_salt, user.members_pass_hash);
    if (!isPasswordValid) {
      // Обновляем счетчик неудачных попыток входа
      const failedLogins = user.failed_logins || '';
      const failedLoginCount = (user.failed_login_count || 0) + 1;
      const newFailedLogins = `${Date.now()},${request.headers.get('x-forwarded-for') || 'unknown'}\n${failedLogins}`;
      
      await updateFailedLogins(user.member_id, newFailedLogins, failedLoginCount);

      return NextResponse.json(
        { message: "Неверный email/имя пользователя или пароль" },
        { status: 401 }
      );
    }

    // Проверка бана пользователя
    if (user.member_banned) {
      return NextResponse.json(
        { message: "Ваш аккаунт заблокирован" },
        { status: 403 }
      );
    }

    // Сброс неудачных попыток входа при успешном входе
    await resetFailedLogins(user.member_id);

    // Обновление времени последнего визита
    await updateLastVisit(user.member_id);

    // Создание JWT токена
    const token = jwt.sign(
      {
        userId: user.member_id,
        email: user.email,
        username: user.name,
        displayName: user.members_display_name
      },
      JWT_SECRET,
      { expiresIn: "24h" }
    );

    // Создание ответа с куки
    const response = NextResponse.json(
      {
        message: "Вход выполнен успешно",
        user: {
          id: user.member_id,
          username: user.name,
          email: user.email,
          displayName: user.members_display_name,
          joined: user.joined
        }
      },
      { status: 200 }
    );

    // Установка куки
    response.headers.set(
      "Set-Cookie",
      cookie.serialize("authToken", token, {
        httpOnly: true,
        secure: false, // Изменено для работы без HTTPS
        maxAge: 60 * 60 * 24, // 24 часа
        sameSite: "lax", // Изменено для лучшей совместимости
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