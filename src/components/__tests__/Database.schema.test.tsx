import { describe, it, expect, beforeAll, afterAll } from '@jest/globals';
import mysql from 'mysql2/promise';

const dbConfig = {
  host: 'localhost',
  user: 'root',
  password: 'Admin2024@',
  database: 'cloudmasters',
  charset: 'utf8mb4'
};

describe('Database Schema Tests', () => {
  let connection: mysql.Connection;

  beforeAll(async () => {
    connection = await mysql.createConnection(dbConfig);
  });

  afterAll(async () => {
    if (connection) {
      await connection.end();
    }
  });

  describe('IPB 3 Tables Structure', () => {
    it('should have cldforums table with correct structure', async () => {
      const [rows] = await connection.execute(`
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_name = 'cldforums' AND table_schema = 'cloudmasters'
      `);
      
      expect(rows[0].count).toBeGreaterThan(0);
    });

    it('should have cldtopics table with correct structure', async () => {
      const [rows] = await connection.execute(`
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_name = 'cldtopics' AND table_schema = 'cloudmasters'
      `);
      
      expect(rows[0].count).toBeGreaterThan(0);
    });

    it('should have cldposts table with correct structure', async () => {
      const [rows] = await connection.execute(`
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_name = 'cldposts' AND table_schema = 'cloudmasters'
      `);
      
      expect(rows[0].count).toBeGreaterThan(0);
    });
  });

  describe('Forum Tables Structure', () => {
    it('should have forum_categories table with correct structure', async () => {
      const [rows] = await connection.execute(`
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_name = 'forum_categories' AND table_schema = 'cloudmasters'
      `);
      
      expect(rows[0].count).toBeGreaterThan(0);
    });

    it('should have forum_topics table with correct structure', async () => {
      const [rows] = await connection.execute(`
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_name = 'forum_topics' AND table_schema = 'cloudmasters'
      `);
      
      expect(rows[0].count).toBeGreaterThan(0);
    });

    it('should have forum_posts table with correct structure', async () => {
      const [rows] = await connection.execute(`
        SELECT COUNT(*) as count 
        FROM information_schema.columns 
        WHERE table_name = 'forum_posts' AND table_schema = 'cloudmasters'
      `);
      
      expect(rows[0].count).toBeGreaterThan(0);
    });
  });

  describe('IPB 3 Data Import', () => {
    it('should have test data in cldforums table', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM cldforums');
      expect(rows[0].count).toBe(5);
    });

    it('should have test data in cldtopics table', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM cldtopics');
      expect(rows[0].count).toBe(4);
    });

    it('should have test data in cldposts table', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM cldposts');
      expect(rows[0].count).toBe(7);
    });

    it('should have imported data in forum_categories table', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM forum_categories');
      expect(rows[0].count).toBe(5);
    });

    it('should have imported data in forum_topics table', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM forum_topics');
      expect(rows[0].count).toBe(4);
    });

    it('should have imported data in forum_posts table', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM forum_posts');
      expect(rows[0].count).toBe(7);
    });
  });

  describe('Data Integrity', () => {
    it('should have matching category names between IPB 3 and forum tables', async () => {
      const [ipbCategories] = await connection.execute(`
        SELECT name FROM cldforums ORDER BY id
      `);
      const [forumCategories] = await connection.execute(`
        SELECT name FROM forum_categories ORDER BY id
      `);
      
      expect(ipbCategories.length).toBe(forumCategories.length);
      
      for (let i = 0; i < ipbCategories.length; i++) {
        expect(ipbCategories[i].name).toBe(forumCategories[i].name);
      }
    });

    it('should have matching topic titles between IPB 3 and forum tables', async () => {
      const [ipbTopics] = await connection.execute(`
        SELECT title FROM cldtopics ORDER BY tid
      `);
      const [forumTopics] = await connection.execute(`
        SELECT title FROM forum_topics ORDER BY id
      `);
      
      expect(ipbTopics.length).toBe(forumTopics.length);
      
      for (let i = 0; i < ipbTopics.length; i++) {
        expect(ipbTopics[i].title).toBe(forumTopics[i].title);
      }
    });

    it('should have correct forum_id references in topics', async () => {
      const [topics] = await connection.execute(`
        SELECT ft.id, ft.title, ft.forum_id, fc.name as forum_name
        FROM forum_topics ft
        JOIN forum_categories fc ON ft.forum_id = fc.id
        ORDER BY ft.id
      `);
      
      expect(topics.length).toBe(4);
      
      // Проверяем, что тема "Добро пожаловать" находится в категории "Общие обсуждения"
      const welcomeTopic = topics.find(t => t.title.includes('Добро пожаловать'));
      expect(welcomeTopic.forum_name).toBe('Общие обсуждения');
    });

    it('should have correct topic_id references in posts', async () => {
      const [posts] = await connection.execute(`
        SELECT fp.id, fp.topic_id, ft.title as topic_title
        FROM forum_posts fp
        JOIN forum_topics ft ON fp.topic_id = ft.id
        ORDER BY fp.id
      `);
      
      expect(posts.length).toBe(7);
      
      // Проверяем, что посты имеют корректные ссылки на темы
      for (const post of posts) {
        expect(post.topic_id).toBeGreaterThan(0);
        expect(post.topic_title).toBeDefined();
      }
    });
  });

  describe('Members Table', () => {
    it('should have members table with users', async () => {
      const [rows] = await connection.execute('SELECT COUNT(*) as count FROM members');
      expect(rows[0].count).toBeGreaterThan(0);
    });

    it('should have required member fields', async () => {
      const [rows] = await connection.execute(`
        SELECT member_id, name, email, joined, posts
        FROM members 
        ORDER BY member_id 
        LIMIT 1
      `);
      
      expect(rows[0]).toHaveProperty('member_id');
      expect(rows[0]).toHaveProperty('name');
      expect(rows[0]).toHaveProperty('email');
      expect(rows[0]).toHaveProperty('joined');
      expect(rows[0]).toHaveProperty('posts');
    });
  });
}); 