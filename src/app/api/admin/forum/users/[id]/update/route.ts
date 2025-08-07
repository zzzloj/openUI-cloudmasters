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

    const userId = parseInt(params.id);
    const body = await request.json();
    const { display_name, title, member_group_id } = body;

    // Подключение к базе данных
    const connection = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || 'Admin2024@',
      database: process.env.DB_NAME || 'cloudmasters'
    });

    // Проверяем, что пользователь существует
    const [existingUser] = await connection.execute(
      'SELECT member_id FROM cldmembers WHERE member_id = ?',
      [userId]
    );

    if (!Array.isArray(existingUser) || existingUser.length === 0) {
      await connection.end();
      return NextResponse.json({ error: 'User not found' }, { status: 404 });
    }

    // Обновляем пользователя
    await connection.execute(
      `UPDATE cldmembers 
       SET members_display_name = ?, 
           member_title = ?, 
           member_group_id = ?
       WHERE member_id = ?`,
      [display_name, title, member_group_id, userId]
    );

    await connection.end();

    return NextResponse.json({ 
      message: 'User updated successfully',
      user: {
        id: userId,
        display_name,
        title,
        member_group_id
      }
    });

  } catch (error) {
    console.error('Error updating user:', error);
    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    );
  }
}





