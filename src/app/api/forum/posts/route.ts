import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const topicId = searchParams.get('topicId');
    const limit = parseInt(searchParams.get('limit') || '20');
    const offset = parseInt(searchParams.get('offset') || '0');

    if (!topicId) {
      return NextResponse.json(
        { error: 'Topic ID is required' },
        { status: 400 }
      );
    }

    let sql = `
      SELECT 
        p.id, p.topic_id, p.author_id, p.author_name, p.content,
        p.created_at, p.is_first_post, p.is_approved,
        t.title as topic_title, t.forum_id,
        c.name as forum_name
      FROM forum_posts p
      LEFT JOIN forum_topics t ON p.topic_id = t.id
      LEFT JOIN forum_categories c ON t.forum_id = c.id
      WHERE p.topic_id = ? AND p.is_approved = 1
      ORDER BY p.created_at ASC
      LIMIT ${limit} OFFSET ${offset}
    `;

    const posts = await query(sql, [topicId]) as any[];

    // Получаем общее количество постов в теме
    const countSql = `
      SELECT COUNT(*) as total
      FROM forum_posts 
      WHERE topic_id = ? AND is_approved = 1
    `;
    const countResult = await query(countSql, [topicId]) as any[];
    const total = countResult[0]?.total || 0;

    return NextResponse.json({
      posts,
      pagination: {
        total,
        pages: Math.ceil(total / limit),
        current: Math.floor(offset / limit) + 1,
        limit,
        offset
      }
    });
  } catch (error) {
    console.error('Error fetching forum posts:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum posts' },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { topic_id, author_id, author_name, content } = body;

    if (!topic_id || !author_id || !author_name || !content) {
      return NextResponse.json(
        { error: 'Missing required fields' },
        { status: 400 }
      );
    }

    // Проверяем, существует ли тема
    const topicCheck = await query(`
      SELECT id, forum_id FROM forum_topics WHERE id = ?
    `, [topic_id]) as any[];

    if (topicCheck.length === 0) {
      return NextResponse.json(
        { error: 'Topic not found' },
        { status: 404 }
      );
    }

    const now = Math.floor(Date.now() / 1000);
    const isFirstPost = false; // Первый пост уже создается при создании темы

    // Создаем пост
    const insertSql = `
      INSERT INTO forum_posts (
        topic_id, author_id, author_name, content, 
        created_at, is_first_post, is_approved
      ) VALUES (?, ?, ?, ?, ?, ?, 1)
    `;

    const result = await query(insertSql, [
      topic_id, author_id, author_name, content, now, isFirstPost
    ]);

    // Обновляем счетчики в теме
    await query(`
      UPDATE forum_topics 
      SET posts_count = posts_count + 1, 
          last_post_date = ?,
          last_poster_name = ?
      WHERE id = ?
    `, [now, author_name, topic_id]);

    // Обновляем счетчики в категории
    await query(`
      UPDATE forum_categories 
      SET posts_count = posts_count + 1,
          last_post_date = ?,
          last_poster_name = ?
      WHERE id = ?
    `, [now, author_name, topicCheck[0].forum_id]);

    return NextResponse.json({
      success: true,
      post_id: (result as any).insertId
    });
  } catch (error) {
    console.error('Error creating forum post:', error);
    return NextResponse.json(
      { error: 'Failed to create forum post' },
      { status: 500 }
    );
  }
} 