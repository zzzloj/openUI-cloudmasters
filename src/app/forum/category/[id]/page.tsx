'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line } from '@once-ui-system/core';
import Link from 'next/link';
import { useParams } from 'next/navigation';

interface ForumTopic {
  id: number;
  title: string;
  forum_id: number;
  author_id: number;
  author_name: string;
  posts_count: number;
  views_count: number;
  is_pinned: boolean;
  is_locked: boolean;
  is_approved: boolean;
  created_at: number;
  last_post_date: number | null;
  last_poster_name: string;
  forum_name: string;
}

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

export default function CategoryPage() {
  const params = useParams();
  const categoryId = params.id as string;
  
  const [category, setCategory] = useState<ForumCategory | null>(null);
  const [topics, setTopics] = useState<ForumTopic[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    fetchCategoryData();
  }, [categoryId, page]);

  const fetchCategoryData = async () => {
    try {
      // Получаем информацию о категории
      const categoryResponse = await fetch(`/api/forum/categories/${categoryId}`);
      const categoryData = await categoryResponse.json();
      
      // Получаем темы в категории
      const topicsResponse = await fetch(`/api/forum/topics?forum_id=${categoryId}&page=${page}&limit=20`);
      const topicsData = await topicsResponse.json();

      setCategory(categoryData.category);
      setTopics(topicsData.topics || []);
      setTotalPages(topicsData.pagination?.pages || 1);
    } catch (error) {
      console.error('Error fetching category data:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (timestamp: number) => {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    return date.toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
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

  if (!category) {
    return (
      <div className="forum-container">
        <div className="forum-header">
          <h1>Категория не найдена</h1>
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
        <span>{category.name}</span>
      </div>

      {/* Заголовок категории */}
      <div className="forum-header">
        <h1>{category.name}</h1>
        {category.description && (
          <p style={{ margin: '10px 0 0 0', fontSize: '14px', opacity: 0.8 }}>
            {category.description}
          </p>
        )}
      </div>

      {/* Темы в категории */}
      <div className="forum-topics-header">
        Темы в категории &quot;{category.name}&quot;
      </div>
      <div className="forum-topics-list">
        {topics.length === 0 ? (
          <div style={{ padding: '20px', textAlign: 'center', color: '#7f8c8d' }}>
            В этой категории пока нет тем
          </div>
        ) : (
          topics.map((topic) => (
            <div key={topic.id} className="forum-topic">
              <div className={`forum-topic-icon ${topic.is_pinned ? 'pinned' : ''} ${topic.is_locked ? 'locked' : ''}`}>
                <i>
                  {topic.is_pinned ? '📌' : topic.is_locked ? '🔒' : '💬'}
                </i>
              </div>
              <div className="forum-topic-info">
                <Link href={`/forum/topic/${topic.id}`} className="forum-topic-title">
                  {topic.title}
                </Link>
                <div className="forum-topic-meta">
                  <span>Автор: {topic.author_name}</span>
                  <span>{formatDate(topic.created_at)}</span>
                </div>
              </div>
              <div className="forum-topic-stats">
                <div className="forum-topic-stats-item">
                  Ответов: {topic.posts_count}
                </div>
                <div className="forum-topic-stats-item">
                  Просмотров: {topic.views_count}
                </div>
              </div>
              {topic.last_post_date && (
                <div className="forum-topic-last-post">
                  <div className="forum-topic-last-post-title">
                    Последнее сообщение
                  </div>
                  <div className="forum-topic-last-post-author">
                    {topic.last_poster_name}
                  </div>
                  <div className="forum-topic-last-post-date">
                    {formatDate(topic.last_post_date)}
                  </div>
                </div>
              )}
            </div>
          ))
        )}
      </div>

      {/* Пагинация */}
      {totalPages > 1 && (
        <div className="forum-pagination">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((pageNum) => (
            <Link
              key={pageNum}
              href="#"
              className={pageNum === page ? 'active' : ''}
              onClick={(e) => {
                e.preventDefault();
                setPage(pageNum);
              }}
            >
              {pageNum}
            </Link>
          ))}
        </div>
      )}

      {/* Действия */}
      <div className="forum-actions">
        <div className="forum-actions-left">
          <Link href="/forum" className="forum-button secondary">
            ← Назад к форуму
          </Link>
        </div>
        <div className="forum-actions-right">
          <Link href={`/forum/new-topic?category=${categoryId}`} className="forum-button">
            Создать тему
          </Link>
        </div>
      </div>
    </div>
  );
} 