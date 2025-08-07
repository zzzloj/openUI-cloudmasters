import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  try {
    console.log('=== SIMPLE LOGIN API ===');
    const body = await request.json();
    const { email, password } = body;

    console.log('Email:', email);
    console.log('Password provided:', !!password);

    if (!email || !password) {
      return NextResponse.json(
        { success: false, error: 'Email и пароль обязательны' },
        { status: 400 }
      );
    }

    // Простая проверка для тестового пользователя
    if (email === 'test@example.com' && password === 'testpass123') {
      console.log('✓ Простая авторизация успешна');
      return NextResponse.json({
        success: true,
        user: {
          id: 2183,
          name: 'testuser',
          email: 'test@example.com',
          display_name: 'Test User'
        },
        token: 'simple-test-token-123'
      });
    }

    console.log('❌ Простая авторизация не удалась');
    return NextResponse.json(
      { success: false, error: 'Неверные учетные данные' },
      { status: 401 }
    );

  } catch (error) {
    console.error('Simple login API error:', error);
    return NextResponse.json(
      { success: false, error: 'Внутренняя ошибка сервера' },
      { status: 500 }
    );
  }
}



