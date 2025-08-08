import { query } from './database';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import mysql from 'mysql2/promise';
import crypto from 'crypto';

export interface User {
  member_id: number;
  name: string;
  email: string;
  members_display_name: string;
  members_seo_name: string;
  member_group_id: number;
  posts: number;
  joined: number;
  last_activity: number;
  last_visit: number;
  ip_address: string;
  members_pass_hash: string;
  members_pass_salt: string;
  member_banned: number;
}

export interface AuthResult {
  success: boolean;
  user?: User;
  token?: string;
  error?: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  display_name?: string;
}

export interface LoginData {
  email: string;
  password: string;
}

// Генерация JWT токена
export function generateToken(user: User): string {
  return jwt.sign(
    { 
      id: user.member_id, 
      email: user.email, 
      display_name: user.members_display_name,
      group_id: user.member_group_id 
    },
    process.env.JWT_SECRET as string,
    { expiresIn: '7d' }
  );
}

// Верификация JWT токена
export function verifyToken(token: string): any {
  try {
    return jwt.verify(token, process.env.JWT_SECRET as string);
  } catch (error) {
    return null;
  }
}

// Регистрация пользователя (IPB style)
export async function registerUser(data: RegisterData): Promise<AuthResult> {
  try {
    // Проверяем, существует ли пользователь
    const existingUser = await query(`
      SELECT member_id FROM cldmembers WHERE email = ? OR name = ?
    `, [data.email, data.name]) as any[];

    if (existingUser.length > 0) {
      return { success: false, error: 'Пользователь с таким email или именем уже существует' };
    }

    // Генерируем соль для IPB
    const salt = Math.random().toString(36).substring(2, 7);
    const md5Password = crypto.createHash('md5').update(data.password).digest('hex');
    const md5Salt = crypto.createHash('md5').update(salt).digest('hex');
    const ipbHash = crypto.createHash('md5').update(md5Salt + md5Password).digest('hex');

    const now = Math.floor(Date.now() / 1000);
    const displayName = data.display_name || data.name;
    const seoName = data.name.toLowerCase().replace(/[^a-z0-9]/g, '-');

    // Создаем пользователя
    const result = await query(`
      INSERT INTO cldmembers (
        name, email, members_pass_hash, members_pass_salt,
        members_display_name, members_seo_name, member_group_id,
        joined, last_activity, last_visit, ip_address,
        posts, member_banned
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0)
    `, [
      data.name, data.email, ipbHash, salt,
      displayName, seoName, 2, // 2 = обычный пользователь
      now, now, now, '127.0.0.1'
    ]) as any;

    const userId = result.insertId;

    // Получаем созданного пользователя
    const user = await query(`
      SELECT * FROM cldmembers WHERE member_id = ?
    `, [userId]) as User[];

    if (user.length === 0) {
      return { success: false, error: 'Ошибка создания пользователя' };
    }

    const token = generateToken(user[0]);

    return {
      success: true,
      user: user[0],
      token
    };
  } catch (error) {
    console.error('Registration error:', error);
    return { success: false, error: 'Ошибка регистрации' };
  }
}

// Авторизация пользователя (IPB style)
export async function loginUser(data: LoginData): Promise<AuthResult> {
  try {
    // Используем прямое подключение к БД
    const dbConfig = {
      host: process.env.DB_HOST || 'localhost',
      user: process.env.DB_USER || 'root',
      password: process.env.DB_PASSWORD || '',
      database: process.env.DB_NAME || 'cloudmasters',
      charset: process.env.DB_CHARSET || 'utf8mb4'
    };
    
    const connection = await mysql.createConnection(dbConfig);
    
    try {
      // Ищем пользователя по email
      const [users] = await connection.execute(`
        SELECT * FROM cldmembers WHERE email = ?
      `, [data.email]) as [User[], any];

      if (users.length === 0) {
        return { success: false, error: 'Пользователь не найден' };
      }

      const user = users[0];

      // Проверяем, не забанен ли пользователь
      if (user.member_banned === 1) {
        return { success: false, error: 'Аккаунт заблокирован' };
      }

      // Проверяем пароль по алгоритму IPB 3.4
      const salt = user.members_pass_salt;
      const md5Password = crypto.createHash('md5').update(data.password).digest('hex');
      const md5Salt = crypto.createHash('md5').update(salt).digest('hex');
      const finalHash = crypto.createHash('md5').update(md5Salt + md5Password).digest('hex');

      if (finalHash !== user.members_pass_hash) {
        return { success: false, error: 'Неверный пароль' };
      }

      // Обновляем последнюю активность
      const now = Math.floor(Date.now() / 1000);
      await connection.execute(`
        UPDATE cldmembers SET last_activity = ?, last_visit = ? WHERE member_id = ?
      `, [now, now, user.member_id]);

      const token = generateToken(user);

      return {
        success: true,
        user,
        token
      };
    } finally {
      await connection.end();
    }
  } catch (error) {
    console.error('Login error:', error);
    return { success: false, error: 'Ошибка авторизации' };
  }
}

// Получение пользователя по токену
export async function getUserFromToken(token: string): Promise<User | null> {
  try {
    const decoded = verifyToken(token);
    if (!decoded) return null;

    const users = await query(`
      SELECT * FROM cldmembers WHERE member_id = ?
    `, [decoded.id]) as User[];

    return users.length > 0 ? users[0] : null;
  } catch (error) {
    console.error('Token verification error:', error);
    return null;
  }
}

// Обновление профиля пользователя
export async function updateUserProfile(userId: number, data: Partial<User>): Promise<AuthResult> {
  try {
    const updates: string[] = [];
    const values: any[] = [];

    if (data.members_display_name) {
      updates.push('members_display_name = ?');
      values.push(data.members_display_name);
    }

    if (data.email) {
      updates.push('email = ?');
      values.push(data.email);
    }

    if (updates.length === 0) {
      return { success: false, error: 'Нет данных для обновления' };
    }

    values.push(userId);

    await query(`
      UPDATE cldmembers SET ${updates.join(', ')} WHERE member_id = ?
    `, values);

    // Получаем обновленного пользователя
    const users = await query(`
      SELECT * FROM cldmembers WHERE member_id = ?
    `, [userId]) as User[];

    if (users.length === 0) {
      return { success: false, error: 'Пользователь не найден' };
    }

    const token = generateToken(users[0]);

    return {
      success: true,
      user: users[0],
      token
    };
  } catch (error) {
    console.error('Profile update error:', error);
    return { success: false, error: 'Ошибка обновления профиля' };
  }
}

// Изменение пароля
export async function changePassword(userId: number, oldPassword: string, newPassword: string): Promise<AuthResult> {
  try {
    // Получаем пользователя
    const users = await query(`
      SELECT * FROM cldmembers WHERE member_id = ?
    `, [userId]) as User[];

    if (users.length === 0) {
      return { success: false, error: 'Пользователь не найден' };
    }

    const user = users[0];

    // Проверяем старый пароль
    const salt = user.members_pass_salt;
    const oldHash = require('crypto').createHash('md5').update(oldPassword + salt).digest('hex');

    if (oldHash !== user.members_pass_hash) {
      return { success: false, error: 'Неверный текущий пароль' };
    }

    // Хешируем новый пароль
    const newSalt = Math.random().toString(36).substring(2, 7);
    const newHash = require('crypto').createHash('md5').update(newPassword + newSalt).digest('hex');

    // Обновляем пароль
    await query(`
      UPDATE cldmembers SET members_pass_hash = ?, members_pass_salt = ? WHERE member_id = ?
    `, [newHash, newSalt, userId]);

    return { success: true };
  } catch (error) {
    console.error('Password change error:', error);
    return { success: false, error: 'Ошибка изменения пароля' };
  }
} 