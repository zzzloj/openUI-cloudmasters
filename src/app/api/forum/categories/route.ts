import { NextRequest, NextResponse } from 'next/server';
import { query } from '@/lib/database';

export async function GET(request: NextRequest) {
  try {
    const categories = await query(`
      SELECT 
        id, name, description, parent_id, position,
        topics_count, posts_count, last_post_date, last_poster_name
      FROM forum_categories 
      ORDER BY position ASC, name ASC
    `) as any[];

    return NextResponse.json({ categories });
  } catch (error) {
    console.error('Error fetching forum categories:', error);
    return NextResponse.json(
      { error: 'Failed to fetch forum categories' },
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { name, description, parent_id = null, position = 0 } = body;

    if (!name) {
      return NextResponse.json(
        { error: 'Category name is required' },
        { status: 400 }
      );
    }

    const result = await query(`
      INSERT INTO forum_categories (name, description, parent_id, position)
      VALUES (?, ?, ?, ?)
    `, [name, description, parent_id, position]);

    return NextResponse.json(
      { message: 'Category created successfully', id: result.insertId },
      { status: 201 }
    );
  } catch (error) {
    console.error('Error creating forum category:', error);
    return NextResponse.json(
      { error: 'Failed to create forum category' },
      { status: 500 }
    );
  }
} 