import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    
    // Получаем статистику пользователя
    const members = await query(`
      SELECT 
        member_id as id,
        name,
        members_display_name as display_name,
        joined,
        posts
      FROM cldmembers 
      WHERE member_id = ?
    `, [id]) as any[];

    if (members.length === 0) {
      return NextResponse.json({ error: 'Пользователь не найден' }, { status: 404 });
    }

    const member = members[0];

    // Формируем ответ
    const stats = {
      id: member.id,
      name: member.name,
      display_name: member.display_name,
      joined: new Date(member.joined * 1000).toISOString(),
      posts_count: member.posts || 0
    };

    return NextResponse.json(stats);

  } catch (error) {
    console.error('Ошибка получения статистики пользователя:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
} 