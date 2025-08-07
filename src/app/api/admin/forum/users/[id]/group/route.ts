import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import { verifyToken } from '@/lib/auth';

export async function POST(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    console.log('=== Начало обработки запроса изменения группы пользователя ===');
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

    const body = await request.json();
    const { group_id } = body;

    console.log('Новая группа:', group_id);

    // Проверяем валидность группы
    if (![1, 2, 3, 4].includes(group_id)) {
      return NextResponse.json({ error: 'Неверная группа пользователя' }, { status: 400 });
    }

    // Нельзя изменить группу другого администратора
    if (targetUser[0].member_group_id === 4 && decoded.id !== parseInt(id)) {
      return NextResponse.json({ error: 'Нельзя изменять группу других администраторов' }, { status: 403 });
    }

    // Обновляем группу пользователя
    await query(`
      UPDATE cldmembers 
      SET member_group_id = ? 
      WHERE member_id = ?
    `, [group_id, id]);

    console.log('Группа пользователя обновлена');
    console.log('=== Конец обработки запроса изменения группы пользователя ===');
    
    return NextResponse.json({ 
      success: true, 
      message: 'Группа пользователя изменена' 
    });

  } catch (error) {
    console.error('Ошибка изменения группы пользователя:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
