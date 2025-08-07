'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line, Column, Heading, Badge, Grid } from '@once-ui-system/core';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import styles from './forum-category.module.css';

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
  last_poster_id: number;
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
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Heading variant="display-strong-l">Загрузка...</Heading>
      </Column>
    );
  }

  if (!category) {
    return (
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Heading variant="display-strong-l">Категория не найдена</Heading>
      </Column>
    );
  }

  return (
    <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
      {/* Хлебные крошки */}
      <div className={styles.breadcrumb}>
        <Link href="/forum" className={styles.breadcrumbLink}>
          Форум
        </Link>
        <span className={styles.breadcrumbSeparator}>→</span>
        <span className={styles.breadcrumbCurrent}>{category.name}</span>
      </div>

      {/* Заголовок категории */}
      <div className={styles.categoryHeader}>
        <div className={styles.categoryTitle}>{category.name}</div>
        {category.description && (
          <div className={styles.categoryDescription}>
            {category.description}
          </div>
        )}
        <div className={styles.categoryStats}>
          <span className={styles.categoryBadge}>
            Тем: {category.topics_count}
          </span>
          <span className={styles.categoryBadge}>
            Сообщений: {category.posts_count}
          </span>
        </div>
      </div>

      {/* Темы в категории */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Heading variant="body-strong-xl">
            Темы в категории &quot;{category.name}&quot;
          </Heading>
          
          {topics.length === 0 ? (
            <Text variant="body-default-m" color="secondary">
              В этой категории пока нет тем
            </Text>
          ) : (
            <div>
              {topics.map((topic) => (
                <div key={topic.id} className={styles.topicCard}>
                  <div className={styles.topicContainer}>
                    {/* Иконка темы */}
                    <div className={styles.topicIcon}>
                      {topic.is_pinned ? '📌' : topic.is_locked ? '🔒' : '💬'}
                    </div>
                    
                    {/* Основной контент темы */}
                    <div className={styles.topicContent}>
                      <Link href={`/forum/topic/${topic.id}`} className={styles.topicTitle}>
                        {topic.title}
                      </Link>
                      <div className={styles.topicMeta}>
                        <span>
                          Автор: <Link 
                            href={`/profile/${topic.author_id}`}
                            className={styles.authorLink}
                          >
                            {topic.author_name}
                          </Link>
                        </span>
                        <span>{formatDate(topic.created_at)}</span>
                      </div>
                    </div>
                    
                    {/* Статистика темы */}
                    <div className={styles.topicStats}>
                      <div>Ответов: {topic.posts_count}</div>
                      <div>Просмотров: {topic.views_count}</div>
                    </div>
                  </div>
                  
                  {/* Информация о последнем сообщении */}
                  {topic.last_post_date && (
                    <div className={styles.lastPostSection}>
                      <div className={styles.lastPostInfo}>
                        <span>
                          Последнее сообщение от <Link 
                            href={`/profile/${topic.last_poster_id || topic.author_id}`}
                            className={styles.authorLink}
                          >
                            {topic.last_poster_name}
                          </Link>
                        </span>
                        <span>{formatDate(topic.last_post_date)}</span>
                      </div>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </Column>
      </Card>

      {/* Пагинация */}
      {totalPages > 1 && (
        <div className={styles.paginationContainer}>
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((pageNum) => (
            <Button
              key={pageNum}
              variant={pageNum === page ? "primary" : "secondary"}
              size="s"
              onClick={() => setPage(pageNum)}
            >
              {pageNum}
            </Button>
          ))}
        </div>
      )}

      {/* Действия */}
      <div className={styles.actionsContainer}>
        <Link href="/forum">
          <Button variant="secondary" size="m">
            ← Назад к форуму
          </Button>
        </Link>
        <Link href={`/forum/new-topic?category=${categoryId}`}>
          <Button variant="primary" size="m">
            Создать тему
          </Button>
        </Link>
      </div>
    </Column>
  );
} 