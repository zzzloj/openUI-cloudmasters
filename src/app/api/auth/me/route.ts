import { NextRequest, NextResponse } from "next/server";
import jwt from "jsonwebtoken";

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
      
      // В реальном приложении здесь была бы проверка в базе данных
      const user = {
        id: decoded.userId,
        email: decoded.email,
        role: decoded.role
      };

      return NextResponse.json(
        { 
          message: "Авторизован",
          user
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