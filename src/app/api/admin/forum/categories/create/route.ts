import { NextRequest, NextResponse } from 'next/server';
import mysql from 'mysql2/promise';
import { verifyToken } from '@/lib/auth';

export async function POST(request: NextRequest) {
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

    const body = await request.json();
    const { name, description, position, is_active } = body;

    // Подключение к базе данных
    const connection = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || 'Admin2024@',
      database: process.env.DB_NAME || 'cloudmasters'
    });

    // Создаем новую категорию
    const [result] = await connection.execute(
      `INSERT INTO cldforums (name, description, position, active) 
       VALUES (?, ?, ?, ?)`,
      [name, description, position, is_active ? 1 : 0]
    );

    const categoryId = (result as any).insertId;

    await connection.end();

    return NextResponse.json({ 
      message: 'Category created successfully',
      category: {
        id: categoryId,
        name,
        description,
        position,
        is_active
      }
    });

  } catch (error) {
    console.error('Error creating category:', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}


