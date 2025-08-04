'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line } from '@once-ui-system/core';
import Link from 'next/link';

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
  last_topic_title?: string;
  last_topic_id?: number;
}

export default function ForumPage() {
  const [categories, setCategories] = useState<ForumCategory[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchForumData();
  }, []);

  const fetchForumData = async () => {
    try {
      // Получаем категории с информацией о последней теме
      const categoriesResponse = await fetch('/api/forum/categories');
      const categoriesData = await categoriesResponse.json();
      
      // Для каждой категории получаем последнюю тему
      const categoriesWithLastTopic = await Promise.all(
        categoriesData.categories.map(async (category: ForumCategory) => {
          try {
            const topicsResponse = await fetch(`/api/forum/topics?categoryId=${category.id}&limit=1`);
            const topicsData = await topicsResponse.json();
            
            if (topicsData.topics && topicsData.topics.length > 0) {
              const lastTopic = topicsData.topics[0];
              return {
                ...category,
                last_topic_title: lastTopic.title,
                last_topic_id: lastTopic.id
              };
            }
            return category;
          } catch (error) {
            console.error(`Error fetching last topic for category ${category.id}:`, error);
            return category;
          }
        })
      );

      setCategories(categoriesWithLastTopic);
    } catch (error) {
      console.error('Error fetching forum data:', error);
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
          <h1>Загрузка форума...</h1>
        </div>
      </div>
    );
  }

  return (
    <div className="forum-container">
      {/* Заголовок форума */}
      <div className="forum-header">
        <h1>Форум CloudMasters</h1>
      </div>

      {/* Категории форума */}
      <div className="forum-category">
        <div className="forum-category-header">
          Категории форума
        </div>
        <div className="forum-category-content">
          {categories.map((category) => (
            <div key={category.id} className="forum-subcategory">
              <div className="forum-subcategory-icon">
                <i>📁</i>
              </div>
              <div className="forum-subcategory-info">
                <Link href={`/forum/category/${category.id}`} className="forum-subcategory-title">
                  {category.name}
                </Link>
                {category.description && (
                  <div className="forum-subcategory-description">
                    {category.description}
                  </div>
                )}
                <div className="forum-subcategory-stats">
                  <span>Тем: {category.topics_count}</span>
                  <span>Постов: {category.posts_count}</span>
                </div>
                {/* Последняя тема в категории */}
                {category.last_topic_title && category.last_topic_id && (
                  <div style={{ 
                    marginTop: '8px', 
                    padding: '8px 12px', 
                    backgroundColor: 'var(--neutral-alpha-weak)',
                    borderRadius: '6px',
                    fontSize: '13px'
                  }}>
                    <Link 
                      href={`/forum/topic/${category.last_topic_id}`}
                      style={{ 
                        color: 'var(--brand-background-strong)',
                        textDecoration: 'none',
                        fontWeight: 'bold'
                      }}
                    >
                      {category.last_topic_title}
                    </Link>
                    {category.last_post_date && (
                      <div style={{ 
                        fontSize: '12px', 
                        color: 'var(--neutral-on-background-weak)',
                        marginTop: '2px'
                      }}>
                        {formatDate(category.last_post_date)} • {category.last_poster_name}
                      </div>
                    )}
                  </div>
                )}
              </div>
              {category.last_post_date && (
                <div className="forum-subcategory-last-post">
                  <div className="forum-subcategory-last-post-title">
                    Последнее сообщение
                  </div>
                  <div className="forum-subcategory-last-post-author">
                    {category.last_poster_name}
                  </div>
                  <div className="forum-subcategory-last-post-date">
                    {formatDate(category.last_post_date)}
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Действия */}
      <div className="forum-actions">
        <div className="forum-actions-left">
          <Link href="/forum/new-topic" className="forum-button">
            Создать тему
          </Link>
        </div>
        <div className="forum-actions-right">
          <Link href="/forum/search" className="forum-button secondary">
            Поиск
          </Link>
        </div>
      </div>
    </div>
  );
} 