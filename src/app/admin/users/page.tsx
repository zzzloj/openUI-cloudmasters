'use client';

import { useEffect, useState } from 'react';
import { Card, Button, Text, Column, Flex, Icon, Badge, Heading, Input, Select, Grid } from '@once-ui-system/core';
import { 
  FaUsers, 
  FaSearch, 
  FaEdit, 
  FaBan, 
  FaUserShield,
  FaUserCheck,
  FaUserTimes,
  FaCrown,
  FaUser,
  FaPlus
} from 'react-icons/fa';

interface User {
  id: number;
  member_id: number;
  members_display_name: string;
  name: string;
  email: string;
  member_group_id: number;
  member_banned: number;
  joined: string;
  last_activity: string;
  posts: number;
  topics: number;
}

interface UserGroup {
  id: number;
  name: string;
  color: string;
}

export default function UsersAdminPage() {
  const [users, setUsers] = useState<User[]>([]);
  const [filteredUsers, setFilteredUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedGroup, setSelectedGroup] = useState<string>('all');
  const [selectedUser, setSelectedUser] = useState<User | null>(null);
  const [showEditModal, setShowEditModal] = useState(false);

  const userGroups: UserGroup[] = [
    { id: 1, name: 'Пользователь', color: 'neutral-medium' },
    { id: 2, name: 'VIP', color: 'warning-medium' },
    { id: 3, name: 'Модератор', color: 'success-medium' },
    { id: 4, name: 'Администратор', color: 'danger-medium' },
    { id: 5, name: 'Модератор', color: 'success-medium' }
  ];

  useEffect(() => {
    loadUsers();
  }, []);

  useEffect(() => {
    filterUsers();
  }, [users, searchTerm, selectedGroup]);

  const loadUsers = async () => {
    try {
      const response = await fetch('/api/admin/forum/users', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Cache-Control': 'no-cache'
        }
      });

      if (response.ok) {
        const data = await response.json();
        setUsers(data.users);
      }
    } catch (error) {
      console.error('Ошибка загрузки пользователей:', error);
    } finally {
      setLoading(false);
    }
  };

  const filterUsers = () => {
    let filtered = users;

    // Фильтр по поиску
    if (searchTerm) {
      filtered = filtered.filter(user => 
        user.members_display_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        user.email.toLowerCase().includes(searchTerm.toLowerCase())
      );
    }

    // Фильтр по группе
    if (selectedGroup !== 'all') {
      filtered = filtered.filter(user => user.member_group_id === parseInt(selectedGroup));
    }

    setFilteredUsers(filtered);
  };

  const getGroupName = (groupId: number) => {
    const group = userGroups.find(g => g.id === groupId);
    return group ? group.name : 'Неизвестно';
  };

  const getGroupColor = (groupId: number) => {
    const group = userGroups.find(g => g.id === groupId);
    return group ? group.color : 'neutral-medium';
  };

  const handleBanUser = async (userId: number, banned: boolean) => {
    try {
      const response = await fetch(`/api/admin/forum/users/${userId}/ban`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({ banned: !banned })
      });

      if (response.ok) {
        // Обновляем список пользователей
        setUsers(users.map(user => 
          user.member_id === userId 
            ? { ...user, member_banned: banned ? 0 : 1 }
            : user
        ));
      }
    } catch (error) {
      console.error('Ошибка изменения статуса бана:', error);
    }
  };

  const handleChangeGroup = async (userId: number, newGroupId: number) => {
    try {
      const response = await fetch(`/api/admin/forum/users/${userId}/group`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({ groupId: newGroupId })
      });

      if (response.ok) {
        // Обновляем список пользователей
        setUsers(users.map(user => 
          user.member_id === userId 
            ? { ...user, member_group_id: newGroupId }
            : user
        ));
      }
    } catch (error) {
      console.error('Ошибка изменения группы:', error);
    }
  };

  const handleEditUser = (user: User) => {
    setSelectedUser(user);
    setShowEditModal(true);
  };

  const handleSaveUser = async () => {
    if (!selectedUser) return;

    try {
      const response = await fetch(`/api/admin/forum/users/${selectedUser.member_id}/update`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          displayName: selectedUser.members_display_name,
          groupId: selectedUser.member_group_id
        })
      });

      if (response.ok) {
        // Обновляем список пользователей
        setUsers(users.map(user => 
          user.member_id === selectedUser.member_id 
            ? selectedUser
            : user
        ));
        setShowEditModal(false);
        setSelectedUser(null);
      }
    } catch (error) {
      console.error('Ошибка обновления пользователя:', error);
    }
  };

  if (loading) {
    return (
      <Flex fillWidth horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Загрузка пользователей...</Text>
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
          <Heading variant="display-strong-l">Управление пользователями</Heading>
          <Text variant="body-default-s" onBackground="neutral-weak">
            Всего пользователей: {users.length}
          </Text>
        </Column>
        <Button variant="primary" prefixIcon="plus">
          Добавить пользователя
        </Button>
      </Flex>

      {/* Фильтры */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Фильтры</Heading>
          <Grid columns={3} gap="m">
            <Input
              placeholder="Поиск по имени, email..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              prefixIcon="search"
            />
            <Select
              placeholder="Выберите группу"
              value={selectedGroup}
              onChange={(e) => setSelectedGroup(e.target.value)}
            >
              <option value="all">Все группы</option>
              {userGroups.map(group => (
                <option key={group.id} value={group.id.toString()}>
                  {group.name}
                </option>
              ))}
            </Select>
            <Button variant="secondary" onClick={loadUsers}>
              Обновить
            </Button>
          </Grid>
        </Column>
      </Card>

      {/* Список пользователей */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Список пользователей</Heading>
          <Column gap="m">
            {filteredUsers.map((user) => (
              <Card key={user.member_id} padding="m" radius="m" shadow="s">
                <Flex fillWidth horizontal="between" vertical="center">
                  <Flex gap="m" vertical="center">
                    <Avatar 
                      name={user.members_display_name}
                      size="m"
                    />
                    <Column gap="xs">
                      <Flex gap="s" vertical="center">
                        <Text variant="body-default-s" fontWeight="bold">
                          {user.members_display_name}
                        </Text>
                        <Badge background={getGroupColor(user.member_group_id)}>
                          {getGroupName(user.member_group_id)}
                        </Badge>
                        {user.member_banned === 1 && (
                          <Badge background="danger-medium">
                            Забанен
                          </Badge>
                        )}
                      </Flex>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {user.email} • {user.name}
                      </Text>
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        Сообщений: {user.posts} • Тем: {user.topics}
                      </Text>
                    </Column>
                  </Flex>
                  
                  <Flex gap="s">
                    <Button 
                      variant="secondary" 
                      size="s"
                      prefixIcon="edit"
                      onClick={() => handleEditUser(user)}
                    >
                      Редактировать
                    </Button>
                    {user.member_banned === 1 ? (
                      <Button 
                        variant="success" 
                        size="s"
                        prefixIcon="userCheck"
                        onClick={() => handleBanUser(user.member_id, true)}
                      >
                        Разбанить
                      </Button>
                    ) : (
                      <Button 
                        variant="danger" 
                        size="s"
                        prefixIcon="userTimes"
                        onClick={() => handleBanUser(user.member_id, false)}
                      >
                        Забанить
                      </Button>
                    )}
                  </Flex>
                </Flex>
              </Card>
            ))}
          </Column>
        </Column>
      </Card>

      {/* Модальное окно редактирования */}
      {showEditModal && selectedUser && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <Card padding="xl" radius="l" shadow="l" maxWidth="m">
            <Column gap="l">
              <Heading variant="display-strong-s">Редактировать пользователя</Heading>
              
              <Column gap="m">
                <Input
                  label="Отображаемое имя"
                  value={selectedUser.members_display_name}
                  onChange={(e) => setSelectedUser({
                    ...selectedUser,
                    members_display_name: e.target.value
                  })}
                />
                
                <Select
                  label="Группа"
                  value={selectedUser.member_group_id.toString()}
                  onChange={(e) => setSelectedUser({
                    ...selectedUser,
                    member_group_id: parseInt(e.target.value)
                  })}
                >
                  {userGroups.map(group => (
                    <option key={group.id} value={group.id.toString()}>
                      {group.name}
                    </option>
                  ))}
                </Select>
              </Column>
              
              <Flex gap="m" horizontal="end">
                <Button 
                  variant="secondary" 
                  onClick={() => {
                    setShowEditModal(false);
                    setSelectedUser(null);
                  }}
                >
                  Отмена
                </Button>
                <Button 
                  variant="primary" 
                  onClick={handleSaveUser}
                >
                  Сохранить
                </Button>
              </Flex>
            </Column>
          </Card>
        </div>
      )}
    </Column>
  );
}
