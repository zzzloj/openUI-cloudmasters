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
  Input,
  Select,
  Schema
} from "@once-ui-system/core";
import { baseURL } from "@/resources";
import { useRouter } from "next/navigation";

interface ForumUser {
  id: number;
  name: string;
  email: string;
  display_name: string;
  member_group_id: number;
  joined: string;
  last_activity: string;
  posts: number;
  is_banned: boolean;
  title: string;
}

interface AdminUser {
  id: number;
  name: string;
  email: string;
  member_group_id: number;
}

export default function ForumUsersManagement() {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [users, setUsers] = useState<ForumUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [usersLoading, setUsersLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [groupFilter, setGroupFilter] = useState('all');
  const [selectedUser, setSelectedUser] = useState<ForumUser | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const router = useRouter();

  useEffect(() => {
    checkAuth();
  }, []);

  useEffect(() => {
    if (user) {
      loadUsers();
    }
  }, [user]);

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

  const loadUsers = async () => {
    try {
      setUsersLoading(true);
      const token = localStorage.getItem('authToken');
      const response = await fetch("/api/admin/forum/users", {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Cache-Control': 'no-cache'
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        console.log('Загружены пользователи:', data.users.length);
        console.log('Пример пользователя:', data.users[0]);
        setUsers(data.users);
      } else {
        console.error('Ошибка загрузки пользователей:', response.status);
      }
    } catch (error) {
      console.error("Ошибка загрузки пользователей:", error);
    } finally {
      setUsersLoading(false);
    }
  };

  const handleEditUser = (user: ForumUser) => {
    setSelectedUser(user);
    setIsModalOpen(true);
  };

  const handleBanUser = async (userId: number, ban: boolean) => {
    try {
      const token = localStorage.getItem('authToken');
      const response = await fetch(`/api/admin/forum/users/${userId}/ban`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ ban })
      });
      
      if (response.ok) {
        await loadUsers(); // Перезагружаем список
      }
    } catch (error) {
      console.error("Ошибка изменения статуса пользователя:", error);
    }
  };

  const handleChangeGroup = async (userId: number, groupId: number) => {
    try {
      const token = localStorage.getItem('authToken');
      const response = await fetch(`/api/admin/forum/users/${userId}/group`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({ group_id: groupId })
      });
      
      if (response.ok) {
        await loadUsers(); // Перезагружаем список
      }
    } catch (error) {
      console.error("Ошибка изменения группы пользователя:", error);
    }
  };

  const getGroupName = (groupId: number) => {
    switch (groupId) {
      case 1: return 'Пользователь';
      case 2: return 'VIP Пользователь';
      case 3: return 'Обычный пользователь';
      case 4: return 'Администратор';
      case 5: return 'Модератор';
      default: return 'Пользователь';
    }
  };

  const getGroupColor = (groupId: number) => {
    switch (groupId) {
      case 1: return 'neutral-medium';
      case 2: return 'success-medium';
      case 3: return 'info-medium';
      case 4: return 'danger-medium';
      case 5: return 'warning-medium';
      default: return 'neutral-medium';
    }
  };

  const filteredUsers = users.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         user.display_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesGroup = groupFilter === 'all' || user.member_group_id.toString() === groupFilter;
    return matchesSearch && matchesGroup;
  });

  if (loading) {
    return (
      <Column maxWidth="xl" gap="xl" horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l">
          <Column gap="l" horizontal="center">
            <Icon name="spinner" size="l" />
            <Text>Загрузка...</Text>
          </Column>
        </Card>
      </Column>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <Column maxWidth="xl" gap="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Forum Users Management - Admin Panel"
        description="Управление пользователями форума"
        path="/admin/forum/users"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l" style={{ flexWrap: 'wrap', gap: '1rem' }}>
        <Heading variant="display-strong-l">Управление пользователями</Heading>
        <Button 
          variant="secondary" 
          prefixIcon="arrow-left"
          href="/admin/forum"
        >
          Назад
        </Button>
      </Flex>

      {/* Filters */}
      <Card padding="l" radius="m">
        <Grid columns={2} gap="m" style={{ 
          gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))' 
        }}>
          <Input
            id="user-search"
            placeholder="Поиск пользователей..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
          <Select
            id="group-filter"
            value={groupFilter}
            onChange={(e) => setGroupFilter(e.target.value)}
            options={[
              { value: 'all', label: 'Все группы' },
              { value: '1', label: 'Пользователи' },
              { value: '2', label: 'VIP Пользователи' },
              { value: '3', label: 'Обычные пользователи' },
              { value: '4', label: 'Администраторы' },
              { value: '5', label: 'Модераторы' }
            ]}
          />
        </Grid>
      </Card>

      {/* Users List */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Flex horizontal="between" vertical="center">
            <Heading variant="display-strong-s">
              Пользователи ({filteredUsers.length})
            </Heading>
            {usersLoading && <Icon name="spinner" size="m" />}
          </Flex>
          
          <Grid columns={1} gap="m">
            {filteredUsers.map((user) => (
              <Card key={user.id} padding="l" radius="m">
                <Column gap="m">
                  <Flex fillWidth horizontal="between" vertical="center" style={{ flexWrap: 'wrap', gap: '1rem' }}>
                    <Column gap="s">
                      <Flex gap="m" vertical="center" style={{ flexWrap: 'wrap' }}>
                        <Text variant="heading-strong-s">{user.display_name}</Text>
                        <Badge background={getGroupColor(user.member_group_id)}>
                          {getGroupName(user.member_group_id)}
                        </Badge>
                        {user.is_banned && (
                          <Badge background="danger-medium">Заблокирован</Badge>
                        )}
                      </Flex>
                      <Text variant="body-default-s" onBackground="neutral-weak">
                        {user.email} • {user.posts} сообщений
                      </Text>
                      <Text variant="body-default-s" onBackground="neutral-weak">
                        Регистрация: {new Date(user.joined).toLocaleDateString('ru-RU')} • 
                        Последняя активность: {new Date(user.last_activity).toLocaleDateString('ru-RU')}
                      </Text>
                    </Column>
                    <Flex gap="s" style={{ flexWrap: 'wrap' }}>
                      <Button
                        size="s"
                        variant="secondary"
                        prefixIcon="edit"
                        onClick={() => handleEditUser(user)}
                      >
                        Изменить
                      </Button>
                      {!user.is_banned ? (
                        <Button
                          size="s"
                          variant="danger"
                          prefixIcon="ban"
                          onClick={() => handleBanUser(user.id, true)}
                        >
                          Заблокировать
                        </Button>
                      ) : (
                        <Button
                          size="s"
                          variant="secondary"
                          prefixIcon="check"
                          onClick={() => handleBanUser(user.id, false)}
                        >
                          Разблокировать
                        </Button>
                      )}
                    </Flex>
                  </Flex>
                </Column>
              </Card>
            ))}
          </Grid>
        </Column>
      </Card>

      {/* User Edit Modal */}
      {isModalOpen && selectedUser && (
        <div 
          style={{
            position: "fixed",
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: "rgba(0,0,0,0.8)",
            zIndex: 9999,
            display: "flex",
            alignItems: "center",
            justifyContent: "center",
            padding: "20px",
            backdropFilter: "blur(4px)"
          }}
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              setIsModalOpen(false);
              setSelectedUser(null);
            }
          }}
        >
          <div 
            style={{
              backgroundColor: "#ffffff",
              borderRadius: "12px",
              padding: "32px",
              maxWidth: "600px",
              width: "100%",
              maxHeight: "90vh",
              overflow: "auto",
              boxShadow: "0 25px 50px rgba(0,0,0,0.8)",
              border: "1px solid #e0e0e0",
              position: "relative",
              zIndex: 10000
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <UserEditForm
              user={selectedUser}
              onSave={async (updatedUser) => {
                try {
                  const token = localStorage.getItem('authToken');
                  const response = await fetch(`/api/admin/forum/users/${updatedUser.id}/update`, {
                    method: 'PUT',
                    headers: {
                      'Content-Type': 'application/json',
                      'Authorization': `Bearer ${token}`
                    },
                    body: JSON.stringify({
                      display_name: updatedUser.display_name,
                      title: updatedUser.title,
                      member_group_id: updatedUser.member_group_id
                    })
                  });

                  if (response.ok) {
                    setIsModalOpen(false);
                    setSelectedUser(null);
                    await loadUsers();
                  } else {
                    const error = await response.json();
                    alert(`Ошибка обновления: ${error.error}`);
                  }
                } catch (error) {
                  console.error('Error updating user:', error);
                  alert('Ошибка при обновлении пользователя');
                }
              }}
              onCancel={() => {
                setIsModalOpen(false);
                setSelectedUser(null);
              }}
            />
          </div>
        </div>
      )}
    </Column>
  );
}

interface UserEditFormProps {
  user: ForumUser;
  onSave: (user: ForumUser) => void;
  onCancel: () => void;
}

function UserEditForm({ user, onSave, onCancel }: UserEditFormProps) {
  const [formData, setFormData] = useState({
    display_name: user.display_name,
    title: user.title,
    member_group_id: user.member_group_id
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave({
      ...user,
      ...formData
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <Column gap="l">
        <Heading variant="display-strong-s">Редактировать пользователя</Heading>
        
        <Input
          id="display-name"
          label="Отображаемое имя"
          value={formData.display_name}
          onChange={(e) => setFormData({...formData, display_name: e.target.value})}
          required
        />
        
        <Input
          id="title"
          label="Должность/статус"
          value={formData.title}
          onChange={(e) => setFormData({...formData, title: e.target.value})}
        />
        
        <Select
          id="member-group"
          label="Группа пользователя"
          value={formData.member_group_id.toString()}
          onChange={(e) => setFormData({...formData, member_group_id: parseInt(e.target.value)})}
          options={[
            { value: "1", label: "Пользователь" },
            { value: "2", label: "VIP Пользователь" },
            { value: "3", label: "Модератор" },
            { value: "4", label: "Администратор" }
          ]}
        />
        
        <Flex gap="m">
          <Button type="submit" variant="primary">
            Сохранить
          </Button>
          <Button type="button" variant="secondary" onClick={onCancel}>
            Отмена
          </Button>
        </Flex>
      </Column>
    </form>
  );
}
