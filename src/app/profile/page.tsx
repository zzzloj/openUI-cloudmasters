"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import {
  Column,
  Card,
  Icon,
  Text,
  Schema,
  Flex,
  Heading,
  Badge,
  Button,
  Grid,
  Input
} from "@once-ui-system/core";

interface User {
  id: string;
  username: string;
  email: string;
  displayName: string;
  role: string;
  isAdmin: boolean;
  memberGroupId: number;
  joined: number;
  lastVisit: number;
  posts: number;
}

export default function ProfilePage() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [editMode, setEditMode] = useState(false);
  const [formData, setFormData] = useState({
    displayName: "",
    email: ""
  });
  const router = useRouter();

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const response = await fetch("/api/auth/me");
      if (response.ok) {
        const data = await response.json();
        setUser(data.user);
        setFormData({
          displayName: data.user.displayName || "",
          email: data.user.email || ""
        });
        
        // Перенаправляем на новую страницу профиля
        router.push(`/profile/${data.user.id}`);
        return;
      } else {
        // Не авторизован, перенаправляем на страницу входа
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

  const handleLogout = async () => {
    try {
      await fetch("/api/auth/logout", { method: "POST" });
      router.push("/auth/login");
    } catch (error) {
      console.error("Ошибка выхода:", error);
    }
  };

  const handleSaveProfile = async () => {
    try {
      const response = await fetch("/api/profile/update", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });
      
      if (response.ok) {
        setEditMode(false);
        // Обновляем данные пользователя
        const updatedUser = { ...user!, ...formData };
        setUser(updatedUser);
      }
    } catch (error) {
      console.error("Ошибка обновления профиля:", error);
    }
  };

  const formatDate = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleDateString('ru-RU');
  };

  if (loading) {
    return (
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Перенаправление на профиль...</Text>
          </Column>
        </Card>
      </Column>
    );
  }

  if (!user) {
    return null; // Перенаправление уже произошло
  }

  return (
    <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL="https://cloudmasters.ru"
        title="Профиль - CloudMasters"
        description="Личный кабинет пользователя"
        path="/profile"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center">
        <Flex gap="m" vertical="center">
          <Heading variant="display-strong-l">Личный кабинет</Heading>
          <Badge background={user.isAdmin ? "success-medium" : "info-medium"}>
            {user.isAdmin ? "Администратор" : "Пользователь"}
          </Badge>
        </Flex>
        <Flex gap="m" vertical="center">
          <Button 
            variant="secondary" 
            prefixIcon="logout"
            onClick={handleLogout}
          >
            Выйти
          </Button>
        </Flex>
      </Flex>

      {/* Profile Info */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Flex horizontal="between" vertical="center">
            <Heading variant="display-strong-s">Информация профиля</Heading>
            <Button 
              variant="secondary" 
              prefixIcon={editMode ? "save" : "edit"}
              onClick={() => editMode ? handleSaveProfile() : setEditMode(true)}
            >
              {editMode ? "Сохранить" : "Редактировать"}
            </Button>
          </Flex>

          <Grid columns={2} gap="l">
            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Имя пользователя
              </Text>
              <Text variant="heading-strong-s">{user.username}</Text>
            </Column>

            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Email
              </Text>
              {editMode ? (
                <Input
                  id="email-input"
                  value={formData.email}
                  onChange={(e) => setFormData(prev => ({ ...prev, email: e.target.value }))}
                  placeholder="Введите email"
                />
              ) : (
                <Text variant="heading-strong-s">{user.email}</Text>
              )}
            </Column>

            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Отображаемое имя
              </Text>
              {editMode ? (
                <Input
                  id="display-name-input"
                  value={formData.displayName}
                  onChange={(e) => setFormData(prev => ({ ...prev, displayName: e.target.value }))}
                  placeholder="Введите отображаемое имя"
                />
              ) : (
                <Text variant="heading-strong-s">{user.displayName}</Text>
              )}
            </Column>

            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Дата регистрации
              </Text>
              <Text variant="heading-strong-s">{formatDate(user.joined)}</Text>
            </Column>
          </Grid>
        </Column>
      </Card>

      {/* Statistics */}
      <Grid columns={3} gap="m">
        <Card padding="l" radius="m">
          <Column gap="s">
            <Flex horizontal="between" vertical="center">
              <Text variant="heading-strong-s">Сообщения</Text>
              <Icon name="message" size="l" />
            </Flex>
            <Text variant="display-strong-xl">{user.posts}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Всего сообщений
            </Text>
          </Column>
        </Card>

        <Card padding="l" radius="m">
          <Column gap="s">
            <Flex horizontal="between" vertical="center">
              <Text variant="heading-strong-s">Последний визит</Text>
              <Icon name="clock" size="l" />
            </Flex>
            <Text variant="display-strong-s">{formatDate(user.lastVisit)}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Активность
            </Text>
          </Column>
        </Card>

        <Card padding="l" radius="m">
          <Column gap="s">
            <Flex horizontal="between" vertical="center">
              <Text variant="heading-strong-s">Статус</Text>
              <Icon name="user" size="l" />
            </Flex>
            <Badge background="success-medium">Активен</Badge>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Аккаунт активен
            </Text>
          </Column>
        </Card>
      </Grid>

      {/* Quick Actions */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Быстрые действия</Heading>
          <Grid columns={2} gap="m">
            <Button 
              variant="secondary" 
              prefixIcon="settings"
              href="/profile/settings"
            >
              Настройки
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="message"
              href="/profile/messages"
            >
              Сообщения
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="document"
              href="/profile/posts"
            >
              Мои публикации
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="security"
              href="/profile/security"
            >
              Безопасность
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
              <Flex gap="m" vertical="center">
                <Icon name="login" size="s" />
                <Text>Вход в систему</Text>
              </Flex>
              <Badge background="success-medium">Сегодня</Badge>
            </Flex>
            <hr style={{ border: 'none', borderTop: '1px solid var(--neutral-alpha-weak)', margin: '8px 0' }} />
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="message" size="s" />
                <Text>Опубликовано сообщение</Text>
              </Flex>
              <Badge background="info-medium">2 дня назад</Badge>
            </Flex>
            <hr style={{ border: 'none', borderTop: '1px solid var(--neutral-alpha-weak)', margin: '8px 0' }} />
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="edit" size="s" />
                <Text>Обновлен профиль</Text>
              </Flex>
              <Badge background="warning-medium">Неделю назад</Badge>
            </Flex>
          </Column>
        </Column>
      </Card>

      {/* Admin Panel Link */}
      {user.isAdmin && (
        <Card padding="xl" radius="l" background="success-weak">
          <Column gap="l">
            <Flex gap="m" vertical="center">
              <Icon name="admin" size="l" />
              <Heading variant="display-strong-s">Панель администратора</Heading>
            </Flex>
            <Text variant="body-default-s" onBackground="neutral-weak">
              У вас есть доступ к панели администратора для управления сайтом.
            </Text>
            <Button 
              variant="primary" 
              prefixIcon="settings"
              href="/admin"
            >
              Перейти в админ-панель
            </Button>
          </Column>
        </Card>
      )}
    </Column>
  );
} 