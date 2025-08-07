import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';
import crypto from 'crypto';

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

export async function POST(request: NextRequest) {
  try {
    console.log('=== Тестовый API авторизации ===');
    const body = await request.json();
    const { email, password } = body;

    console.log('Email:', email);
    console.log('Пароль предоставлен:', !!password);

    if (!email || !password) {
      return NextResponse.json(
        { success: false, error: 'Email и пароль обязательны' },
        { status: 400 }
      );
    }

    const connection = await mysql.createConnection(dbConfig);
    
    try {
      console.log('Подключение к БД установлено');
      
      // Ищем пользователя
      const [users] = await connection.execute(`
        SELECT * FROM cldmembers WHERE email = ?
      `, [email]) as [any[], any];

      console.log('Найдено пользователей:', users.length);

      if (users.length === 0) {
        console.log('Пользователь не найден');
        return NextResponse.json({ success: false, error: 'Пользователь не найден' }, { status: 401 });
      }

      const user = users[0];
      console.log('Пользователь найден:', user.name);
      console.log('Соль:', user.members_pass_salt);
      console.log('Хеш:', user.members_pass_hash);

      // Проверяем пароль
      const salt = user.members_pass_salt;
      const hashedPassword = crypto.createHash('md5').update(password + salt).digest('hex');
      console.log('Вычисленный хеш:', hashedPassword);
      console.log('Совпадают:', hashedPassword === user.members_pass_hash);

      if (hashedPassword !== user.members_pass_hash) {
        console.log('Пароль неверный');
        return NextResponse.json({ success: false, error: 'Неверный пароль' }, { status: 401 });
      }

      console.log('Авторизация успешна!');
      return NextResponse.json({
        success: true,
        user: {
          id: user.member_id,
          name: user.name,
          email: user.email,
          display_name: user.members_display_name
        }
      });

    } finally {
      await connection.end();
      console.log('Соединение с БД закрыто');
    }

  } catch (error) {
    console.error('Ошибка в тестовом API:', error);
    return NextResponse.json(
      { success: false, error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
} 