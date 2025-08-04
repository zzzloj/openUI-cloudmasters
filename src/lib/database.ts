import mysql from 'mysql2/promise';
import crypto from 'crypto';

// Конфигурация базы данных
const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@', // Пароль root пользователя MySQL
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

// Тип для пользователя
export interface Member {
  member_id: number;
  name: string;
  member_group_id: number;
  email: string;
  members_pass_hash: string;
  members_pass_salt: string;
  ip_address: string;
  joined: number;
  members_display_name: string;
  members_seo_name: string;
  members_l_display_name: string;
  members_l_username: string;
  last_visit: number;
  last_activity: number;
  failed_logins: string | null;
  failed_login_count: number;
  member_banned: number;
  posts: number;
  activation_code: string | null;
  activation_expires: number | null;
  is_activated: number;
}

// Создание пула соединений
const pool = mysql.createPool(dbConfig);

// Утилита для выполнения запросов
export async function query(sql: string, params?: any[]) {
  try {
    console.log('Executing SQL:', sql);
    console.log('With params:', params);
    const [rows] = await pool.execute(sql, params);
    console.log('Query result:', rows);
    return rows;
  } catch (error) {
    console.error('Database error:', error);
    throw error;
  }
}

// Утилита для получения одного пользователя
export async function getUserByEmail(email: string): Promise<Member | null> {
  const sql = 'SELECT * FROM members WHERE email = ?';
  const result = await query(sql, [email]) as Member[];
  return result.length > 0 ? result[0] : null;
}

// Утилита для получения пользователя по username
export async function getUserByUsername(username: string): Promise<Member | null> {
  const sql = 'SELECT * FROM members WHERE name = ?';
  const result = await query(sql, [username]) as Member[];
  return result.length > 0 ? result[0] : null;
}

// Утилита для создания нового пользователя
export async function createUser(userData: {
  name: string;
  email: string;
  members_pass_hash: string;
  members_pass_salt: string;
  ip_address: string;
  joined: number;
  members_display_name: string;
  members_seo_name: string;
  members_l_display_name: string;
  members_l_username: string;
  activation_code?: string;
  activation_expires?: number;
  is_activated?: number;
}) {
  const sql = `
    INSERT INTO members (
      name, email, members_pass_hash, members_pass_salt, ip_address, 
      joined, members_display_name, members_seo_name, 
      members_l_display_name, members_l_username,
      activation_code, activation_expires, is_activated,
      member_group_id, posts, warn_lastwarn, restrict_post, login_anonymous,
      mgroup_others, org_perm_id, member_login_key, member_login_key_expire,
      has_gallery, members_auto_dst, members_created_remote, members_disable_pm,
      failed_login_count, members_profile_views, member_banned, member_uploader,
      members_bitoptions, fb_uid, fb_emailhash, fb_lastsync, members_day_posts,
      vk_uid, twitter_id, twitter_token, twitter_secret, notification_cnt,
      tc_lastsync, fb_session, ipsconnect_id, gallery_perms
    ) VALUES (
      ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
      ?, ?, ?,
      2, 0, 0, '0', '0&0',
      '', '', '', 0,
      0, 1, 0, 0,
      0, 0, 0, 'default',
      0, 0, '', 0, '0,0',
      0, '', '', '', 0,
      0, '', 0, '1:1:1'
    )
  `;
  
  const result = await query(sql, [
    userData.name,
    userData.email,
    userData.members_pass_hash,
    userData.members_pass_salt,
    userData.ip_address,
    userData.joined,
    userData.members_display_name,
    userData.members_seo_name,
    userData.members_l_display_name,
    userData.members_l_username,
    userData.activation_code || null,
    userData.activation_expires || null,
    userData.is_activated || 0
  ]);
  
  return result;
}

// Утилита для обновления последнего визита
export async function updateLastVisit(memberId: number) {
  const sql = 'UPDATE members SET last_visit = ?, last_activity = ? WHERE member_id = ?';
  const now = Math.floor(Date.now() / 1000);
  await query(sql, [now, now, memberId]);
}

// Утилита для обновления неудачных попыток входа
export async function updateFailedLogins(memberId: number, failedLogins: string, failedLoginCount: number) {
  const sql = 'UPDATE members SET failed_logins = ?, failed_login_count = ? WHERE member_id = ?';
  await query(sql, [failedLogins, failedLoginCount, memberId]);
}

// Утилита для сброса неудачных попыток входа
export async function resetFailedLogins(memberId: number) {
  const sql = 'UPDATE members SET failed_logins = NULL, failed_login_count = 0 WHERE member_id = ?';
  await query(sql, [memberId]);
}

// Утилита для генерации случайной соли
export function generateSalt(): string {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  for (let i = 0; i < 5; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
}

// Утилита для хеширования пароля с солью
export function hashPassword(password: string, salt: string): string {
  return crypto.createHash('md5').update(password + salt).digest('hex');
}

// Утилита для проверки пароля
export function verifyPassword(password: string, salt: string, hash: string): boolean {
  const expectedHash = hashPassword(password, salt);
  return expectedHash === hash;
}
