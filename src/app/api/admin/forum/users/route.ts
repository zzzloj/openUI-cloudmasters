import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import { verifyToken } from '@/lib/auth';

export async function GET(request: NextRequest) {
  try {
    console.log('=== Начало обработки запроса списка пользователей ===');
    
    // Проверяем авторизацию
    const authHeader = request.headers.get('authorization');
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return NextResponse.json({ error: 'Требуется авторизация' }, { status: 401 });
    }

    const token = authHeader.substring(7);
    const decoded = verifyToken(token);
    
    if (!decoded) {
      return NextResponse.json({ error: 'Недействительный токен' }, { status: 401 });
    }

    // Проверяем права администратора
    const user = await query(`
      SELECT member_group_id 
      FROM cldmembers 
      WHERE member_id = ?
    `, [decoded.id]) as any[];

    if (user.length === 0 || user[0].member_group_id !== 4) {
      return NextResponse.json({ error: 'Недостаточно прав' }, { status: 403 });
    }

    console.log('Права администратора подтверждены');

    // Получаем список пользователей
    const users = await query(`
      SELECT 
        member_id as id,
        name,
        email,
        members_display_name as display_name,
        member_group_id,
        joined,
        last_activity,
        posts,
        member_banned as is_banned,
        title
      FROM cldmembers 
      ORDER BY member_id DESC
    `) as any[];

    console.log('Найдено пользователей:', users.length);

    // Форматируем данные для фронтенда
    const formattedUsers = users.map(user => ({
      id: user.id,
      name: user.name,
      email: user.email,
      display_name: user.display_name,
      member_group_id: user.member_group_id,
      joined: new Date(user.joined * 1000).toISOString(),
      last_activity: user.last_activity ? new Date(user.last_activity * 1000).toISOString() : null,
      posts: user.posts || 0,
      is_banned: user.is_banned === 1,
      title: user.title || ''
    }));

    console.log('=== Конец обработки запроса списка пользователей ===');
    
    return NextResponse.json({ users: formattedUsers });

  } catch (error) {
    console.error('Ошибка получения списка пользователей:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
