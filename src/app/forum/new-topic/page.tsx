'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line } from '@once-ui-system/core';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';

interface ForumCategory {
  id: number;
  name: string;
  description: string;
  parent_id: number | null;
  position: number;
  topics_count: number;
  posts_count: number;
  last_post_date: number | null;
  last_poster_name: string;
}

export default function NewTopicPage() {
  const searchParams = useSearchParams();
  const categoryId = searchParams.get('category');
  
  const [categories, setCategories] = useState<ForumCategory[]>([]);
  const [selectedCategory, setSelectedCategory] = useState(categoryId || '');
  const [title, setTitle] = useState('');
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const response = await fetch('/api/forum/categories');
      const data = await response.json();
      setCategories(data.categories || []);
    } catch (error) {
      console.error('Error fetching categories:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim() || !content.trim() || !selectedCategory) return;

    setSubmitting(true);
    try {
      const response = await fetch('/api/forum/topics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          title: title.trim(),
          forum_id: parseInt(selectedCategory),
          author_id: 1, // TODO: Получать из авторизации
          author_name: 'User', // TODO: Получать из авторизации
          content: content.trim()
        }),
      });

      if (response.ok) {
        const result = await response.json();
        // Перенаправляем на созданную тему
        window.location.href = `/forum/topic/${result.topic_id}`;
      } else {
        console.error('Failed to create topic');
      }
    } catch (error) {
      console.error('Error creating topic:', error);
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="forum-container">
        <div className="forum-header">
          <h1>Загрузка...</h1>
        </div>
      </div>
    );
  }

  return (
    <div className="forum-container">
      {/* Хлебные крошки */}
      <div className="forum-breadcrumb">
        <Link href="/forum">Форум</Link>
        <span>→</span>
        <span>Создать тему</span>
      </div>

      {/* Заголовок */}
      <div className="forum-header">
        <h1>Создать новую тему</h1>
      </div>

      {/* Форма создания темы */}
      <div className="forum-actions">
        <div className="forum-actions-left">
          <form onSubmit={handleSubmit} style={{ width: '100%' }}>
            <div style={{ marginBottom: '20px' }}>
              <label style={{ 
                display: 'block', 
                marginBottom: '8px',
                color: 'var(--neutral-on-background-strong)',
                fontWeight: 'bold'
              }}>
                Категория:
              </label>
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                required
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid var(--neutral-alpha-medium)',
                  borderRadius: '8px',
                  backgroundColor: 'var(--surface-background)',
                  color: 'var(--neutral-on-background-strong)',
                  fontSize: '14px'
                }}
              >
                <option value="">Выберите категорию</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            </div>

            <div style={{ marginBottom: '20px' }}>
              <label style={{ 
                display: 'block', 
                marginBottom: '8px',
                color: 'var(--neutral-on-background-strong)',
                fontWeight: 'bold'
              }}>
                Заголовок темы:
              </label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Введите заголовок темы..."
                required
                style={{
                  width: '100%',
                  padding: '12px',
                  border: '1px solid var(--neutral-alpha-medium)',
                  borderRadius: '8px',
                  backgroundColor: 'var(--surface-background)',
                  color: 'var(--neutral-on-background-strong)',
                  fontSize: '14px'
                }}
              />
            </div>

            <div style={{ marginBottom: '20px' }}>
              <label style={{ 
                display: 'block', 
                marginBottom: '8px',
                color: 'var(--neutral-on-background-strong)',
                fontWeight: 'bold'
              }}>
                Содержание первого сообщения:
              </label>
              <textarea
                value={content}
                onChange={(e) => setContent(e.target.value)}
                placeholder="Введите содержание вашего сообщения..."
                required
                style={{
                  width: '100%',
                  minHeight: '200px',
                  padding: '12px',
                  border: '1px solid var(--neutral-alpha-medium)',
                  borderRadius: '8px',
                  backgroundColor: 'var(--surface-background)',
                  color: 'var(--neutral-on-background-strong)',
                  fontSize: '14px',
                  resize: 'vertical',
                  fontFamily: 'inherit'
                }}
              />
            </div>

            <div style={{ display: 'flex', gap: '10px' }}>
              <button
                type="submit"
                disabled={submitting || !title.trim() || !content.trim() || !selectedCategory}
                className="forum-button"
                style={{
                  opacity: submitting || !title.trim() || !content.trim() || !selectedCategory ? 0.6 : 1,
                  cursor: submitting || !title.trim() || !content.trim() || !selectedCategory ? 'not-allowed' : 'pointer'
                }}
              >
                {submitting ? 'Создание...' : 'Создать тему'}
              </button>
              <Link href="/forum" className="forum-button secondary">
                Отмена
              </Link>
            </div>
          </form>
        </div>
      </div>
    </div>
  );
} 