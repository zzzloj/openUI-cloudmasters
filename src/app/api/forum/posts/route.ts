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

    if (!topicId) {
      return NextResponse.json(
        { error: 'Topic ID is required' },
        { status: 400 }
      );
    }

    const connection = await mysql.createConnection(dbConfig);
    
    // Простой запрос для тестирования
    const [posts] = await connection.execute(`
      SELECT pid, author_name, post FROM cldposts WHERE topic_id = ? LIMIT 3
    `, [topicId]) as [any[], any];

    await connection.end();

    const postsList = posts.map((post: any) => ({
      id: post.pid,
      author_name: post.author_name,
      content: post.post ? post.post.substring(0, 100) + '...' : ''
    }));

    return NextResponse.json({
      posts: postsList,
      pagination: {
        total: posts.length,
        pages: 1,
        current: 1,
        limit: 3,
        offset: 0
      }
    });

  } catch (error) {
    console.error('Error fetching forum posts:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum posts' },
      { status: 500 }
    );
  }
}
