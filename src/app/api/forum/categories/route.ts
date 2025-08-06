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
    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем форумы из IPB структуры как категории
    const [forums] = await connection.execute(`
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
    `) as [any[], any];

    await connection.end();

    // Формируем ответ в формате категорий
    const categories = forums.map((forum: any) => ({
      id: forum.id,
      name: forum.name,
      description: forum.description || '',
      parent_id: null, // В IPB нет иерархии категорий
      position: forum.position || 0,
      topics_count: forum.topics || 0,
      posts_count: forum.posts || 0,
      last_post_date: forum.last_post ? new Date(forum.last_post * 1000).toISOString() : null,
      last_poster_name: forum.last_poster_name || '',
      is_protected: !!forum.password,
      sort_key: forum.sort_key || 'last_post',
      sort_order: forum.sort_order || 'desc'
    }));

    return NextResponse.json({ categories });

  } catch (error) {
    console.error('Error fetching forum categories:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum categories' },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { name, description, parent_id = null, position = 0 } = body;

    if (!name) {
      return NextResponse.json(
        { error: 'Category name is required' },
        { status: 400 }
      );
    }

    const connection = await mysql.createConnection(dbConfig);
    
    const [result] = await connection.execute(`
      INSERT INTO cldforums (name, description, position, topics, posts)
      VALUES (?, ?, ?, 0, 0)
    `, [name, description, position]) as [any, any];

    await connection.end();

    return NextResponse.json(
      { message: 'Category created successfully', id: result.insertId },
      { status: 201 }
    );
  } catch (error) {
    console.error('Error creating forum category:', error);
    return NextResponse.json(
      { error: 'Failed to create forum category' },
      { status: 500 }
    );
  }
}
