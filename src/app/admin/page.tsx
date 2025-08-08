'use client';

import { useEffect, useState } from 'react';
import { Card, Button, Text, Column, Flex, Icon, Badge, Heading, Grid } from '@once-ui-system/core';
import { 
  FaUsers, 
  FaComments, 
  FaFileAlt, 
  FaChartLine, 
  FaExclamationTriangle,
  FaClock,
  FaEye,
  FaPlus,
  FaCog
} from 'react-icons/fa';

interface DashboardStats {
  totalUsers: number;
  totalTopics: number;
  totalPosts: number;
  newUsersToday: number;
  newTopicsToday: number;
  newPostsToday: number;
  activeUsers: number;
  systemStatus: 'online' | 'warning' | 'error';
}

interface RecentActivity {
  id: number;
  type: 'user' | 'topic' | 'post' | 'system';
  title: string;
  description: string;
  time: string;
  user?: string;
}

export default function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [recentActivity, setRecentActivity] = useState<RecentActivity[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    try {
      // Загружаем статистику
      const statsResponse = await fetch('/api/admin/forum/stats', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('authToken')}`
        }
      });

      if (statsResponse.ok) {
        const statsData = await statsResponse.json();
        setStats({
          totalUsers: statsData.totalUsers,
          totalTopics: statsData.totalTopics,
          totalPosts: statsData.totalPosts,
          newUsersToday: statsData.newUsersToday,
          newTopicsToday: statsData.newTopicsToday,
          newPostsToday: statsData.newPostsToday,
          activeUsers: Math.floor(Math.random() * 50) + 10, // Временные данные
          systemStatus: 'online' as const
        });
      }

      // Загружаем последнюю активность
      setRecentActivity([
        {
          id: 1,
          type: 'user',
          title: 'Новый пользователь',
          description: 'Зарегистрировался новый участник',
          time: '2 минуты назад',
          user: 'alex_2024'
        },
        {
          id: 2,
          type: 'topic',
          title: 'Новая тема',
          description: 'Создана тема в разделе "Общие вопросы"',
          time: '5 минут назад',
          user: 'maria_s'
        },
        {
          id: 3,
          type: 'post',
          title: 'Новое сообщение',
          description: 'Добавлено сообщение в тему "Помощь с настройкой"',
          time: '8 минут назад',
          user: 'admin'
        },
        {
          id: 4,
          type: 'system',
          title: 'Система',
          description: 'Автоматическое резервное копирование завершено',
          time: '15 минут назад'
        }
      ]);

    } catch (error) {
      console.error('Ошибка загрузки данных дашборда:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'online': return 'success';
      case 'warning': return 'warning';
      case 'error': return 'danger';
      default: return 'default';
    }
  };

  const getActivityIcon = (type: string) => {
    switch (type) {
      case 'user': return <FaUsers />;
      case 'topic': return <FaFileAlt />;
      case 'post': return <FaComments />;
      case 'system': return <FaCog />;
      default: return <FaChartLine />;
    }
  };

  if (loading) {
    return (
      <Flex fillWidth horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Загрузка админки...</Text>
          </Column>
        </Card>
      </Flex>
    );
  }

  return (
    <Column maxWidth="xl" gap="xl">
      {/* Заголовок */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Column gap="s">
          <Heading variant="display-strong-l">Панель управления</Heading>
          <Text variant="body-default-s" onBackground="neutral-weak">
            Добро пожаловать в панель администратора
          </Text>
        </Column>
        <Button 
          variant="primary" 
          prefixIcon="plus"
        >
          Быстрое действие
        </Button>
      </Flex>

      {/* Статистика */}
      {stats && (
        <Grid columns={4} gap="m">
          <Card padding="l" radius="m">
            <Column gap="s">
              <Flex horizontal="between" vertical="center">
                <Text variant="heading-strong-s">Всего пользователей</Text>
                <FaUsers size={24} className="text-blue-500" />
              </Flex>
              <Text variant="display-strong-xl">{stats.totalUsers}</Text>
              <Text variant="body-default-s" onBackground="neutral-weak">
                +{stats.newUsersToday} сегодня
              </Text>
            </Column>
          </Card>

          <Card padding="l" radius="m">
            <Column gap="s">
              <Flex horizontal="between" vertical="center">
                <Text variant="heading-strong-s">Всего тем</Text>
                <FaFileAlt size={24} className="text-green-500" />
              </Flex>
              <Text variant="display-strong-xl">{stats.totalTopics}</Text>
              <Text variant="body-default-s" onBackground="neutral-weak">
                +{stats.newTopicsToday} сегодня
              </Text>
            </Column>
          </Card>

          <Card padding="l" radius="m">
            <Column gap="s">
              <Flex horizontal="between" vertical="center">
                <Text variant="heading-strong-s">Всего сообщений</Text>
                <FaComments size={24} className="text-purple-500" />
              </Flex>
              <Text variant="display-strong-xl">{stats.totalPosts}</Text>
              <Text variant="body-default-s" onBackground="neutral-weak">
                +{stats.newPostsToday} сегодня
              </Text>
            </Column>
          </Card>

          <Card padding="l" radius="m">
            <Column gap="s">
              <Flex horizontal="between" vertical="center">
                <Text variant="heading-strong-s">Активные пользователи</Text>
                <FaChartLine size={24} className="text-orange-500" />
              </Flex>
              <Text variant="display-strong-xl">{stats.activeUsers}</Text>
              <Text variant="body-default-s" onBackground="neutral-weak">
                сейчас онлайн
              </Text>
            </Column>
          </Card>
        </Grid>
      )}

      {/* Статус системы */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Статус системы</Heading>
          <Grid columns={3} gap="m">
            <Flex gap="m" vertical="center">
              <Badge background="success-medium">
                {stats?.systemStatus === 'online' ? 'Онлайн' : 'Ошибка'}
              </Badge>
              <Text variant="body-default-s">Основная система</Text>
            </Flex>
            
            <Flex gap="m" vertical="center">
              <Badge background="success-medium">Онлайн</Badge>
              <Text variant="body-default-s">База данных</Text>
            </Flex>
            
            <Flex gap="m" vertical="center">
              <Badge background="success-medium">Онлайн</Badge>
              <Text variant="body-default-s">Кеш</Text>
            </Flex>
          </Grid>
        </Column>
      </Card>

      {/* Последняя активность и быстрые действия */}
      <Grid columns={2} gap="l">
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Последняя активность</Heading>
            <Column gap="m">
              {recentActivity.map((activity) => (
                <Flex key={activity.id} horizontal="between" vertical="center" paddingY="s">
                  <Flex gap="m" vertical="center">
                    <div className="text-gray-500">
                      {getActivityIcon(activity.type)}
                    </div>
                    <Column gap="xs">
                      <Text variant="body-default-s">{activity.title}</Text>
                      <Text variant="body-default-s" onBackground="neutral-weak">
                        {activity.description}
                      </Text>
                      <Flex gap="s" vertical="center">
                        <FaClock size={12} className="text-gray-400" />
                        <Text variant="body-default-xs" onBackground="neutral-weak">
                          {activity.time}
                        </Text>
                        {activity.user && (
                          <>
                            <Text variant="body-default-xs" onBackground="neutral-weak">•</Text>
                            <Text variant="body-default-xs" onBackground="neutral-medium">
                              {activity.user}
                            </Text>
                          </>
                        )}
                      </Flex>
                    </Column>
                  </Flex>
                </Flex>
              ))}
            </Column>
          </Column>
        </Card>

        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Быстрые действия</Heading>
            <Grid columns={2} gap="m">
              <Button 
                variant="secondary" 
                prefixIcon="person"
                className="justify-start h-12"
              >
                Управление пользователями
              </Button>
              
              <Button 
                variant="secondary" 
                prefixIcon="forum"
                className="justify-start h-12"
              >
                Управление форумами
              </Button>
              
              <Button 
                variant="secondary" 
                prefixIcon="settings"
                className="justify-start h-12"
              >
                Настройки системы
              </Button>
              
              <Button 
                variant="secondary" 
                prefixIcon="eye"
                className="justify-start h-12"
              >
                Просмотр логов
              </Button>
            </Grid>
          </Column>
        </Card>
      </Grid>
    </Column>
  );
} 