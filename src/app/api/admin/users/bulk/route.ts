import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import jwt from 'jsonwebtoken';

export async function POST(request: NextRequest) {
  try {
    // Проверяем авторизацию
    const authHeader = request.headers.get('authorization');
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return NextResponse.json({ error: 'Unauthorized' }, { status: 401 });
    }

    const token = authHeader.substring(7);
    const decoded = jwt.verify(token, process.env.JWT_SECRET!) as any;
    
    // Проверяем права администратора
    if (decoded.member_group_id !== 4) {
      return NextResponse.json({ error: 'Forbidden' }, { status: 403 });
    }

    const { action, userIds, groupId, reason } = await request.json();

    if (!userIds || !Array.isArray(userIds) || userIds.length === 0) {
      return NextResponse.json({ error: 'No users selected' }, { status: 400 });
    }

    let sql = '';
    const params: any[] = [];

    switch (action) {
      case 'ban':
        sql = 'UPDATE cldmembers SET member_banned = 1 WHERE member_id IN (?)';
        params.push(userIds.join(','));
        break;

      case 'unban':
        sql = 'UPDATE cldmembers SET member_banned = 0 WHERE member_id IN (?)';
        params.push(userIds.join(','));
        break;

      case 'change_group':
        if (!groupId) {
          return NextResponse.json({ error: 'Group ID required' }, { status: 400 });
        }
        sql = 'UPDATE cldmembers SET member_group_id = ? WHERE member_id IN (?)';
        params.push(groupId, userIds.join(','));
        break;

      case 'delete':
        // Проверяем, что не удаляем администраторов
        const adminCheck = await query(
          'SELECT member_id FROM cldmembers WHERE member_id IN (?) AND member_group_id = 4',
          [userIds.join(',')]
        );
        
        if (adminCheck.length > 0) {
          return NextResponse.json({ error: 'Cannot delete administrators' }, { status: 400 });
        }

        // Удаляем пользователей (сначала удаляем связанные данные)
        await query('DELETE FROM cldposts WHERE author_id IN (?)', [userIds.join(',')]);
        await query('DELETE FROM cldtopics WHERE starter_id IN (?)', [userIds.join(',')]);
        sql = 'DELETE FROM cldmembers WHERE member_id IN (?)';
        params.push(userIds.join(','));
        break;

      default:
        return NextResponse.json({ error: 'Invalid action' }, { status: 400 });
    }

    const result = await query(sql, params);

    // Логируем действие
    const logSql = `
      INSERT INTO admin_logs (admin_id, action, target_type, target_ids, details, created_at)
      VALUES (?, ?, ?, ?, ?, NOW())
    `;
    
    await query(logSql, [
      decoded.member_id,
      action,
      'users',
      userIds.join(','),
      JSON.stringify({ reason, affected: result.affectedRows })
    ]);

    return NextResponse.json({
      success: true,
      message: `Successfully ${action} ${result.affectedRows} users`,
      affected: result.affectedRows
    });

  } catch (error) {
    console.error('Ошибка массовых операций:', error);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}
