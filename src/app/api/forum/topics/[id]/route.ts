import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    const topicId = id;

    // Увеличиваем счетчик просмотров
    await query(`
      UPDATE forum_topics 
      SET views_count = views_count + 1
      WHERE id = ?
    `, [topicId]);

    const topics = await query(`
      SELECT 
        t.id, t.title, t.forum_id, t.author_id, t.author_name,
        t.posts_count, t.views_count, t.is_pinned, t.is_locked, t.is_approved,
        t.created_at, t.last_post_date, t.last_poster_name,
        c.name as forum_name
      FROM forum_topics t
      LEFT JOIN forum_categories c ON t.forum_id = c.id
      WHERE t.id = ?
    `, [topicId]) as any[];

    if (topics.length === 0) {
      return NextResponse.json(
        { error: 'Topic not found' },
        { status: 404 }
      );
    }

    return NextResponse.json({ topic: topics[0] });
  } catch (error) {
    console.error('Error fetching forum topic:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum topic' },
      { status: 500 }
    );
  }
} 