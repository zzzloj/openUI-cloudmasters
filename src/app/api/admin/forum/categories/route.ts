import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import { verifyToken } from '@/lib/auth';

export async function GET(request: NextRequest) {
  try {
    console.log('=== Начало обработки запроса списка категорий ===');
    
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

    // Получаем список категорий с статистикой
    const categories = await query(`
      SELECT 
        id,
        name,
        description,
        position,
        topics,
        posts,
        last_id as last_topic_id,
        last_title as last_topic_title,
        last_poster_id,
        last_poster_name,
        last_post as last_post_date,
        newest_id,
        newest_title
      FROM cldforums 
      ORDER BY position ASC, id ASC
    `) as any[];

    console.log('Найдено категорий:', categories.length);

    // Форматируем данные для фронтенда
    const formattedCategories = categories.map(category => ({
      id: category.id,
      name: category.name,
      description: category.description || '',
      topics_count: category.topics || 0,
      posts_count: category.posts || 0,
      last_topic_id: category.last_topic_id,
      last_topic_title: category.last_topic_title,
      last_poster_id: category.last_poster_id,
      last_poster_name: category.last_poster_name,
      last_post_date: category.last_post_date ? new Date(category.last_post_date * 1000).toISOString() : null,
      position: category.position || 0,
      is_active: true // По умолчанию все категории активны
    }));

    console.log('=== Конец обработки запроса списка категорий ===');
    
    return NextResponse.json({ categories: formattedCategories });

  } catch (error) {
    console.error('Ошибка получения списка категорий:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
