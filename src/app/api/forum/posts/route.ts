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
    const { searchParams } = new URL(request.url);
    const topicId = searchParams.get('topic_id');
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '20');
    const offset = (page - 1) * limit;

    console.log('API Posts - Параметры:', { topicId, page, limit, offset });

    if (!topicId) {
      return NextResponse.json(
        { error: 'Topic ID is required' },
        { status: 400 }
      );
    }

    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем посты с пагинацией
    const sql = `
      SELECT pid, author_name, post, author_id, post_date 
      FROM cldposts 
      WHERE topic_id = ? 
      ORDER BY post_date ASC
      LIMIT ? OFFSET ?
    `;
    
    console.log('API Posts - SQL запрос:', sql);
    console.log('API Posts - Параметры запроса:', [topicId, limit, offset]);

    const [posts] = await connection.query(sql, [topicId, limit, offset]) as [any[], any];
    
    console.log('API Posts - Найдено постов:', posts.length);

    // Получаем общее количество постов
    const countSql = `SELECT COUNT(*) as total FROM cldposts WHERE topic_id = ?`;
    const [countResult] = await connection.query(countSql, [topicId]) as [any[], any];

    await connection.end();

    const total = countResult[0]?.total || 0;
    console.log('API Posts - Общее количество постов:', total);

    const postsList = posts.map((post: any) => ({
      id: post.pid,
      author_id: post.author_id,
      author_name: post.author_name,
      content: post.post || '',
      created_at: post.post_date
    }));

    console.log('API Posts - Обработано постов:', postsList.length);

    return NextResponse.json({
      posts: postsList,
      pagination: {
        total,
        pages: Math.ceil(total / limit),
        current: page,
        limit,
        offset
      }
    });

  } catch (error) {
    console.error('Error fetching forum posts:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum posts', details: String(error) },
      { status: 500 }
    );
  }
}
