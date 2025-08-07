import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';
import { verifyToken } from '@/lib/auth';

export async function PUT(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    // Проверяем аутентификацию
    const token = request.headers.get('authorization')?.replace('Bearer ', '');
    if (!token) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const user = await verifyToken(token);
    if (!user || user.member_group_id !== 4) {
      return NextResponse.json({ error: 'Forbidden' }, { status: 403 });
    }

    const categoryId = parseInt(params.id);
    const body = await request.json();
    const { name, description, position, is_active } = body;

    // Подключение к базе данных
    const connection = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || 'Admin2024@',
      database: process.env.DB_NAME || 'cloudmasters'
    });

    // Проверяем, что категория существует
    const [existingCategory] = await connection.execute(
      'SELECT id FROM cldforums WHERE id = ?',
      [categoryId]
    );

    if (!Array.isArray(existingCategory) || existingCategory.length === 0) {
      await connection.end();
      return NextResponse.json({ error: 'Category not found' }, { status: 404 });
    }

    // Обновляем категорию
    await connection.execute(
      `UPDATE cldforums 
       SET name = ?, 
           description = ?, 
           position = ?,
           active = ?
       WHERE id = ?`,
      [name, description, position, is_active ? 1 : 0, categoryId]
    );

    await connection.end();

    return NextResponse.json({ 
      message: 'Category updated successfully',
      category: {
        id: categoryId,
        name,
        description,
        position,
        is_active
      }
    });

  } catch (error) {
    console.error('Error updating category:', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}





