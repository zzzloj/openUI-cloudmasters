import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  try {
    console.log('=== ТЕСТОВЫЙ API ===');
    const body = await request.json();
    console.log('Полученные данные:', body);
    
    return NextResponse.json({
      success: true,
      message: 'Тестовый API работает',
      received: body
    });
  } catch (error) {
    console.error('Ошибка в тестовом API:', error);
    return NextResponse.json(
      { success: false, error: 'Ошибка тестового API' },
      { status: 500 }
    );
  }
}
