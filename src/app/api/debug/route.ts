import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  try {
    console.log('=== DEBUG API ===');
    const body = await request.json();
    console.log('Тело запроса:', body);
    
    return NextResponse.json({
      success: true,
      message: 'Debug API работает',
      received: body
    });
    
  } catch (error) {
    console.error('Debug API error:', error);
    return NextResponse.json(
      { success: false, error: 'Debug API error' },
      { status: 500 }
    );
  }
}











