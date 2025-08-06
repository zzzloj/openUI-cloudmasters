import { NextRequest, NextResponse } from "next/server";
import { query, generateSalt, hashPassword } from "@/lib/database";

interface User {
  member_id: number;
  name: string;
  email: string;
  reset_code: string;
  reset_expires: number;
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { email, code, newPassword } = body;

    // Валидация
    if (!email || !code || !newPassword) {
      return NextResponse.json(
        { message: "Все поля обязательны для заполнения" },
        { status: 400 }
      );
    }

    // Проверка длины пароля
    if (newPassword.length < 8) {
      return NextResponse.json(
        { message: "Пароль должен содержать минимум 8 символов" },
        { status: 400 }
      );
    }

    // Проверяем код восстановления в базе данных
    const result = await query(
      "SELECT member_id, name, email, reset_code, reset_expires FROM members WHERE email = ? AND reset_code = ?",
      [email, code]
    ) as User[];

    // Проверяем, что результат является массивом
    if (!Array.isArray(result) || result.length === 0) {
      return NextResponse.json(
        { message: "Неверный код восстановления" },
        { status: 400 }
      );
    }

    const user = result[0];
    const currentTime = Math.floor(Date.now() / 1000);

    // Проверяем, не истек ли срок действия кода
    if (user.reset_expires && user.reset_expires < currentTime) {
      return NextResponse.json(
        { message: "Срок действия кода восстановления истек. Запросите новый код." },
        { status: 400 }
      );
    }

    // Генерируем новую соль и хешируем новый пароль
    const salt = generateSalt();
    const hashedPassword = hashPassword(newPassword, salt);

    // Обновляем пароль и очищаем код восстановления
    await query(
      "UPDATE members SET members_pass_hash = ?, members_pass_salt = ?, reset_code = NULL, reset_expires = NULL WHERE member_id = ?",
      [hashedPassword, salt, user.member_id]
    );

    console.log(`Пароль для пользователя ${user.email} успешно обновлен`);

    return NextResponse.json(
      { 
        message: "Пароль успешно обновлен! Теперь вы можете войти в систему с новым паролем.",
        user: {
          id: user.member_id,
          name: user.name,
          email: user.email
        }
      },
      { status: 200 }
    );

  } catch (error) {
    console.error("Ошибка обновления пароля:", error);
    return NextResponse.json(
      { message: "Внутренняя ошибка сервера" },
      { status: 500 }
    );
  }
} 