import { query } from './database';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';

export interface User {
  id: number;
  name: string;
  email: string;
  members_display_name: string;
  members_seo_name: string;
  member_group_id: number;
  posts: number;
  joined: number;
  is_activated: number;
  last_activity: number;
  last_visit: number;
  ip_address: string;
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
      id: user.id, 
      email: user.email, 
      display_name: user.members_display_name,
      group_id: user.member_group_id 
    },
    process.env.JWT_SECRET || 'cloudmasters-secret-key-2024',
    { expiresIn: '7d' }
  );
}

// Верификация JWT токена
export function verifyToken(token: string): any {
  try {
    return jwt.verify(token, process.env.JWT_SECRET || 'cloudmasters-secret-key-2024');
  } catch (error) {
    return null;
  }
}

// Регистрация пользователя (IPS4 style)
export async function registerUser(data: RegisterData): Promise<AuthResult> {
  try {
    // Проверяем, существует ли пользователь
    const existingUser = await query(`
      SELECT id FROM members WHERE email = ? OR name = ?
    `, [data.email, data.name]) as any[];

    if (existingUser.length > 0) {
      return { success: false, error: 'Пользователь с таким email или именем уже существует' };
    }

    // Хешируем пароль
    const saltRounds = 12;
    const hashedPassword = await bcrypt.hash(data.password, saltRounds);
    
    // Генерируем соль для совместимости с IPB
    const salt = Math.random().toString(36).substring(2, 15);
    const ipbHash = require('crypto').createHash('md5').update(hashedPassword + salt).digest('hex');

    const now = Math.floor(Date.now() / 1000);
    const displayName = data.display_name || data.name;
    const seoName = data.name.toLowerCase().replace(/[^a-z0-9]/g, '-');

    // Создаем пользователя
    const result = await query(`
      INSERT INTO members (
        name, email, members_pass_hash, members_pass_salt,
        members_display_name, members_seo_name, member_group_id,
        joined, last_activity, last_visit, ip_address,
        posts, is_activated
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1)
    `, [
      data.name, data.email, ipbHash, salt,
      displayName, seoName, 2, // 2 = обычный пользователь
      now, now, now, '127.0.0.1'
    ]) as any;

    const userId = result.insertId;

    // Получаем созданного пользователя
    const user = await query(`
      SELECT * FROM members WHERE id = ?
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

// Авторизация пользователя (IPS4 style)
export async function loginUser(data: LoginData): Promise<AuthResult> {
  try {
    // Ищем пользователя по email
    const users = await query(`
      SELECT * FROM members WHERE email = ?
    `, [data.email]) as User[];

    if (users.length === 0) {
      return { success: false, error: 'Пользователь не найден' };
    }

    const user = users[0];

    // Проверяем активацию
    if (user.is_activated !== 1) {
      return { success: false, error: 'Аккаунт не активирован' };
    }

    // Проверяем пароль (IPB style)
    const salt = user.members_pass_salt;
    const hashedPassword = require('crypto').createHash('md5').update(data.password + salt).digest('hex');

    if (hashedPassword !== user.members_pass_hash) {
      return { success: false, error: 'Неверный пароль' };
    }

    // Обновляем последнюю активность
    const now = Math.floor(Date.now() / 1000);
    await query(`
      UPDATE members SET last_activity = ?, last_visit = ? WHERE id = ?
    `, [now, now, user.id]);

    const token = generateToken(user);

    return {
      success: true,
      user,
      token
    };
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
      SELECT * FROM members WHERE id = ?
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
      UPDATE members SET ${updates.join(', ')} WHERE id = ?
    `, values);

    // Получаем обновленного пользователя
    const users = await query(`
      SELECT * FROM members WHERE id = ?
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
      SELECT * FROM members WHERE id = ?
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
    const newSalt = Math.random().toString(36).substring(2, 15);
    const newHash = require('crypto').createHash('md5').update(newPassword + newSalt).digest('hex');

    // Обновляем пароль
    await query(`
      UPDATE members SET members_pass_hash = ?, members_pass_salt = ? WHERE id = ?
    `, [newHash, newSalt, userId]);

    return { success: true };
  } catch (error) {
    console.error('Password change error:', error);
    return { success: false, error: 'Ошибка изменения пароля' };
  }
} 