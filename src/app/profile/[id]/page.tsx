"use client";

import React, { useState, useEffect } from "react";
import { useParams } from "next/navigation";
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
  Avatar
} from "@once-ui-system/core";
import AvatarWithAPI from "@/components/AvatarWithAPI";

interface ProfileData {
  id: number;
  name: string;
  display_name: string;
  email: string;
  joined: string;
  last_visit: string | null;
  group_id: number;
  is_banned: boolean;
  posts: number;
  title: string;
  last_activity: string | null;
  ip_address: string;
  members_pass_hash: string;
  members_pass_salt: string;
}

export default function ProfilePage() {
  const params = useParams();
  const [profile, setProfile] = useState<ProfileData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState('overview');

  useEffect(() => {
    fetchProfile();
  }, [params.id]);

  const fetchProfile = async () => {
    try {
      const response = await fetch(`/api/profile/${params.id}`);
      
      if (!response.ok) {
        const errorData = await response.json();
        setError(errorData.error || 'Ошибка загрузки профиля');
        return;
      }
      
      const data = await response.json();
      
      if (data.error) {
        setError(data.error);
      } else {
        setProfile(data);
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
      setError('Ошибка загрузки профиля');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatRelativeDate = (dateString: string) => {
    const now = new Date();
    const date = new Date(dateString);
    const diff = Math.floor((now.getTime() - date.getTime()) / 1000);
    
    if (diff < 60) return 'только что';
    if (diff < 3600) return `${Math.floor(diff / 60)} мин. назад`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} ч. назад`;
    if (diff < 2592000) return `${Math.floor(diff / 86400)} дн. назад`;
    
    return formatDate(dateString);
  };

  const getMemberGroupColor = (groupId: number) => {
    switch (groupId) {
      case 4: return 'success-medium'; // Администратор
      case 3: return 'warning-medium'; // Модератор
      case 2: return 'info-medium'; // VIP
      default: return 'neutral-medium'; // Обычный пользователь
    }
  };

  const getMemberGroupName = (groupId: number) => {
    switch (groupId) {
      case 4: return 'Администратор';
      case 3: return 'Модератор';
      case 2: return 'VIP Пользователь';
      default: return 'Пользователь';
    }
  };

  if (loading) {
    return (
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Загрузка профиля...</Text>
          </Column>
        </Card>
      </Column>
    );
  }

  if (error || !profile) {
    return (
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="error" size="l" />
            <Text>{error || 'Профиль не найден'}</Text>
            <Button variant="secondary" href="/">Вернуться на главную</Button>
          </Column>
        </Card>
      </Column>
    );
  }

  return (
    <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL="https://demo.magic-portfolio.com"
        title={`${profile.display_name} - Профиль пользователя`}
        description={`Профиль пользователя ${profile.display_name} на форуме CloudMasters`}
        path={`/profile/${profile.id}`}
      />

      {/* Profile Header */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Flex horizontal="between" vertical="center">
            <Column gap="s">
              <Heading variant="display-strong-l">{profile.display_name}</Heading>
              <Text variant="body-default-s" onBackground="neutral-weak">
                {profile.title}
              </Text>
            </Column>
            <AvatarWithAPI userId={profile.id} size="xl" />
          </Flex>
          
          <Flex gap="m" vertical="center">
            <Badge background={getMemberGroupColor(profile.group_id)}>
              {getMemberGroupName(profile.group_id)}
            </Badge>
            {profile.is_banned && (
              <Badge background="danger-medium">Заблокирован</Badge>
            )}
          </Flex>
        </Column>
      </Card>

      {/* Profile Stats */}
      <Grid columns={3} gap="m">
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="message" size="l" />
            <Text variant="display-strong-xl">{profile.posts}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Сообщений
            </Text>
          </Column>
        </Card>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="calendar" size="l" />
            <Text variant="display-strong-s">
              {formatDate(profile.joined)}
            </Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Дата регистрации
            </Text>
          </Column>
        </Card>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="clock" size="l" />
            <Text variant="display-strong-s">
              {profile.last_activity ? formatRelativeDate(profile.last_activity) : 'Неизвестно'}
            </Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Последняя активность
            </Text>
          </Column>
        </Card>
      </Grid>

      {/* Profile Info */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Информация о пользователе</Heading>
          <Column gap="m">
            <Flex horizontal="between" vertical="center">
              <Text variant="body-default-s" onBackground="neutral-weak">Email:</Text>
              <Text variant="body-default-s">{profile.email}</Text>
            </Flex>
            <Flex horizontal="between" vertical="center">
              <Text variant="body-default-s" onBackground="neutral-weak">IP адрес:</Text>
              <Text variant="body-default-s">{profile.ip_address}</Text>
            </Flex>
            <Flex horizontal="between" vertical="center">
              <Text variant="body-default-s" onBackground="neutral-weak">Последний визит:</Text>
              <Text variant="body-default-s">
                {profile.last_visit ? formatDate(profile.last_visit) : 'Неизвестно'}
              </Text>
            </Flex>
          </Column>
        </Column>
      </Card>
    </Column>
  );
} 