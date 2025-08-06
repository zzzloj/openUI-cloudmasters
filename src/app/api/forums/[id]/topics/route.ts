import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    const { searchParams } = new URL(request.url);
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '20');
    const offset = (page - 1) * limit;

    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем темы форума из IPB структуры
    const [topics] = await connection.execute(`
      SELECT 
        t.tid as id,
        t.title,
        t.starter_id,
        t.starter_name,
        t.start_date,
        t.last_post,
        t.last_poster_id,
        t.last_poster_name,
        t.posts,
        t.views,
        t.forum_id,
        t.approved,
        t.pinned,
        t.state,
        t.poll_state,
        t.moved_to,
        t.topic_hasattach,
        p.pid as first_post_id,
        p.post_date as first_post_date,
        p.post as first_post_content
      FROM cldtopics t
      LEFT JOIN cldposts p ON t.tid = p.topic_id AND p.new_topic = 1
      WHERE t.forum_id = ? AND t.approved = 1
      ORDER BY t.pinned DESC, t.last_post DESC
      LIMIT ? OFFSET ?
    `, [id, limit, offset]) as [any[], any];

    // Получаем общее количество тем
    const [countResult] = await connection.execute(`
      SELECT COUNT(*) as total
      FROM cldtopics 
      WHERE forum_id = ? AND approved = 1
    `, [id]) as [any[], any];

    await connection.end();

    // Формируем ответ
    const topicsList = topics.map((topic: any) => ({
      id: topic.id,
      title: topic.title,
      author: {
        id: topic.starter_id,
        name: topic.starter_name
      },
      created_at: new Date(topic.start_date * 1000).toISOString(),
      last_post_at: topic.last_post ? new Date(topic.last_post * 1000).toISOString() : null,
      last_poster: {
        id: topic.last_poster_id,
        name: topic.last_poster_name
      },
      posts_count: topic.posts || 0,
      views_count: topic.views || 0,
      forum_id: topic.forum_id,
      is_pinned: topic.pinned ? true : false,
      is_locked: topic.state === 'closed',
      has_poll: topic.poll_state === 'open',
      is_moved: !!topic.moved_to,
      has_attachments: topic.topic_hasattach > 0,
      first_post: topic.first_post_id ? {
        id: topic.first_post_id,
        created_at: new Date(topic.first_post_date * 1000).toISOString(),
        content: topic.first_post_content ? topic.first_post_content.substring(0, 200) + '...' : ''
      } : null
    }));

    return NextResponse.json({
      topics: topicsList,
      pagination: {
        page,
        limit,
        total: countResult[0]?.total || 0,
        total_pages: Math.ceil((countResult[0]?.total || 0) / limit)
      }
    });

  } catch (error) {
    console.error('Ошибка получения тем:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
