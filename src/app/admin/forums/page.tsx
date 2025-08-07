'use client';

import { useEffect, useState } from 'react';
import { Card, Button, Text, Column, Flex, Icon, Badge, Heading, Input, Select, Grid } from '@once-ui-system/core';
import { 
  FaComments, 
  FaPlus, 
  FaEdit, 
  FaTrash, 
  FaFolder
} from 'react-icons/fa';

interface Forum {
  id: number;
  name: string;
  description: string;
  topics: number;
  posts: number;
  last_post: string;
  last_poster: string;
  status: 'online' | 'offline';
}

export default function ForumsAdminPage() {
  const [forums, setForums] = useState<Forum[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedForum, setSelectedForum] = useState<Forum | null>(null);
  const [showEditModal, setShowEditModal] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);

  useEffect(() => {
    loadForums();
  }, []);

  const loadForums = async () => {
    try {
      const response = await fetch('/api/admin/forum/categories', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });

      if (response.ok) {
        const data = await response.json();
        setForums(data.forums);
      }
    } catch (error) {
      console.error('Ошибка загрузки форумов:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleEditForum = (forum: Forum) => {
    setSelectedForum(forum);
    setShowEditModal(true);
  };

  if (loading) {
    return (
      <Flex fillWidth horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Загрузка форумов...</Text>
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
          <Heading variant="display-strong-l">Управление форумами</Heading>
          <Text variant="body-default-s" onBackground="neutral-weak">
            Всего форумов: {forums.length}
          </Text>
        </Column>
        <Button 
          variant="primary" 
          prefixIcon="plus"
          onClick={() => setShowCreateModal(true)}
        >
          Создать форум
        </Button>
      </Flex>

      {/* Список форумов */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Список форумов</Heading>
          <Column gap="m">
            {forums.map((forum) => (
              <Card key={forum.id} padding="m" radius="m" shadow="s">
                <Flex fillWidth horizontal="between" vertical="center">
                  <Flex gap="m" vertical="center">
                    <FaFolder size={24} className="text-blue-500" />
                    <Column gap="xs">
                      <Flex gap="s" vertical="center">
                        <Text variant="body-default-s" style={{ fontWeight: 'bold' }}>
                          {forum.name}
                        </Text>
                        <Badge background="success-medium">
                          Активен
                        </Badge>
                      </Flex>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {forum.description}
                      </Text>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        Тем: {forum.topics} • Сообщений: {forum.posts}
                      </Text>
                    </Column>
                  </Flex>
                  
                  <Flex gap="s">
                    <Button 
                      variant="secondary" 
                      size="s"
                      prefixIcon="edit"
                      onClick={() => handleEditForum(forum)}
                    >
                      Редактировать
                    </Button>
                  </Flex>
                </Flex>
              </Card>
            ))}
          </Column>
        </Column>
      </Card>
    </Column>
  );
}
