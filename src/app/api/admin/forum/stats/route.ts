import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import { verifyToken } from '@/lib/auth';

export async function GET(request: NextRequest) {
  try {
    console.log('=== Начало обработки запроса статистики форума ===');
    
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

    // Получаем общую статистику
    const totalUsers = await query(`
      SELECT COUNT(*) as count 
      FROM cldmembers
    `) as any[];

    const totalTopics = await query(`
      SELECT COUNT(*) as count 
      FROM cldtopics
    `) as any[];

    const totalPosts = await query(`
      SELECT COUNT(*) as count 
      FROM cldposts
    `) as any[];

    // Получаем активных пользователей (за последние 24 часа)
    const activeUsers = await query(`
      SELECT COUNT(DISTINCT member_id) as count 
      FROM cldmembers 
      WHERE last_activity > ?
    `, [Math.floor(Date.now() / 1000) - 86400]) as any[];

    // Получаем статистику за сегодня
    const today = Math.floor(Date.now() / 1000) - 86400;
    
    const newUsersToday = await query(`
      SELECT COUNT(*) as count 
      FROM cldmembers 
      WHERE joined > ?
    `, [today]) as any[];

    const newTopicsToday = await query(`
      SELECT COUNT(*) as count 
      FROM cldtopics 
      WHERE start_date > ?
    `, [today]) as any[];

    const newPostsToday = await query(`
      SELECT COUNT(*) as count 
      FROM cldposts 
      WHERE post_date > ?
    `, [today]) as any[];

    const stats = {
      totalUsers: totalUsers[0]?.count || 0,
      totalTopics: totalTopics[0]?.count || 0,
      totalPosts: totalPosts[0]?.count || 0,
      activeUsers: activeUsers[0]?.count || 0,
      newUsersToday: newUsersToday[0]?.count || 0,
      newTopicsToday: newTopicsToday[0]?.count || 0,
      newPostsToday: newPostsToday[0]?.count || 0
    };

    console.log('Статистика сформирована:', stats);
    console.log('=== Конец обработки запроса статистики форума ===');
    
    return NextResponse.json(stats);

  } catch (error) {
    console.error('Ошибка получения статистики форума:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
