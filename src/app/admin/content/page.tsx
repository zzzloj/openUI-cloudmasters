"use client";

import React, { useState } from "react";
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

interface ContentItem {
  id: string;
  title: string;
  type: "page" | "post" | "project";
  status: "published" | "draft" | "archived";
  lastModified: string;
  author: string;
}

const mockContent: ContentItem[] = [
  {
    id: "1",
    title: "Главная страница",
    type: "page",
    status: "published",
    lastModified: "2025-08-03",
    author: "admin"
  },
  {
    id: "2",
    title: "О нас",
    type: "page",
    status: "published",
    lastModified: "2025-08-02",
    author: "admin"
  },
  {
    id: "3",
    title: "Building Once UI",
    type: "project",
    status: "published",
    lastModified: "2025-08-01",
    author: "admin"
  },
  {
    id: "4",
    title: "Новый пост",
    type: "post",
    status: "draft",
    lastModified: "2025-08-03",
    author: "admin"
  }
];

export default function ContentManagement() {
  const [content, setContent] = useState<ContentItem[]>(mockContent);
  const [selectedItem, setSelectedItem] = useState<ContentItem | null>(null);
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [filter, setFilter] = useState("all");

  const handleEdit = (item: ContentItem) => {
    setSelectedItem(item);
    setIsModalOpen(true);
  };

  const handleDelete = (id: string) => {
    if (confirm("Вы уверены, что хотите удалить этот элемент?")) {
      setContent(content.filter(item => item.id !== id));
    }
  };

  const handleSave = (updatedItem: ContentItem) => {
    setContent(content.map(item => 
      item.id === updatedItem.id ? updatedItem : item
    ));
    setIsModalOpen(false);
    setSelectedItem(null);
  };

  const handleCreate = (newItem: Omit<ContentItem, "id">) => {
    const item: ContentItem = {
      ...newItem,
      id: Date.now().toString(),
      lastModified: new Date().toISOString().split('T')[0]
    };
    setContent([...content, item]);
    setIsCreateModalOpen(false);
  };

  const filteredContent = filter === "all" 
    ? content 
    : content.filter(item => item.type === filter);

  return (
    <Column maxWidth="xl" gap="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Content Management - Admin Panel"
        description="Управление контентом сайта"
        path="/admin/content"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Heading variant="display-strong-l">Управление контентом</Heading>
        <Button 
          variant="primary" 
          prefixIcon="plus"
          onClick={() => setIsCreateModalOpen(true)}
        >
          Создать
        </Button>
      </Flex>

      {/* Filters */}
      <Card padding="l" radius="m">
        <Flex gap="m" vertical="center">
          <Text variant="body-default-s">Фильтр:</Text>
          <Select
            id="content-filter"
            value={filter}
            onChange={(e) => setFilter(e.target.value)}
            options={[
              { value: "all", label: "Все" },
              { value: "page", label: "Страницы" },
              { value: "post", label: "Посты" },
              { value: "project", label: "Проекты" }
            ]}
          />
        </Flex>
      </Card>

      {/* Content List */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Контент ({filteredContent.length})</Heading>
          
          <Grid columns={1} gap="m">
            {filteredContent.map((item) => (
              <Card key={item.id} padding="l" radius="m">
                <Flex fillWidth horizontal="between" vertical="center">
                  <Column gap="s">
                    <Text variant="heading-strong-s">{item.title}</Text>
                    <Flex gap="m" vertical="center">
                      <Badge 
                        background={item.type === "page" ? "brand-medium" : item.type === "post" ? "success-medium" : "warning-medium"}
                      >
                        {item.type}
                      </Badge>
                      <Badge 
                        background={item.status === "published" ? "success-medium" : item.status === "draft" ? "warning-medium" : "neutral-medium"}
                      >
                        {item.status}
                      </Badge>
                    </Flex>
                    <Text variant="body-default-s" onBackground="neutral-weak">
                      Автор: {item.author} | Изменен: {item.lastModified}
                    </Text>
                  </Column>
                  <Flex gap="s">
                    <Button
                      size="s"
                      variant="secondary"
                      prefixIcon="edit"
                      onClick={() => handleEdit(item)}
                    >
                      Изменить
                    </Button>
                    <Button
                      size="s"
                      variant="danger"
                      prefixIcon="delete"
                      onClick={() => handleDelete(item.id)}
                    >
                      Удалить
                    </Button>
                  </Flex>
                </Flex>
              </Card>
            ))}
          </Grid>
        </Column>
      </Card>

      {/* Simple Modal for Edit/Create */}
      {(isModalOpen || isCreateModalOpen) && (
        <Card 
          padding="xl" 
          radius="l" 
          style={{
            position: "fixed",
            top: "50%",
            left: "50%",
            transform: "translate(-50%, -50%)",
            zIndex: 1000,
            background: "var(--surface-color)",
            boxShadow: "0 10px 25px rgba(0,0,0,0.2)"
          }}
        >
          <ContentForm
            item={selectedItem || undefined}
            onSave={isModalOpen ? handleSave : handleCreate}
            onCancel={() => {
              setIsModalOpen(false);
              setIsCreateModalOpen(false);
              setSelectedItem(null);
            }}
          />
        </Card>
      )}
    </Column>
  );
}

interface ContentFormProps {
  item?: ContentItem;
  onSave: (item: ContentItem) => void;
  onCancel: () => void;
}

function ContentForm({ item, onSave, onCancel }: ContentFormProps) {
  const [formData, setFormData] = useState({
    title: item?.title || "",
    type: item?.type || "page",
    status: item?.status || "draft",
    author: item?.author || "admin"
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave({
      id: item?.id || "",
      ...formData,
      lastModified: item?.lastModified || new Date().toISOString().split('T')[0]
    });
  };

  return (
    <form onSubmit={handleSubmit}>
      <Column gap="l">
        <Heading variant="display-strong-s">
          {item ? "Редактировать контент" : "Создать новый контент"}
        </Heading>
        
        <Input
          id="content-title"
          label="Название"
          value={formData.title}
          onChange={(e) => setFormData({...formData, title: e.target.value})}
          required
        />
        
        <Select
          id="content-type"
          label="Тип"
          value={formData.type}
          onChange={(e) => setFormData({...formData, type: e.target.value as any})}
          options={[
            { value: "page", label: "Страница" },
            { value: "post", label: "Пост" },
            { value: "project", label: "Проект" }
          ]}
        />
        
        <Select
          id="content-status"
          label="Статус"
          value={formData.status}
          onChange={(e) => setFormData({...formData, status: e.target.value as any})}
          options={[
            { value: "draft", label: "Черновик" },
            { value: "published", label: "Опубликовано" },
            { value: "archived", label: "Архив" }
          ]}
        />
        
        <Input
          id="content-author"
          label="Автор"
          value={formData.author}
          onChange={(e) => setFormData({...formData, author: e.target.value})}
          required
        />
        
        <Flex gap="m" horizontal="end">
          <Button variant="secondary" onClick={onCancel}>
            Отмена
          </Button>
          <Button type="submit" variant="primary">
            {item ? "Сохранить" : "Создать"}
          </Button>
        </Flex>
      </Column>
    </form>
  );
} 