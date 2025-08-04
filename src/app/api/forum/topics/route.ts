import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const limit = parseInt(searchParams.get('limit') || '20');
    const offset = parseInt(searchParams.get('offset') || '0');
    const categoryId = searchParams.get('categoryId');

    let sql = `
      SELECT 
        t.id, t.title, t.forum_id, t.author_id, t.author_name, 
        t.posts_count, t.views_count, t.is_pinned, t.is_locked, 
        t.is_approved, t.created_at, t.last_post_date, t.last_poster_name,
        c.name as forum_name
      FROM forum_topics t
      LEFT JOIN forum_categories c ON t.forum_id = c.id
      WHERE t.is_approved = 1
    `;
    const params: (string | number)[] = [];

    if (categoryId) {
      sql += ' AND t.forum_id = ?';
      params.push(categoryId);
    }

    sql += ' ORDER BY t.is_pinned DESC, COALESCE(t.last_post_date, t.created_at) DESC LIMIT ' + limit + ' OFFSET ' + offset; // Fixed SQL parameter issue

    const topics = await query(sql, params) as any[];

    // Получаем общее количество тем
    let countSql = `
      SELECT COUNT(*) as total
      FROM forum_topics t
      WHERE t.is_approved = 1
    `;
    const countParams: (string | number)[] = [];

    if (categoryId) {
      countSql += ' AND t.forum_id = ?';
      countParams.push(categoryId);
    }

    const countResult = await query(countSql, countParams) as any[];
    const total = countResult[0]?.total || 0;

    return NextResponse.json({
      topics,
      pagination: {
        total,
        pages: Math.ceil(total / limit),
        current: Math.floor(offset / limit) + 1,
        limit,
        offset
      }
    });
  } catch (error) {
    console.error('Error fetching forum topics:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum topics' },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { title, forum_id, author_id, author_name, content } = body;

    if (!title || !forum_id || !author_id || !author_name || !content) {
      return NextResponse.json(
        { error: 'Missing required fields' },
        { status: 400 }
      );
    }

    // Проверяем, существует ли категория
    const categoryCheck = await query(`
      SELECT id, name FROM forum_categories WHERE id = ?
    `, [forum_id]) as any[];

    if (categoryCheck.length === 0) {
      return NextResponse.json(
        { error: 'Category not found' },
        { status: 404 }
      );
    }

    const now = Math.floor(Date.now() / 1000);

    // Создаем тему
    const topicSql = `
      INSERT INTO forum_topics (
        title, forum_id, author_id, author_name, 
        posts_count, views_count, is_pinned, is_locked, 
        is_approved, created_at, last_post_date, last_poster_name
      ) VALUES (?, ?, ?, ?, 1, 0, 0, 0, 1, ?, ?, ?)
    `;

    const topicResult = await query(topicSql, [
      title, forum_id, author_id, author_name, now, now, author_name
    ]);

    const topicId = (topicResult as any).insertId;

    // Создаем первый пост в теме
    const postSql = `
      INSERT INTO forum_posts (
        topic_id, author_id, author_name, content, 
        created_at, is_first_post, is_approved
      ) VALUES (?, ?, ?, ?, ?, 1, 1)
    `;

    await query(postSql, [
      topicId, author_id, author_name, content, now
    ]);

    // Обновляем счетчики в категории
    await query(`
      UPDATE forum_categories 
      SET topics_count = topics_count + 1,
          posts_count = posts_count + 1,
          last_post_date = ?,
          last_poster_name = ?
      WHERE id = ?
    `, [now, author_name, forum_id]);

    return NextResponse.json({
      success: true,
      topic_id: topicId
    });
  } catch (error) {
    console.error('Error creating forum topic:', error);
    return NextResponse.json(
      { error: 'Failed to create forum topic' },
      { status: 500 }
    );
  }
} 