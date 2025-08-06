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
    const categoryId = id;

    const connection = await mysql.createConnection(dbConfig);

    const [categories] = await connection.query(`
      SELECT 
        id, name, description, position,
        topics, posts, last_post, last_poster_name
      FROM cldforums 
      WHERE id = ?
    `, [categoryId]) as [any[], any];

    await connection.end();

    if (categories.length === 0) {
      return NextResponse.json(
        { error: 'Category not found' },
        { status: 404 }
      );
    }

    const category = categories[0];
    
    // Формируем ответ в нужном формате
    const categoryData = {
      id: category.id,
      name: category.name,
      description: category.description || '',
      parent_id: null,
      position: category.position || 0,
      topics_count: category.topics || 0,
      posts_count: category.posts || 0,
      last_post_date: category.last_post,
      last_poster_name: category.last_poster_name || ''
    };

    return NextResponse.json({ category: categoryData });
  } catch (error) {
    console.error('Error fetching forum category:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum category' },
      { status: 500 }
    );
  }
} 