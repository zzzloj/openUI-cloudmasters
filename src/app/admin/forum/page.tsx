"use client";

import React, { useState, useEffect } from "react";
import {
  Column,
  Flex,
  Heading,
  Text,
  Button,
  Card,
  Icon,
  Grid,
  Badge,
  Schema
} from "@once-ui-system/core";
import { baseURL } from "@/resources";
import { useRouter } from "next/navigation";

interface ForumStats {
  totalUsers: number;
  totalTopics: number;
  totalPosts: number;
  activeUsers: number;
  newUsersToday: number;
  newTopicsToday: number;
  newPostsToday: number;
}

interface AdminUser {
  id: number;
  name: string;
  email: string;
  member_group_id: number;
  isAdmin: boolean;
}

export default function ForumAdminDashboard() {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState<ForumStats>({
    totalUsers: 0,
    totalTopics: 0,
    totalPosts: 0,
    activeUsers: 0,
    newUsersToday: 0,
    newTopicsToday: 0,
    newPostsToday: 0
  });
  const router = useRouter();

  useEffect(() => {
    checkAuth();
    loadStats();
  }, []);

  const checkAuth = async () => {
    try {
      const token = localStorage.getItem('authToken');
      if (!token) {
        router.push("/auth/login");
        return;
      }

      const response = await fetch("/api/auth/me", {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        
        // Проверяем права доступа к админ-панели (группа 4 = администратор)
        if (data.user.member_group_id !== 4) {
          router.push("/profile");
          return;
        }
        
        setUser(data.user);
      } else {
        router.push("/auth/login");
        return;
      }
    } catch (error) {
      console.error("Ошибка проверки аутентификации:", error);
      router.push("/auth/login");
      return;
    } finally {
      setLoading(false);
    }
  };

  const loadStats = async () => {
    try {
      const response = await fetch("/api/admin/forum/stats");
      if (response.ok) {
        const data = await response.json();
        setStats(data);
      }
    } catch (error) {
      console.error("Ошибка загрузки статистики:", error);
    }
  };

  const handleLogout = async () => {
    try {
      await fetch("/api/auth/logout", { method: "POST" });
      router.push("/auth/login");
    } catch (error) {
      console.error("Ошибка выхода:", error);
    }
  };

  if (loading) {
    return (
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Загрузка панели администрирования...</Text>
          </Column>
        </Card>
      </Column>
    );
  }

  if (!user) {
    return null; // Перенаправление уже произошло
  }

  return (
    <Column maxWidth="xl" gap="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Forum Admin Panel - CloudMasters"
        description="Панель администрирования форума"
        path="/admin/forum"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l" style={{ flexWrap: 'wrap', gap: '1rem' }}>
        <Flex gap="m" vertical="center" style={{ flexWrap: 'wrap' }}>
          <Heading variant="display-strong-l">Панель администрирования форума</Heading>
          <Badge background="success-medium">
            Администратор
          </Badge>
        </Flex>
        <Flex gap="m" vertical="center" style={{ flexWrap: 'wrap' }}>
          <Text variant="body-default-s" onBackground="neutral-weak">
            {user.name} ({user.email})
          </Text>
          <Button 
            variant="secondary" 
            prefixIcon="logout"
            onClick={handleLogout}
          >
            Выйти
          </Button>
        </Flex>
      </Flex>

      {/* Statistics */}
      <Grid columns={4} gap="m" style={{ 
        gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))' 
      }}>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="users" size="l" />
            <Text variant="display-strong-xl">{stats.totalUsers}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Пользователей
            </Text>
          </Column>
        </Card>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="message" size="l" />
            <Text variant="display-strong-xl">{stats.totalTopics}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Тем
            </Text>
          </Column>
        </Card>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="chat" size="l" />
            <Text variant="display-strong-xl">{stats.totalPosts}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Сообщений
            </Text>
          </Column>
        </Card>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="activity" size="l" />
            <Text variant="display-strong-xl">{stats.activeUsers}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Активных
            </Text>
          </Column>
        </Card>
      </Grid>

      {/* Today's Activity */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Активность за сегодня</Heading>
          <Grid columns={3} gap="m" style={{ 
            gridTemplateColumns: 'repeat(auto-fit, minmax(150px, 1fr))' 
          }}>
            <Flex gap="s" vertical="center">
              <Icon name="user-plus" size="m" />
              <Column gap="xs">
                <Text variant="body-strong-s">{stats.newUsersToday}</Text>
                <Text variant="body-default-s" onBackground="neutral-weak">Новых пользователей</Text>
              </Column>
            </Flex>
            <Flex gap="s" vertical="center">
              <Icon name="message-plus" size="m" />
              <Column gap="xs">
                <Text variant="body-strong-s">{stats.newTopicsToday}</Text>
                <Text variant="body-default-s" onBackground="neutral-weak">Новых тем</Text>
              </Column>
            </Flex>
            <Flex gap="s" vertical="center">
              <Icon name="chat-plus" size="m" />
              <Column gap="xs">
                <Text variant="body-strong-s">{stats.newPostsToday}</Text>
                <Text variant="body-default-s" onBackground="neutral-weak">Новых сообщений</Text>
              </Column>
            </Flex>
          </Grid>
        </Column>
      </Card>

      {/* Quick Actions */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Быстрые действия</Heading>
          <Grid columns={2} gap="m" style={{ 
            gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))' 
          }}>
            <Button 
              variant="secondary" 
              prefixIcon="users"
              href="/admin/forum/users"
            >
              Управление пользователями
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="folder"
              href="/admin/forum/categories"
            >
              Управление категориями
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="shield"
              href="/admin/forum/moderation"
            >
              Модерация
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="settings"
              href="/admin/forum/settings"
            >
              Настройки форума
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="analytics"
              href="/admin/forum/statistics"
            >
              Статистика
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="bell"
              href="/admin/forum/notifications"
            >
              Уведомления
            </Button>
          </Grid>
        </Column>
      </Card>

      {/* Recent Activity */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Последняя активность</Heading>
          <Column gap="m">
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Text variant="body-default-s">Новые пользователи</Text>
              <Badge background="success-medium">{stats.newUsersToday}</Badge>
            </Flex>
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Text variant="body-default-s">Новые темы</Text>
              <Badge background="info-medium">{stats.newTopicsToday}</Badge>
            </Flex>
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Text variant="body-default-s">Новые сообщения</Text>
              <Badge background="warning-medium">{stats.newPostsToday}</Badge>
            </Flex>
          </Column>
        </Column>
      </Card>
    </Column>
  );
}
