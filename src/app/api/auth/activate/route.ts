import { NextRequest, NextResponse } from "next/server";
import { query } from "@/lib/database";

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const code = searchParams.get('code');
    const email = searchParams.get('email');

    if (!code || !email) {
      return NextResponse.json(
        { message: "Неверная ссылка активации" },
        { status: 400 }
      );
    }

    // Проверяем код активации в базе данных
    const users = await query(
      "SELECT member_id, name, email, activation_code, activation_expires, is_activated FROM members WHERE email = ? AND activation_code = ?",
      [email, code]
    ) as any[];

    if (!users || users.length === 0) {
      return NextResponse.json(
        { message: "Неверный код активации" },
        { status: 400 }
      );
    }

    const user = users[0];
    const currentTime = Math.floor(Date.now() / 1000);

    // Проверяем, не истек ли срок действия кода
    if (user.activation_expires && user.activation_expires < currentTime) {
      return NextResponse.json(
        { message: "Срок действия кода активации истек. Зарегистрируйтесь заново." },
        { status: 400 }
      );
    }

    // Проверяем, не активирован ли уже аккаунт
    if (user.is_activated) {
      return NextResponse.json(
        { message: "Аккаунт уже активирован" },
        { status: 400 }
      );
    }

    // Активируем аккаунт
    await query(
      "UPDATE members SET is_activated = 1, activation_code = NULL, activation_expires = NULL WHERE member_id = ?",
      [user.member_id]
    );

    console.log(`Аккаунт ${user.email} успешно активирован`);

    return NextResponse.json(
      { 
        message: "Аккаунт успешно активирован! Теперь вы можете войти в систему.",
        user: {
          id: user.member_id,
          name: user.name,
          email: user.email
        }
      },
      { status: 200 }
    );

  } catch (error) {
    console.error("Ошибка активации аккаунта:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 