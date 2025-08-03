import { NextRequest, NextResponse } from "next/server";
import jwt from "jsonwebtoken";
import { getUserByEmail } from "@/lib/database";

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

export async function GET(request: NextRequest) {
  try {
    // Получение токена из куки
    const authToken = request.cookies.get("authToken")?.value;

    if (!authToken) {
      return NextResponse.json(
        { message: "Не авторизован" },
        { status: 401 }
      );
    }

    // Проверка JWT токена
    try {
      const decoded = jwt.verify(authToken, JWT_SECRET) as any;
      
            // Получение актуальных данных пользователя из базы данных
      const user = await getUserByEmail(decoded.email);
      
      if (!user) {
        return NextResponse.json(
          { message: "Пользователь не найден" },
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

      // Определение роли пользователя
      const isAdmin = user.member_group_id === 4;
      const role = isAdmin ? "admin" : "user";

      return NextResponse.json(
        { 
          message: "Авторизован",
          user: {
            id: user.member_id,
            username: user.name,
            email: user.email,
            displayName: user.members_display_name,
            joined: user.joined,
            lastVisit: user.last_visit,
            posts: user.posts,
            memberGroupId: user.member_group_id,
            role: role,
            isAdmin: isAdmin
          }
        },
        { status: 200 }
      );

    } catch (jwtError) {
      return NextResponse.json(
        { message: "Недействительный токен" },
        { status: 401 }
      );
    }

  } catch (error) {
    console.error("Ошибка проверки аутентификации:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 