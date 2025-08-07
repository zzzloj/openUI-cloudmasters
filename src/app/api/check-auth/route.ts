import { NextRequest, NextResponse } from 'next/server';
import { verifyToken } from '@/lib/auth';

export async function GET(request: NextRequest) {
  try {
    const token = request.headers.get('authorization')?.replace('Bearer ', '');
    
    if (!token) {
      return NextResponse.json({ authenticated: false }, { status: 401 });
    }

    const decoded = verifyToken(token);
    if (!decoded) {
      return NextResponse.json({ authenticated: false }, { status: 401 });
    }

    return NextResponse.json({ 
      authenticated: true, 
      user: {
        id: decoded.id,
        email: decoded.email,
        display_name: decoded.display_name,
        group_id: decoded.group_id
      }
    });
  } catch (error) {
    console.error('Check auth error:', error);
    return NextResponse.json({ authenticated: false }, { status: 401 });
  }
}
