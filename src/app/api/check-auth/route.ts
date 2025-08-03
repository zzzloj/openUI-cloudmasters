import { NextRequest, NextResponse } from "next/server";
import * as cookie from "cookie";
import jwt from "jsonwebtoken";

const JWT_SECRET = process.env.JWT_SECRET || "your-secret-key";

export async function GET(request: NextRequest) {
  return handleCheckAuth(request);
}

export async function POST(request: NextRequest) {
  return handleCheckAuth(request);
}

async function handleCheckAuth(request: NextRequest) {
  try {
    const cookieHeader = request.headers.get("cookie") || "";
    const cookies = cookie.parse(cookieHeader);
    const token = cookies.authToken;

    if (!token) {
      return NextResponse.json({ authenticated: false }, { status: 401 });
    }

    try {
      // Проверяем JWT токен
      const decoded = jwt.verify(token, JWT_SECRET) as any;
      
      if (decoded && decoded.userId) {
        return NextResponse.json({ 
          authenticated: true,
          user: {
            id: decoded.userId,
            email: decoded.email,
            isAdmin: decoded.isAdmin
          }
        }, { status: 200 });
      } else {
        return NextResponse.json({ authenticated: false }, { status: 401 });
      }
    } catch (jwtError) {
      console.error("JWT verification error:", jwtError);
      return NextResponse.json({ authenticated: false }, { status: 401 });
    }

  } catch (error) {
    console.error("Check auth error:", error);
    return NextResponse.json({ authenticated: false }, { status: 500 });
  }
}
