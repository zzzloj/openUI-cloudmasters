import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";

export async function POST(request: NextRequest) {
  try {
    // Создание ответа
    const response = NextResponse.json(
      { message: "Выход выполнен успешно" },
      { status: 200 }
    );

    // Удаление куки аутентификации
    response.headers.set(
      "Set-Cookie",
      cookie.serialize("authToken", "", {
        httpOnly: true,
        secure: process.env.NODE_ENV === "production",
        maxAge: 0, // Немедленное удаление
        sameSite: "strict",
        path: "/",
      })
    );

    return response;

  } catch (error) {
    console.error("Ошибка выхода:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 