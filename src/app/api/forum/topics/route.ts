import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

export async function GET(request: NextRequest) {
  try {
    console.log('=== Начало обработки запроса тем ===');
    const { searchParams } = new URL(request.url);
    const forumId = searchParams.get('forum_id');
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '20');
    const offset = (page - 1) * limit;

    console.log('Параметры запроса:', { forumId, page, limit, offset });

    const connection = await mysql.createConnection(dbConfig);

    let sql = `
      SELECT 
        tid as id,
        title,
        forum_id,
        starter_id as author_id,
        starter_name as author_name,
        posts,
        views,
        pinned as is_pinned,
        state as is_locked,
        approved as is_approved,
        start_date as created_at,
        last_post,
        last_poster_name,
        last_poster_id
      FROM cldtopics 
      WHERE approved = 1
    `;

    const params: any[] = [];

    if (forumId) {
      sql += ' AND forum_id = ?';
      params.push(forumId);
    }

    sql += ' ORDER BY pinned DESC, last_post DESC LIMIT ? OFFSET ?';
    params.push(limit, offset);

    console.log('SQL запрос:', sql);
    console.log('Параметры:', params);

    // Используем query вместо execute для избежания проблем с параметрами
    const [topics] = await connection.query(sql, params) as [any[], any];
    console.log('Результат запроса тем:', topics.length, 'записей');

    // Получаем общее количество тем для пагинации
    let countSql = 'SELECT COUNT(*) as total FROM cldtopics WHERE approved = 1';
    const countParams: any[] = [];

    if (forumId) {
      countSql += ' AND forum_id = ?';
      countParams.push(forumId);
    }

    console.log('SQL запрос для подсчета:', countSql);
    console.log('Параметры для подсчета:', countParams);

    const [countResult] = await connection.query(countSql, countParams) as [any[], any];
    const total = countResult[0]?.total || 0;
    console.log('Общее количество тем:', total);

    await connection.end();

    const topicsList = topics.map((topic: any) => ({
      id: topic.id,
      title: topic.title,
      forum_id: topic.forum_id,
      author_id: topic.author_id,
      author_name: topic.author_name,
      posts_count: topic.posts || 0,
      views_count: topic.views || 0,
      is_pinned: topic.is_pinned === 1,
      is_locked: topic.is_locked === 'closed',
      is_approved: topic.is_approved === 1,
      created_at: topic.created_at,
      last_post_date: topic.last_post,
      last_poster_name: topic.last_poster_name || '',
      last_poster_id: topic.last_poster_id || topic.author_id,
      forum_name: '' // Можно добавить JOIN для получения имени форума
    }));

    console.log('Список тем сформирован:', topicsList.length, 'тем');
    console.log('=== Конец обработки запроса тем ===');

    return NextResponse.json({
      topics: topicsList,
      pagination: {
        total,
        pages: Math.ceil(total / limit),
        current: page,
        limit,
        offset
      }
    });

  } catch (error) {
    console.error('Ошибка получения тем форума:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum topics', details: String(error) },
      { status: 500 }
    );
  }
}
