import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";

export async function POST(request: NextRequest) {
  return handleLogout();
}

export async function GET(request: NextRequest) {
  return handleLogout();
}

async function handleLogout() {
  try {
    // Создание HTML ответа с редиректом
    const htmlResponse = `
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выход из системы - CloudMasters</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: white;
        }
        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 1rem;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .spinner {
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
        }
        p {
            margin: 0;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Выход из системы</h1>
        <p>Выполняется выход из системы...</p>
        <p>Перенаправление на главную страницу...</p>
    </div>
    <script>
        // Удаляем куку аутентификации
        document.cookie = "authToken=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        
        // Перенаправляем на главную страницу через 2 секунды
        setTimeout(function() {
            window.location.href = "/";
        }, 2000);
    </script>
</body>
</html>`;

    // Создание ответа с HTML
    const response = new NextResponse(htmlResponse, {
      status: 200,
      headers: {
        "Content-Type": "text/html; charset=utf-8",
        // Удаление куки аутентификации
        "Set-Cookie": cookie.serialize("authToken", "", {
          httpOnly: true,
          secure: false,
          maxAge: 0,
          sameSite: "lax",
          path: "/",
        }),
      },
    });

    return response;

  } catch (error) {
    console.error("Ошибка выхода:", error);
    
    // В случае ошибки возвращаем простой HTML с редиректом
    const errorHtml = `
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка - CloudMasters</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: white;
        }
        .container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 1rem;
            backdrop-filter: blur(10px);
        }
        h1 { margin: 0 0 1rem 0; }
        a { color: white; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Произошла ошибка</h1>
        <p>Не удалось выполнить выход из системы.</p>
        <p><a href="/">Вернуться на главную</a></p>
    </div>
</body>
</html>`;

    return new NextResponse(errorHtml, {
      status: 500,
      headers: {
        "Content-Type": "text/html; charset=utf-8",
      },
    });
  }
} 