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

interface ProfileData {
  id: number;
  name: string;
  display_name: string;
  seo_name: string;
  email: string;
  member_group_id: number;
  member_group: any;
  joined: number;
  last_visit: number;
  last_activity: number;
  posts: number;
  title: string;
  warn_level: number;
  member_banned: boolean;
  has_blog: boolean;
  has_gallery: boolean;
  profile_views: number;
  day_posts: string;
  bitoptions: number;
  uploader: string;
  time_offset: string;
  language: number;
  skin: number;
  dst_in_use: boolean;
  coppa_user: boolean;
  view_sigs: boolean;
  view_img: boolean;
  auto_track: string;
  temp_ban: string;
  login_anonymous: string;
  ignored_users: string;
  mgroup_others: string;
  org_perm_id: string;
  member_login_key: string;
  member_login_key_expire: number;
  blogs_recache: boolean;
  members_auto_dst: boolean;
  members_created_remote: boolean;
  members_cache: string;
  members_disable_pm: number;
  members_l_display_name: string;
  members_l_username: string;
  failed_logins: string;
  failed_login_count: number;
  members_pass_hash: string;
  members_pass_salt: string;
  fb_uid: number;
  fb_emailhash: string;
  fb_lastsync: number;
  vk_uid: number;
  vk_token: string;
  live_id: string;
  twitter_id: string;
  twitter_token: string;
  twitter_secret: string;
  notification_cnt: number;
  tc_lastsync: number;
  fb_session: string;
  fb_token: string;
  ips_mobile_token: string;
  unacknowledged_warnings: boolean;
  ipsconnect_id: number;
  ipsconnect_revalidate_url: string;
  gallery_perms: string;
  activation_code: string;
  activation_expires: number;
  is_activated: boolean;
  reset_code: string;
  reset_expires: number;
  birthday: {
    day: number;
    month: number;
    year: number;
  };
  messages: {
    new: number;
    total: number;
    reset: number;
    show_notification: number;
  };
  misc: string;
  allow_admin_mails: boolean;
  restrict_post: string;
  mod_posts: string;
  warn_lastwarn: number;
  ip_address: string;
  stats: {
    total_posts: number;
    total_topics: number;
    last_post_date: number | null;
  };
  recent_posts: any[];
  user_topics: any[];
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
      const data = await response.json();
      
      if (data.success) {
        setProfile(data.profile);
      } else {
        setError(data.error || 'Ошибка загрузки профиля');
      }
    } catch (error) {
      console.error('Error fetching profile:', error);
      setError('Ошибка загрузки профиля');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const formatRelativeDate = (timestamp: number) => {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - timestamp;
    
    if (diff < 60) return 'только что';
    if (diff < 3600) return `${Math.floor(diff / 60)} мин. назад`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} ч. назад`;
    if (diff < 2592000) return `${Math.floor(diff / 86400)} дн. назад`;
    
    return formatDate(timestamp);
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
        baseURL="https://cloudmasters.ru"
        title={`Профиль ${profile.display_name || profile.name} - CloudMasters`}
        description={`Профиль пользователя ${profile.display_name || profile.name}`}
        path={`/profile/${profile.id}`}
      />
      
      {/* Profile Header */}
      <Card padding="xl" radius="l" shadow="l">
        <Flex gap="l" vertical="center">
          <Avatar 
            size="xl" 
            src={`/api/avatar/${profile.id}`}
          />
          <Column gap="s" fillWidth>
            <Flex gap="m" vertical="center">
              <Heading variant="display-strong-l">
                {profile.display_name || profile.name}
              </Heading>
              <Badge background={getMemberGroupColor(profile.member_group_id)}>
                {getMemberGroupName(profile.member_group_id)}
              </Badge>
              {profile.member_banned && (
                <Badge background="danger-medium">Заблокирован</Badge>
              )}
            </Flex>
            {profile.title && (
              <Text variant="body-default-s" onBackground="neutral-weak">
                {profile.title}
              </Text>
            )}
            <Text variant="body-default-s" onBackground="neutral-weak">
              На сайте с {formatDate(profile.joined)}
            </Text>
          </Column>
          <Flex gap="m" vertical="center">
            <Button variant="secondary" prefixIcon="message">
              Написать сообщение
            </Button>
            <Button variant="secondary" prefixIcon="user">
              Подписаться
            </Button>
          </Flex>
        </Flex>
      </Card>

      {/* Profile Stats */}
      <Grid columns={4} gap="m">
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
            <Icon name="document" size="l" />
            <Text variant="display-strong-xl">{profile.stats.total_topics}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Тем
            </Text>
          </Column>
        </Card>
        <Card padding="l" radius="m">
          <Column gap="s" horizontal="center">
            <Icon name="eye" size="l" />
            <Text variant="display-strong-xl">{profile.profile_views}</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Просмотров
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

      {/* Profile Content */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Последние сообщения</Heading>
          {profile.recent_posts.length > 0 ? (
            <Column gap="m">
              {profile.recent_posts.map((post: any) => (
                <Card key={post.id} padding="m" radius="m">
                  <Column gap="s">
                    <Flex horizontal="between" vertical="center">
                      <Text variant="heading-strong-s">{post.topic_title}</Text>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {formatRelativeDate(post.created_at)}
                      </Text>
                    </Flex>
                    <Text variant="body-default-s" onBackground="neutral-weak">
                      {post.content.substring(0, 200)}...
                    </Text>
                    <Text variant="body-default-xs" onBackground="neutral-weak">
                      в разделе {post.forum_name}
                    </Text>
                  </Column>
                </Card>
              ))}
            </Column>
          ) : (
            <Text variant="body-default-s" onBackground="neutral-weak">
              Пользователь пока не оставлял сообщений
            </Text>
          )}
        </Column>
      </Card>

      {/* User Topics */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Созданные темы</Heading>
          {profile.user_topics.length > 0 ? (
            <Column gap="m">
              {profile.user_topics.map((topic: any) => (
                <Card key={topic.id} padding="m" radius="m">
                  <Column gap="s">
                    <Flex horizontal="between" vertical="center">
                      <Text variant="heading-strong-s">{topic.title}</Text>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {formatRelativeDate(topic.created_at)}
                      </Text>
                    </Flex>
                    <Flex gap="m" vertical="center">
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {topic.posts_count} сообщений
                      </Text>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {topic.views_count} просмотров
                      </Text>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        в разделе {topic.forum_name}
                      </Text>
                    </Flex>
                  </Column>
                </Card>
              ))}
            </Column>
          ) : (
            <Text variant="body-default-s" onBackground="neutral-weak">
              Пользователь пока не создавал тем
            </Text>
          )}
        </Column>
      </Card>

      {/* Activity Info */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Активность</Heading>
          <Column gap="m">
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="login" size="s" />
                <Text>Последний вход</Text>
              </Flex>
              <Text variant="body-default-s">
                {profile.last_visit ? formatRelativeDate(profile.last_visit) : 'Неизвестно'}
              </Text>
            </Flex>
            <hr style={{ border: 'none', borderTop: '1px solid var(--neutral-alpha-weak)', margin: '8px 0' }} />
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="message" size="s" />
                <Text>Последнее сообщение</Text>
              </Flex>
              <Text variant="body-default-s">
                {profile.stats.last_post_date ? formatRelativeDate(profile.stats.last_post_date) : 'Нет сообщений'}
              </Text>
            </Flex>
            <hr style={{ border: 'none', borderTop: '1px solid var(--neutral-alpha-weak)', margin: '8px 0' }} />
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="eye" size="s" />
                <Text>Просмотры профиля</Text>
              </Flex>
              <Text variant="body-default-s">{profile.profile_views}</Text>
            </Flex>
          </Column>
        </Column>
      </Card>

      {/* Additional Info */}
      <Card padding="xl" radius="l" shadow="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Дополнительная информация</Heading>
          <Grid columns={2} gap="l">
            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Email
              </Text>
              <Text variant="heading-strong-s">{profile.email}</Text>
            </Column>
            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                IP адрес
              </Text>
              <Text variant="heading-strong-s">{profile.ip_address}</Text>
            </Column>
            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Часовой пояс
              </Text>
              <Text variant="heading-strong-s">{profile.time_offset || 'Не указан'}</Text>
            </Column>
            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Язык
              </Text>
              <Text variant="heading-strong-s">{profile.language || 'Не указан'}</Text>
            </Column>
            {profile.birthday.day && (
              <Column gap="m">
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Дата рождения
                </Text>
                <Text variant="heading-strong-s">
                  {profile.birthday.day}.{profile.birthday.month}.{profile.birthday.year}
                </Text>
              </Column>
            )}
            <Column gap="m">
              <Text variant="body-default-s" onBackground="neutral-weak">
                Статус аккаунта
              </Text>
              <Badge background={profile.is_activated ? 'success-medium' : 'warning-medium'}>
                {profile.is_activated ? 'Активирован' : 'Не активирован'}
              </Badge>
            </Column>
          </Grid>
        </Column>
      </Card>
    </Column>
  );
} 