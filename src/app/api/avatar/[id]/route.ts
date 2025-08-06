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
    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем данные пользователя из IPB структуры
    const [members] = await connection.execute(`
      SELECT 
        member_id,
        name,
        members_display_name,
        avatar_type,
        avatar_location,
        avatar_size
      FROM cldmembers
      WHERE member_id = ?
    `, [id]) as [any[], any];

    await connection.end();

    if (members.length === 0) {
      return NextResponse.json({ error: 'Пользователь не найден' }, { status: 404 });
    }

    const member = members[0];

    // Проверяем, есть ли у пользователя кастомный аватар
    if (member.avatar_type && member.avatar_location && member.avatar_type !== 'none') {
      // Если есть кастомный аватар, возвращаем его
      return NextResponse.redirect(`https://89.111.170.207/uploads/avatars/${member.avatar_location}`);
    } else {
      // Возвращаем дефолтный аватар
      return NextResponse.redirect('https://89.111.170.207/images/default-avatar.svg');
    }

  } catch (error) {
    console.error('Ошибка получения аватара:', error);
    // В случае ошибки возвращаем дефолтный аватар
    return NextResponse.redirect('https://89.111.170.207/images/default-avatar.svg');
  }
}
