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
  { params }: { params: { id: string } }
) {
  try {
    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем информацию о пользователе
    const [members] = await connection.execute(`
      SELECT 
        member_id,
        name,
        members_display_name
      FROM members 
      WHERE member_id = ?
    `, [params.id]) as [any[], mysql.FieldPacket[]];

    await connection.end();

    if (!members || members.length === 0) {
      // Возвращаем дефолтный аватар
      return NextResponse.redirect('https://89.111.170.207/images/default-avatar.svg');
    }

    // Возвращаем дефолтный аватар для всех пользователей
    return NextResponse.redirect('https://89.111.170.207/images/default-avatar.svg');

  } catch (error) {
    console.error('Avatar API error:', error);
    // В случае ошибки возвращаем дефолтный аватар
    return NextResponse.redirect('https://89.111.170.207/images/default-avatar.svg');
  }
} 