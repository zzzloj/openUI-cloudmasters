'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { Card, Button, Text, Column, Flex, Icon, Badge, Avatar, Heading } from '@once-ui-system/core';
import { 
  FaUsers, 
  FaComments, 
  FaCog, 
  FaTools, 
  FaChartBar, 
  FaSignOutAlt,
  FaUser,
  FaShieldAlt,
  FaChartLine
} from 'react-icons/fa';

interface AdminLayoutProps {
  children: React.ReactNode;
}

export default function AdminLayout({ children }: AdminLayoutProps) {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const response = await fetch('/api/auth/me', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });

      if (!response.ok) {
        router.push('/login');
        return;
      }

      const data = await response.json();
      
      // Проверяем права администратора (группа 4)
      if (data.user.member_group_id !== 4) {
        router.push('/');
        return;
      }

      setUser(data.user);
    } catch (error) {
      console.error('Ошибка проверки авторизации:', error);
      router.push('/login');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    localStorage.removeItem('token');
    router.push('/login');
  };

  const navigation = [
    {
      name: 'Главная',
      href: '/admin',
      icon: FaChartBar,
      description: 'Обзор и статистика'
    },
    {
      name: 'Пользователи',
      href: '/admin/users',
      icon: FaUsers,
      description: 'Управление участниками'
    },
    {
      name: 'Форумы',
      href: '/admin/forums',
      icon: FaComments,
      description: 'Управление разделами'
    },
    {
      name: 'Система',
      href: '/admin/system',
      icon: FaCog,
      description: 'Настройки и логи'
    },
    {
      name: 'Инструменты',
      href: '/admin/tools',
      icon: FaTools,
      description: 'Утилиты администрирования'
    }
  ];

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
    <Column fillWidth>
      {/* Header */}
      <Card padding="l" radius="s" shadow="s" background="page">
        <Flex fillWidth horizontal="between" vertical="center">
          <Flex gap="m" vertical="center">
            <FaShieldAlt size={32} className="text-blue-500" />
            <Heading variant="display-strong-s">Панель администратора</Heading>
          </Flex>
          
          <Flex gap="l" vertical="center">
            <Flex gap="s" vertical="center">
              <FaChartLine size={16} className="text-green-500" />
              <Text variant="body-default-s" onBackground="neutral-weak">Система активна</Text>
            </Flex>
            
            <Button variant="secondary" prefixIcon="person">
              <Text variant="body-default-s">{user?.members_display_name || 'Админ'}</Text>
            </Button>
            
            <Button variant="secondary" prefixIcon="logout" onClick={handleLogout}>
              Выйти
            </Button>
          </Flex>
        </Flex>
      </Card>

      {/* Navigation */}
      <Card padding="s" radius="s" shadow="s" background="neutral-weak">
        <Flex fillWidth gap="l" horizontal="start">
          {navigation.map((item) => {
            const IconComponent = item.icon;
            return (
                              <Button
                  key={item.name}
                  variant="secondary"
                  prefixIcon="arrowRight"
                  href={item.href}
                  className="flex items-center gap-2"
                >
                <IconComponent size={16} />
                <Text variant="body-default-s">{item.name}</Text>
              </Button>
            );
          })}
        </Flex>
      </Card>

      {/* Main content */}
      <Flex fillWidth horizontal="center" paddingY="xl">
        <Column maxWidth="xl" fillWidth>
          {children}
        </Column>
      </Flex>
    </Column>
  );
}
