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
    const { searchParams } = new URL(request.url);
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '20');
    const offset = (page - 1) * limit;

    const connection = await mysql.createConnection(dbConfig);
    
    // Получаем сообщения темы из IPB структуры
    const [posts] = await connection.execute(`
      SELECT 
        p.pid as id,
        p.author_id,
        p.author_name,
        p.post_date,
        p.post,
        p.use_sig,
        p.use_emo,
        p.ip_address,
        p.queued,
        p.post_key,
        p.post_htmlstate,
        p.post_edit_reason,
        p.edit_time,
        p.edit_name,
        p.append_edit,
        t.tid as topic_id,
        t.title as topic_title,
        t.forum_id,
        f.name as forum_name
      FROM cldposts p
      JOIN cldtopics t ON p.topic_id = t.tid
      JOIN cldforums f ON t.forum_id = f.id
      WHERE p.topic_id = ? AND p.queued = 0
      ORDER BY p.post_date ASC
      LIMIT ? OFFSET ?
    `, [id, limit, offset]) as [any[], any];

    // Получаем общее количество сообщений
    const [countResult] = await connection.execute(`
      SELECT COUNT(*) as total
      FROM cldposts 
      WHERE topic_id = ? AND queued = 0
    `, [id]) as [any[], any];

    // Получаем информацию о теме
    const [topicResult] = await connection.execute(`
      SELECT 
        tid as id,
        title,
        starter_id,
        starter_name,
        start_date,
        last_post,
        last_poster_id,
        last_poster_name,
        posts,
        views,
        forum_id,
        approved,
        pinned,
        state,
        poll_state
      FROM cldtopics 
      WHERE tid = ?
    `, [id]) as [any[], any];

    await connection.end();

    if (topicResult.length === 0) {
      return NextResponse.json({ error: 'Тема не найдена' }, { status: 404 });
    }

    const topic = topicResult[0];

    // Формируем ответ
    const postsList = posts.map((post: any) => ({
      id: post.id,
      author: {
        id: post.author_id,
        name: post.author_name
      },
      created_at: new Date(post.post_date * 1000).toISOString(),
      content: post.post || '',
      use_signature: post.use_sig ? true : false,
      use_emoticons: post.use_emo ? true : false,
      ip_address: post.ip_address,
      post_key: post.post_key,
      html_state: post.post_htmlstate,
      is_edited: post.edit_time ? true : false,
      edited_at: post.edit_time ? new Date(post.edit_time * 1000).toISOString() : null,
      edited_by: post.edit_name,
      edit_reason: post.post_edit_reason,
      is_append_edit: post.append_edit ? true : false,
      topic: {
        id: post.topic_id,
        title: post.topic_title
      },
      forum: {
        id: post.forum_id,
        name: post.forum_name
      }
    }));

    return NextResponse.json({
      topic: {
        id: topic.id,
        title: topic.title,
        author: {
          id: topic.starter_id,
          name: topic.starter_name
        },
        created_at: new Date(topic.start_date * 1000).toISOString(),
        last_post_at: topic.last_post ? new Date(topic.last_post * 1000).toISOString() : null,
        last_poster: {
          id: topic.last_poster_id,
          name: topic.last_poster_name
        },
        posts_count: topic.posts || 0,
        views_count: topic.views || 0,
        forum_id: topic.forum_id,
        is_pinned: topic.pinned ? true : false,
        is_locked: topic.state === 'closed',
        has_poll: topic.poll_state === 'open'
      },
      posts: postsList,
      pagination: {
        page,
        limit,
        total: countResult[0]?.total || 0,
        total_pages: Math.ceil((countResult[0]?.total || 0) / limit)
      }
    });

  } catch (error) {
    console.error('Ошибка получения сообщений:', error);
    return NextResponse.json(
      { error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}
