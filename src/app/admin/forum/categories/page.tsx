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

interface ForumCategory {
  id: number;
  name: string;
  description: string;
  topics_count: number;
  posts_count: number;
  last_topic_id: number | null;
  last_topic_title: string | null;
  last_poster_id: number | null;
  last_poster_name: string | null;
  last_post_date: string | null;
  position: number;
  is_active: boolean;
}

interface AdminUser {
  id: number;
  name: string;
  email: string;
  member_group_id: number;
}

export default function ForumCategoriesManagement() {
  const [user, setUser] = useState<AdminUser | null>(null);
  const [categories, setCategories] = useState<ForumCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [categoriesLoading, setCategoriesLoading] = useState(true);
  const [selectedCategory, setSelectedCategory] = useState<ForumCategory | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const router = useRouter();

  useEffect(() => {
    checkAuth();
  }, []);

  useEffect(() => {
    if (user) {
      loadCategories();
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

  const loadCategories = async () => {
    try {
      setCategoriesLoading(true);
      const token = localStorage.getItem('authToken');
      const response = await fetch("/api/admin/forum/categories", {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        setCategories(data.categories);
      }
    } catch (error) {
      console.error("Ошибка загрузки категорий:", error);
    } finally {
      setCategoriesLoading(false);
    }
  };

  const handleEditCategory = (category: ForumCategory) => {
    setSelectedCategory(category);
    setIsModalOpen(true);
  };

  const handleCreateCategory = () => {
    setIsCreateModalOpen(true);
  };

  const handleDeleteCategory = async (categoryId: number) => {
    if (!confirm('Вы уверены, что хотите удалить эту категорию? Все темы и сообщения будут потеряны.')) {
      return;
    }

    try {
      const token = localStorage.getItem('authToken');
      const response = await fetch(`/api/admin/forum/categories/${categoryId}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      
      if (response.ok) {
        await loadCategories();
      }
    } catch (error) {
      console.error("Ошибка удаления категории:", error);
    }
  };

  const formatDate = (dateString: string | null) => {
    if (!dateString) return 'Нет активности';
    return new Date(dateString).toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

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
        title="Forum Categories Management - Admin Panel"
        description="Управление категориями форума"
        path="/admin/forum/categories"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l" style={{ flexWrap: 'wrap', gap: '1rem' }}>
        <Heading variant="display-strong-l">Управление категориями</Heading>
        <Flex gap="m" style={{ flexWrap: 'wrap' }}>
          <Button 
            variant="primary" 
            prefixIcon="plus"
            onClick={handleCreateCategory}
          >
            Создать категорию
          </Button>
          <Button 
            variant="secondary" 
            prefixIcon="arrow-left"
            href="/admin/forum"
          >
            Назад
          </Button>
        </Flex>
      </Flex>

      {/* Categories List */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Flex horizontal="between" vertical="center">
            <Heading variant="display-strong-s">
              Категории ({categories.length})
            </Heading>
            {categoriesLoading && <Icon name="spinner" size="m" />}
          </Flex>
          
          <Grid columns={1} gap="m">
            {categories.map((category) => (
              <Card key={category.id} padding="l" radius="m" style={{ height: 'fit-content' }}>
                <Column gap="m">
                  <Flex fillWidth horizontal="between" vertical="center" style={{ flexWrap: 'wrap', gap: '1rem' }}>
                    <Column gap="s" style={{ flex: 1, minWidth: '200px' }}>
                      <Flex gap="m" vertical="center" style={{ flexWrap: 'wrap' }}>
                        <Text variant="heading-strong-s">{category.name}</Text>
                        <Badge background={category.is_active ? "success-medium" : "neutral-medium"}>
                          {category.is_active ? "Активна" : "Неактивна"}
                        </Badge>
                        <Text variant="body-default-s" onBackground="neutral-weak">
                          Позиция: {category.position}
                        </Text>
                      </Flex>
                      <Text variant="body-default-s" onBackground="neutral-weak" style={{ wordBreak: 'break-word' }}>
                        {category.description || 'Описание отсутствует'}
                      </Text>
                    </Column>
                    <Flex gap="s" style={{ flexWrap: 'wrap', flexShrink: 0 }}>
                      <Button
                        size="s"
                        variant="secondary"
                        prefixIcon="edit"
                        onClick={() => handleEditCategory(category)}
                      >
                        Изменить
                      </Button>
                      <Button
                        size="s"
                        variant="danger"
                        prefixIcon="delete"
                        onClick={() => handleDeleteCategory(category.id)}
                      >
                        Удалить
                      </Button>
                    </Flex>
                  </Flex>
                  
                  <Grid columns={2} gap="m" style={{ 
                    gridTemplateColumns: 'repeat(auto-fit, minmax(120px, 1fr))' 
                  }}>
                    <Flex gap="s" vertical="center">
                      <Icon name="message" size="s" />
                      <Column gap="xs">
                        <Text variant="body-strong-s">{category.topics_count}</Text>
                        <Text variant="body-default-s" onBackground="neutral-weak">Тем</Text>
                      </Column>
                    </Flex>
                    <Flex gap="s" vertical="center">
                      <Icon name="chat" size="s" />
                      <Column gap="xs">
                        <Text variant="body-strong-s">{category.posts_count}</Text>
                        <Text variant="body-default-s" onBackground="neutral-weak">Сообщений</Text>
                      </Column>
                    </Flex>
                  </Grid>
                  
                  {category.last_topic_title && (
                    <Text variant="body-default-s" onBackground="neutral-weak" style={{ wordBreak: 'break-word' }}>
                      Последняя тема: {category.last_topic_title} • {formatDate(category.last_post_date)}
                    </Text>
                  )}
                </Column>
              </Card>
            ))}
          </Grid>
        </Column>
      </Card>

      {/* Category Edit/Create Modal */}
      {(isModalOpen || isCreateModalOpen) && (
        <div style={{
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
            setIsCreateModalOpen(false);
            setSelectedCategory(null);
          }
        }}
        >
          <Card 
            padding="xl" 
            radius="l" 
            style={{
              maxWidth: "600px",
              width: "100%",
              maxHeight: "90vh",
              overflow: "auto",
              backgroundColor: "#ffffff",
              boxShadow: "0 25px 50px rgba(0,0,0,0.8)",
              border: "1px solid #e0e0e0",
              position: "relative",
              zIndex: 10000
            }}
            onClick={(e) => e.stopPropagation()}
          >
            <CategoryForm
              category={selectedCategory || undefined}
              onSave={async (updatedCategory) => {
                try {
                  const token = localStorage.getItem('authToken');
                  
                  if (updatedCategory.id === 0) {
                    // Создание новой категории
                    const response = await fetch('/api/admin/forum/categories/create', {
                      method: 'POST',
                      headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                      },
                      body: JSON.stringify({
                        name: updatedCategory.name,
                        description: updatedCategory.description,
                        position: updatedCategory.position,
                        is_active: updatedCategory.is_active
                      })
                    });

                    if (response.ok) {
                      setIsModalOpen(false);
                      setIsCreateModalOpen(false);
                      setSelectedCategory(null);
                      await loadCategories();
                    } else {
                      const error = await response.json();
                      alert(`Ошибка создания: ${error.error}`);
                    }
                  } else {
                    // Обновление существующей категории
                    const response = await fetch(`/api/admin/forum/categories/${updatedCategory.id}/update`, {
                      method: 'PUT',
                      headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${token}`
                      },
                      body: JSON.stringify({
                        name: updatedCategory.name,
                        description: updatedCategory.description,
                        position: updatedCategory.position,
                        is_active: updatedCategory.is_active
                      })
                    });

                    if (response.ok) {
                      setIsModalOpen(false);
                      setIsCreateModalOpen(false);
                      setSelectedCategory(null);
                      await loadCategories();
                    } else {
                      const error = await response.json();
                      alert(`Ошибка обновления: ${error.error}`);
                    }
                  }
                } catch (error) {
                  console.error('Error saving category:', error);
                  alert('Ошибка при сохранении категории');
                }
              }}
              onCancel={() => {
                setIsModalOpen(false);
                setIsCreateModalOpen(false);
                setSelectedCategory(null);
              }}
            />
          </Card>
        </div>
      )}
    </Column>
  );
}

interface CategoryFormProps {
  category?: ForumCategory;
  onSave: (category: ForumCategory) => void;
  onCancel: () => void;
}

function CategoryForm({ category, onSave, onCancel }: CategoryFormProps) {
  const [formData, setFormData] = useState({
    name: category?.name || "",
    description: category?.description || "",
    position: category?.position || 1,
    is_active: category?.is_active ?? true
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave({
      id: category?.id || 0,
      ...formData,
      topics_count: category?.topics_count || 0,
      posts_count: category?.posts_count || 0,
      last_topic_id: category?.last_topic_id || null,
      last_topic_title: category?.last_topic_title || null,
      last_poster_id: category?.last_poster_id || null,
      last_poster_name: category?.last_poster_name || null,
      last_post_date: category?.last_post_date || null
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <Column gap="l">
        <Heading variant="display-strong-s">
          {category ? "Редактировать категорию" : "Создать новую категорию"}
        </Heading>
        
        <Input
          id="name"
          label="Название категории"
          value={formData.name}
          onChange={(e) => setFormData({...formData, name: e.target.value})}
          required
        />
        
        <textarea
          id="description-textarea"
          value={formData.description}
          onChange={(e) => setFormData({...formData, description: e.target.value})}
          placeholder="Описание категории"
          style={{
            width: '100%',
            minHeight: '80px',
            padding: '8px 12px',
            border: '1px solid var(--neutral-alpha-medium)',
            borderRadius: '6px',
            fontSize: '14px',
            fontFamily: 'inherit',
            resize: 'vertical'
          }}
        />
        
        <Input
          id="position"
          label="Позиция"
          type="number"
          value={formData.position.toString()}
          onChange={(e) => setFormData({...formData, position: parseInt(e.target.value)})}
          required
        />
        
        <Flex gap="s" vertical="center">
          <input
            type="checkbox"
            id="is_active"
            checked={formData.is_active}
            onChange={(e) => setFormData({...formData, is_active: e.target.checked})}
          />
          <Text variant="body-default-s">Активная категория</Text>
        </Flex>
        
        <Flex gap="m" style={{ flexWrap: 'wrap' }}>
          <Button type="submit" variant="primary">
            {category ? "Сохранить" : "Создать"}
          </Button>
          <Button type="button" variant="secondary" onClick={onCancel}>
            Отмена
          </Button>
        </Flex>
      </Column>
    </form>
  );
}
