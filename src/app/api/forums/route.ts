import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(request: NextRequest) {
  try {
    // Получаем список форумов из IPB структуры
    const forums = await query(`
      SELECT 
        id,
        name,
        description,
        topics,
        posts,
        last_post,
        last_poster_id,
        last_poster_name,
        position,
        password,
        sort_key,
        sort_order
      FROM cldforums 
      ORDER BY position ASC, id ASC
    `) as any[];

    // Формируем ответ
    const forumsList = forums.map((forum: any) => ({
      id: forum.id,
      name: forum.name,
      description: forum.description || '',
      topics_count: forum.topics || 0,
      posts_count: forum.posts || 0,
      last_post_at: forum.last_post ? new Date(forum.last_post * 1000).toISOString() : null,
      last_poster: {
        id: forum.last_poster_id,
        name: forum.last_poster_name
      },
      position: forum.position || 0,
      is_protected: !!forum.password,
      sort_key: forum.sort_key || 'last_post',
      sort_order: forum.sort_order || 'desc'
    }));

    return NextResponse.json(forumsList);

  } catch (error) {
    console.error('Ошибка получения форумов:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
