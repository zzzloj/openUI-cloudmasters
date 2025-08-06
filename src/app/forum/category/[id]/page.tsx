'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line, Column, Heading, Badge, Grid } from '@once-ui-system/core';
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
      <Flex gap="s" vertical="center">
        <Link href="/forum">
          <Text variant="body-strong-s">Форум</Text>
        </Link>
        <Text variant="body-default-s">→</Text>
        <Text variant="body-strong-s">{category.name}</Text>
      </Flex>

      {/* Заголовок категории */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="m">
          <Heading variant="display-strong-l">{category.name}</Heading>
          {category.description && (
            <Text variant="body-default-m" color="secondary">
              {category.description}
            </Text>
          )}
          <Flex gap="l" vertical="center">
            <Badge>
              Тем: {category.topics_count}
            </Badge>
            <Badge>
              Сообщений: {category.posts_count}
            </Badge>
          </Flex>
        </Column>
      </Card>

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
            <Column gap="m">
              {topics.map((topic) => (
                <Card key={topic.id} padding="m" radius="m" shadow="s">
                  <Grid columns={4} gap="m">
                    <Flex gap="s" vertical="center">
                      <Text variant="body-default-l">
                        {topic.is_pinned ? '📌' : topic.is_locked ? '🔒' : '💬'}
                      </Text>
                    </Flex>
                    
                    <Column gap="xs" style={{ gridColumn: 'span 2' }}>
                      <Link href={`/forum/topic/${topic.id}`}>
                        <Text variant="body-strong-m" color="primary">
                          {topic.title}
                        </Text>
                      </Link>
                      <Flex gap="m" vertical="center">
                        <Text variant="body-default-s" color="secondary">
                          Автор: {topic.author_name}
                        </Text>
                        <Text variant="body-default-s" color="secondary">
                          {formatDate(topic.created_at)}
                        </Text>
                      </Flex>
                    </Column>
                    
                    <Column gap="xs">
                      <Text variant="body-default-s" color="secondary">
                        Ответов: {topic.posts_count}
                      </Text>
                      <Text variant="body-default-s" color="secondary">
                        Просмотров: {topic.views_count}
                      </Text>
                    </Column>
                  </Grid>
                  
                  {topic.last_post_date && (
                    <>
                      <Line marginTop="m" />
                      <Flex gap="m" vertical="center">
                        <Text variant="body-default-s" color="secondary">
                          Последнее сообщение от {topic.last_poster_name}
                        </Text>
                        <Text variant="body-default-s" color="secondary">
                          {formatDate(topic.last_post_date)}
                        </Text>
                      </Flex>
                    </>
                  )}
                </Card>
              ))}
            </Column>
          )}
        </Column>
      </Card>

      {/* Пагинация */}
      {totalPages > 1 && (
        <Card padding="m" radius="m" shadow="s">
          <Flex gap="s" vertical="center">
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
          </Flex>
        </Card>
      )}

      {/* Действия */}
      <Flex gap="l" vertical="center" fillWidth>
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
      </Flex>
    </Column>
  );
} 