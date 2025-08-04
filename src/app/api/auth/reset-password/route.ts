import { NextRequest, NextResponse } from "next/server";
import { getUserByEmail, getUserByUsername, query } from "@/lib/database";
import { sendPasswordResetEmail, generateCode } from "@/lib/email";

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { emailOrUsername } = body;

    // Валидация
    if (!emailOrUsername) {
      return NextResponse.json(
        { message: "Email или имя пользователя обязательно" },
        { status: 400 }
      );
    }

    // Поиск пользователя по email или username
    let user = await getUserByEmail(emailOrUsername);
    if (!user) {
      user = await getUserByUsername(emailOrUsername);
    }

    if (!user) {
      // Для безопасности не сообщаем, что пользователь не найден
      return NextResponse.json(
        { message: "Если пользователь с таким email/именем пользователя существует, вы получите инструкции" },
        { status: 200 }
      );
    }

    // Проверка активации аккаунта
    if (!user.is_activated) {
      return NextResponse.json(
        { message: "Аккаунт не активирован. Сначала активируйте аккаунт." },
        { status: 400 }
      );
    }

    // Генерация кода восстановления
    const resetCode = generateCode(6);
    const resetExpires = Math.floor(Date.now() / 1000) + (60 * 60); // 1 час

    // Сохранение кода восстановления в базу данных
    await query(
      "UPDATE members SET reset_code = ?, reset_expires = ? WHERE member_id = ?",
      [resetCode, resetExpires, user.member_id]
    );

    // Отправка email с кодом восстановления
    const emailSent = await sendPasswordResetEmail(
      user.email,
      user.members_display_name || user.name,
      resetCode
    );

    if (!emailSent) {
      console.warn("Не удалось отправить email восстановления пароля на", user.email);
    }

    console.log("Запрос на сброс пароля для:", emailOrUsername);

    return NextResponse.json(
      { message: "Если пользователь с таким email/именем пользователя существует, вы получите инструкции" },
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