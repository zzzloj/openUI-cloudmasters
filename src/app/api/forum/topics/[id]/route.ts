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
    const topicId = id;

    const connection = await mysql.createConnection(dbConfig);

    // Увеличиваем счетчик просмотров
    await connection.query(`
      UPDATE cldtopics 
      SET views = views + 1
      WHERE tid = ?
    `, [topicId]);

    const [topics] = await connection.query(`
      SELECT 
        t.tid as id, t.title, t.forum_id, t.starter_id as author_id, t.starter_name as author_name,
        t.posts, t.views, t.pinned as is_pinned, t.state as is_locked, t.approved as is_approved,
        t.start_date as created_at, t.last_post as last_post_date, t.last_poster_name,
        f.name as forum_name
      FROM cldtopics t
      LEFT JOIN cldforums f ON t.forum_id = f.id
      WHERE t.tid = ?
    `, [topicId]) as [any[], any];

    await connection.end();

    if (topics.length === 0) {
      return NextResponse.json(
        { error: 'Topic not found' },
        { status: 404 }
      );
    }

    const topic = topics[0];
    const topicData = {
      id: topic.id,
      title: topic.title,
      forum_id: topic.forum_id,
      author_id: topic.author_id,
      author_name: topic.author_name,
      posts_count: topic.posts || 0,
      views_count: topic.views || 0,
      is_pinned: topic.is_pinned === 1,
      is_locked: topic.is_locked === 'closed',
      is_approved: topic.is_approved === 1,
      created_at: topic.created_at,
      last_post_date: topic.last_post_date,
      last_poster_name: topic.last_poster_name || '',
      forum_name: topic.forum_name || ''
    };

    return NextResponse.json({ topic: topicData });
  } catch (error) {
    console.error('Error fetching forum topic:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum topic' },
      { status: 500 }
    );
  }
} 