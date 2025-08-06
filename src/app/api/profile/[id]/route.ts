import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    console.log('=== Начало обработки запроса профиля ===');
    const { id } = await params;
    console.log('ID пользователя:', id);
    
    console.log('Выполнение запроса...');
    // Получаем данные пользователя из IPB структуры
    const members = await query(`
      SELECT 
        member_id as id,
        name,
        members_display_name as display_name,
        email,
        joined,
        last_visit,
        member_group_id as group_id,
        member_banned as is_banned,
        posts,
        title,
        last_activity,
        ip_address,
        members_pass_hash,
        members_pass_salt
      FROM cldmembers 
      WHERE member_id = ?
    `, [id]) as any[];

    console.log('Результат запроса:', members.length, 'записей');
    if (members.length > 0) {
      console.log('Данные пользователя:', members[0]);
    }

    if (members.length === 0) {
      console.log('Пользователь не найден');
      return NextResponse.json({ error: 'Пользователь не найден' }, { status: 404 });
    }

    const member = members[0];

    // Формируем ответ
    const profile = {
      id: member.id,
      name: member.name,
      display_name: member.display_name,
      email: member.email,
      joined: new Date(member.joined * 1000).toISOString(),
      last_visit: member.last_visit ? new Date(member.last_visit * 1000).toISOString() : null,
      group_id: member.group_id,
      is_banned: member.is_banned === 1,
      posts: member.posts || 0,
      title: member.title || '',
      last_activity: member.last_activity ? new Date(member.last_activity * 1000).toISOString() : null,
      ip_address: member.ip_address,
      members_pass_hash: member.members_pass_hash,
      members_pass_salt: member.members_pass_salt
    };

    console.log('Профиль сформирован:', profile);
    console.log('=== Конец обработки запроса профиля ===');
    return NextResponse.json(profile);

  } catch (error) {
    console.error('Ошибка получения профиля:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
