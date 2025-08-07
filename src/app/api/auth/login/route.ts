import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';
import crypto from 'crypto';
import jwt from 'jsonwebtoken';

export async function POST(request: NextRequest) {
  try {
    console.log('=== Начало обработки запроса авторизации ===');
    const body = await request.json();
    const { email, username, password } = body;

    // Поддерживаем авторизацию как по email, так и по username
    const loginField = email || username;
    
    console.log('Поле для входа:', loginField);
    console.log('Пароль предоставлен:', !!password);

    if (!loginField || !password) {
      console.log('Отсутствуют данные для входа или пароль');
      return NextResponse.json(
        { success: false, error: 'Email/имя пользователя и пароль обязательны' },
        { status: 400 }
      );
    }

    console.log('Начинаем авторизацию...');
    console.log('Email:', email);
    console.log('Пароль предоставлен:', !!password);
    
    try {
      // Подключение к БД
      const dbConfig = {
        host: 'localhost',
        user: 'root',
        password: 'Admin2024@',
        database: 'cloudmasters',
        charset: 'utf8mb4'
      };
      
      console.log('Подключаемся к БД...');
      const connection = await mysql.createConnection(dbConfig);
      console.log('✓ Подключение к БД установлено');
      
      try {
        // Ищем пользователя по email или username
        console.log('Ищем пользователя с полем:', loginField);
        const [users] = await connection.execute(`
          SELECT * FROM cldmembers WHERE email = ? OR name = ?
        `, [loginField, loginField]) as [any[], any];

        console.log('Найдено пользователей:', users.length);

        if (users.length === 0) {
          console.log('❌ Пользователь не найден');
          return NextResponse.json({ success: false, error: 'Пользователь не найден' }, { status: 401 });
        }

        const user = users[0];
        console.log('✓ Пользователь найден:', user.name);
        console.log('ID пользователя:', user.member_id);
        console.log('Хеш в БД:', user.members_pass_hash);
        console.log('Соль:', user.members_pass_salt);

        // Проверяем пароль по алгоритму IPB 3.4
        const salt = user.members_pass_salt;
        const md5Password = crypto.createHash('md5').update(password).digest('hex');
        const md5Salt = crypto.createHash('md5').update(salt).digest('hex');
        const finalHash = crypto.createHash('md5').update(md5Salt + md5Password).digest('hex');
        
        console.log('MD5 пароля:', md5Password);
        console.log('MD5 соли:', md5Salt);
        console.log('Вычисленный финальный хеш:', finalHash);
        console.log('Хеш в БД:', user.members_pass_hash);
        console.log('Хеши совпадают:', finalHash === user.members_pass_hash);

        if (finalHash !== user.members_pass_hash) {
          console.log('❌ Пароль неверный');
          return NextResponse.json({ success: false, error: 'Неверный пароль' }, { status: 401 });
        }

        console.log('✓ Пароль верный, генерируем токен...');

        // Генерируем токен
        const token = jwt.sign(
          { 
            id: user.member_id, 
            email: user.email, 
            display_name: user.members_display_name,
            group_id: user.member_group_id 
          },
          'cloudmasters-secret-key-2024',
          { expiresIn: '7d' }
        );

        console.log('✓ Токен сгенерирован');

        // Обновляем активность
        const now = Math.floor(Date.now() / 1000);
        await connection.execute(`
          UPDATE cldmembers SET last_activity = ?, last_visit = ? WHERE member_id = ?
        `, [now, now, user.member_id]);

        console.log('✓ Активность обновлена');
        console.log('🎉 Авторизация успешна!');
        
        return NextResponse.json({
          success: true,
          user: {
            id: user.member_id,
            name: user.name,
            email: user.email,
            members_display_name: user.members_display_name,
            member_group_id: user.member_group_id,
            is_activated: user.member_group_id > 0
          },
          token: token
        });

      } finally {
        await connection.end();
        console.log('✓ Соединение с БД закрыто');
      }

    } catch (loginError) {
      console.error('❌ Ошибка в авторизации:', loginError);
      console.error('Стек ошибки:', loginError.stack);
      return NextResponse.json(
        { success: false, error: 'Ошибка авторизации' },
        { status: 401 }
      );
    }
  } catch (error) {
    console.error('Login API error:', error);
    return NextResponse.json(
      { success: false, error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
} 