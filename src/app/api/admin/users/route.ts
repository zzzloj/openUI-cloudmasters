import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';
import jwt from 'jsonwebtoken';

export async function GET(request: NextRequest) {
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

    // Получаем параметры запроса
    const { searchParams } = new URL(request.url);
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '20');
    const search = searchParams.get('search') || '';
    const group = searchParams.get('group') || '';
    const status = searchParams.get('status') || '';

    const offset = (page - 1) * limit;

    // Формируем SQL запрос
    let sql = `
      SELECT 
        member_id,
        name,
        members_display_name,
        email,
        member_group_id,
        member_banned,
        joined,
        last_activity,
        (SELECT COUNT(*) FROM cldposts WHERE author_id = cldmembers.member_id) as posts,
        (SELECT COUNT(*) FROM cldtopics WHERE starter_id = cldmembers.member_id) as topics
      FROM cldmembers 
      WHERE 1=1
    `;

    const params: any[] = [];

    // Добавляем фильтры
    if (search) {
      sql += ` AND (members_display_name LIKE ? OR name LIKE ? OR email LIKE ?)`;
      params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }

    if (group) {
      sql += ` AND member_group_id = ?`;
      params.push(parseInt(group));
    }

    if (status === 'banned') {
      sql += ` AND member_banned = 1`;
    } else if (status === 'active') {
      sql += ` AND member_banned = 0`;
    }

    // Добавляем сортировку и пагинацию
    sql += ` ORDER BY joined DESC LIMIT ? OFFSET ?`;
    params.push(limit, offset);

    const users = await query(sql, params);

    // Получаем общее количество пользователей для пагинации
    let countSql = `
      SELECT COUNT(*) as total 
      FROM cldmembers 
      WHERE 1=1
    `;
    const countParams: any[] = [];

    if (search) {
      countSql += ` AND (members_display_name LIKE ? OR name LIKE ? OR email LIKE ?)`;
      countParams.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }

    if (group) {
      countSql += ` AND member_group_id = ?`;
      countParams.push(parseInt(group));
    }

    if (status === 'banned') {
      countSql += ` AND member_banned = 1`;
    } else if (status === 'active') {
      countSql += ` AND member_banned = 0`;
    }

    const totalResult = await query(countSql, countParams);
    const total = totalResult[0].total;

    return NextResponse.json({
      users,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    });

  } catch (error) {
    console.error('Ошибка получения пользователей:', error);
    return NextResponse.json({ error: 'Internal Server Error' }, { status: 500 });
  }
}
