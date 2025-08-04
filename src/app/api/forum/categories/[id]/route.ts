import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(
  request: NextRequest,
  { params }: { params: Promise<{ id: string }> }
) {
  try {
    const { id } = await params;
    const categoryId = id;

    const categories = await query(`
      SELECT 
        id, name, description, parent_id, position,
        topics_count, posts_count, last_post_date, last_poster_name
      FROM forum_categories 
      WHERE id = ?
    `, [categoryId]) as any[];

    if (categories.length === 0) {
      return NextResponse.json(
        { error: 'Category not found' },
        { status: 404 }
      );
    }

    return NextResponse.json({ category: categories[0] });
  } catch (error) {
    console.error('Error fetching forum category:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum category' },
      { status: 500 }
    );
  }
} 