import React from "react";
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
  Schema
} from "@once-ui-system/core";
import { baseURL } from "@/resources";

export default function AdminDashboard() {
  return (
    <Column maxWidth="xl" gap="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Admin Panel - CloudMasters"
        description="Админ-панель для управления контентом"
        path="/admin"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Heading variant="display-strong-l">Админ-панель</Heading>
        <Button 
          variant="secondary" 
          prefixIcon="logout"
        >
          Выйти
        </Button>
      </Flex>

      {/* Dashboard Stats */}
      <Grid columns={3} gap="m">
        <Card padding="l" radius="m">
          <Column gap="s">
            <Flex horizontal="between" vertical="center">
              <Text variant="heading-strong-s">Контент</Text>
              <Icon name="document" size="l" />
            </Flex>
            <Text variant="display-strong-xl">12</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Страниц контента
            </Text>
          </Column>
        </Card>

        <Card padding="l" radius="m">
          <Column gap="s">
            <Flex horizontal="between" vertical="center">
              <Text variant="heading-strong-s">SEO</Text>
              <Icon name="search" size="l" />
            </Flex>
            <Text variant="display-strong-xl">8.5</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Рейтинг SEO
            </Text>
          </Column>
        </Card>

        <Card padding="l" radius="m">
          <Column gap="s">
            <Flex horizontal="between" vertical="center">
              <Text variant="heading-strong-s">Настройки</Text>
              <Icon name="settings" size="l" />
            </Flex>
            <Text variant="display-strong-xl">24</Text>
            <Text variant="body-default-s" onBackground="neutral-weak">
              Параметров
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
              prefixIcon="edit"
              href="/admin/content"
            >
              Управление контентом
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="search"
              href="/admin/seo"
            >
              SEO настройки
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="settings"
              href="/admin/settings"
            >
              Настройки сайта
            </Button>
            <Button 
              variant="secondary" 
              prefixIcon="analytics"
              href="/admin/analytics"
            >
              Аналитика
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
                <Icon name="edit" size="s" />
                <Text>Обновлена главная страница</Text>
              </Flex>
              <Badge background="success-medium">2 часа назад</Badge>
            </Flex>
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="search" size="s" />
                <Text>Изменены SEO мета-теги</Text>
              </Flex>
              <Badge background="info-medium">1 день назад</Badge>
            </Flex>
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Flex gap="m" vertical="center">
                <Icon name="settings" size="s" />
                <Text>Обновлены настройки темы</Text>
              </Flex>
              <Badge background="warning-medium">3 дня назад</Badge>
            </Flex>
          </Column>
        </Column>
      </Card>
    </Column>
  );
} 