import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import { verifyToken } from '@/lib/auth';

export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    console.log('=== Начало обработки запроса блокировки пользователя ===');
    const { id } = await params;
    console.log('ID пользователя:', id);
    
    // Проверяем авторизацию
    const authHeader = request.headers.get('authorization');
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return NextResponse.json({ error: 'Требуется авторизация' }, { status: 401 });
    }

    const token = authHeader.substring(7);
    const decoded = verifyToken(token);
    
    if (!decoded) {
      return NextResponse.json({ error: 'Недействительный токен' }, { status: 401 });
    }

    // Проверяем права администратора
    const admin = await query(`
      SELECT member_group_id 
      FROM cldmembers 
      WHERE member_id = ?
    `, [decoded.id]) as any[];

    if (admin.length === 0 || admin[0].member_group_id !== 4) {
      return NextResponse.json({ error: 'Недостаточно прав' }, { status: 403 });
    }

    // Проверяем, что пользователь существует
    const targetUser = await query(`
      SELECT member_id, name, member_group_id 
      FROM cldmembers 
      WHERE member_id = ?
    `, [id]) as any[];

    if (targetUser.length === 0) {
      return NextResponse.json({ error: 'Пользователь не найден' }, { status: 404 });
    }

    // Нельзя блокировать других администраторов
    if (targetUser[0].member_group_id === 4) {
      return NextResponse.json({ error: 'Нельзя блокировать администраторов' }, { status: 403 });
    }

    const body = await request.json();
    const { ban } = body;

    console.log('Действие:', ban ? 'блокировка' : 'разблокировка');

    // Обновляем статус блокировки
    await query(`
      UPDATE cldmembers 
      SET member_banned = ? 
      WHERE member_id = ?
    `, [ban ? 1 : 0, id]);

    console.log('Статус пользователя обновлен');
    console.log('=== Конец обработки запроса блокировки пользователя ===');
    
    return NextResponse.json({ 
      success: true, 
      message: ban ? 'Пользователь заблокирован' : 'Пользователь разблокирован' 
    });

  } catch (error) {
    console.error('Ошибка блокировки пользователя:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
