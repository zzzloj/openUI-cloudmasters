'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line, Column, Heading } from '@once-ui-system/core';
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
}

export default function ForumPage() {
  const [categories, setCategories] = useState<ForumCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchForumData();
  }, []);

  const fetchForumData = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch('/api/forum/categories');
      if (!response.ok) {
        throw new Error('Failed to fetch categories');
      }
      
      const data = await response.json();
      setCategories(data.categories || []);
    } catch (error) {
      console.error('Error fetching forum data:', error);
      setError('Ошибка загрузки форума');
      // Fallback to static data
      setCategories([
        {
          id: 1,
          name: "Общие обсуждения",
          description: "Общие темы и обсуждения",
          parent_id: null,
          position: 1,
          topics_count: 1,
          posts_count: 3,
          last_post_date: null,
          last_poster_name: ""
        },
        {
          id: 2,
          name: "Техническая поддержка",
          description: "Вопросы по работе сайта и технические проблемы",
          parent_id: null,
          position: 2,
          topics_count: 1,
          posts_count: 2,
          last_post_date: null,
          last_poster_name: ""
        },
        {
          id: 3,
          name: "Новости и анонсы",
          description: "Новости проекта и важные объявления",
          parent_id: null,
          position: 3,
          topics_count: 1,
          posts_count: 1,
          last_post_date: null,
          last_poster_name: ""
        }
      ]);
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
      <Column fillWidth gap="xl" horizontal="center">
        <Heading variant="display-strong-l">Загрузка форума...</Heading>
      </Column>
    );
  }

  return (
    <Column fillWidth gap="xl" horizontal="center">
      {/* Заголовок форума */}
      <Column fillWidth>
        <Heading variant="display-strong-l">Форум CloudMasters</Heading>
      </Column>

      {/* Категории форума */}
      <Column fillWidth gap="m">
        <Text variant="heading-default-m" onBackground="neutral-strong">
          Категории форума
        </Text>
        <Column fillWidth gap="m">
          {categories.map((category) => (
            <Flex key={category.id} fillWidth gap="m" padding="m" background="surface" radius="m">
              <Flex gap="m" fillWidth>
                <Text variant="heading-default-l">📁</Text>
                <Column fillWidth gap="s">
                  <Link href={`/forum/category/${category.id}`}>
                    <Text variant="heading-default-m" onBackground="brand-strong">
                      {category.name}
                    </Text>
                  </Link>
                  {category.description && (
                    <Text variant="body-default-s" onBackground="neutral-weak">
                      {category.description}
                    </Text>
                  )}
                  <Flex gap="m">
                    <Text variant="body-default-xs" onBackground="neutral-weak">
                      Тем: {category.topics_count}
                    </Text>
                    <Text variant="body-default-xs" onBackground="neutral-weak">
                      Постов: {category.posts_count}
                    </Text>
                  </Flex>
                </Column>
              </Flex>
              {category.last_post_date && (
                <Column gap="xs" horizontal="end">
                  <Text variant="body-default-xs" onBackground="neutral-weak">
                    Последнее сообщение
                  </Text>
                  <Text variant="body-default-xs" onBackground="neutral-strong">
                    {category.last_poster_name}
                  </Text>
                  <Text variant="body-default-xs" onBackground="neutral-weak">
                    {formatDate(category.last_post_date)}
                  </Text>
                </Column>
              )}
            </Flex>
          ))}
        </Column>
      </Column>

      {/* Действия */}
      <Flex fillWidth gap="m" horizontal="between">
        <Button href="/forum/new-topic" variant="primary" size="m">
          Создать тему
        </Button>
        <Button href="/forum/search" variant="secondary" size="m">
          Поиск
        </Button>
      </Flex>
    </Column>
  );
} 