'use client';

import { useEffect, useState } from 'react';
import { Card, Button, Text, Column, Flex, Icon, Badge, Heading, Input, Select, Grid } from '@once-ui-system/core';
import { 
  FaTools, 
  FaEnvelope, 
  FaExclamationTriangle,
  FaTrash,
  FaDownload,
  FaUpload,
  FaSearch,
  FaChartBar
} from 'react-icons/fa';

export default function ToolsAdminPage() {
  const [bulkMailData, setBulkMailData] = useState({
    subject: '',
    message: '',
    group: 'all'
  });

  return (
    <Column maxWidth="xl" gap="xl">
      {/* Заголовок */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Column gap="s">
          <Heading variant="display-strong-l">Инструменты администрирования</Heading>
          <Text variant="body-default-s" onBackground="neutral-weak">
            Утилиты для управления форумом
          </Text>
        </Column>
      </Flex>

      {/* Массовая рассылка */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Массовая рассылка</Heading>
          <Column gap="m">
            <Input
              label="Тема письма"
              placeholder="Введите тему письма"
              value={bulkMailData.subject}
              onChange={(e) => setBulkMailData({
                ...bulkMailData,
                subject: e.target.value
              })}
            />
            <Input
              label="Сообщение"
              placeholder="Введите текст сообщения"
              value={bulkMailData.message}
              onChange={(e) => setBulkMailData({
                ...bulkMailData,
                message: e.target.value
              })}
            />
            <Select
              label="Группа получателей"
              value={bulkMailData.group}
              onChange={(e) => setBulkMailData({
                ...bulkMailData,
                group: e.target.value
              })}
            >
              <option value="all">Все пользователи</option>
              <option value="1">Пользователи</option>
              <option value="2">VIP</option>
              <option value="3">Модераторы</option>
              <option value="4">Администраторы</option>
            </Select>
            <Button variant="primary" prefixIcon="envelope">
              Отправить рассылку
            </Button>
          </Column>
        </Column>
      </Card>

      {/* Инструменты модерации */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Инструменты модерации</Heading>
          <Grid columns={2} gap="m">
            <Button variant="secondary" prefixIcon="search">
              Поиск контента
            </Button>
            <Button variant="secondary" prefixIcon="trash">
              Массовое удаление
            </Button>
            <Button variant="secondary" prefixIcon="exclamation">
              Управление жалобами
            </Button>
            <Button variant="secondary" prefixIcon="chart">
              Статистика активности
            </Button>
          </Grid>
        </Column>
      </Card>

      {/* Импорт/Экспорт */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Импорт/Экспорт данных</Heading>
          <Grid columns={2} gap="m">
            <Button variant="secondary" prefixIcon="download">
              Экспорт пользователей
            </Button>
            <Button variant="secondary" prefixIcon="upload">
              Импорт пользователей
            </Button>
            <Button variant="secondary" prefixIcon="download">
              Экспорт форумов
            </Button>
            <Button variant="secondary" prefixIcon="upload">
              Импорт форумов
            </Button>
          </Grid>
        </Column>
      </Card>

      {/* Очистка системы */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Очистка системы</Heading>
          <Column gap="m">
            <Flex horizontal="between" vertical="center">
              <Text variant="body-default-s">Очистить кеш</Text>
              <Button variant="danger" size="s">
                Очистить
              </Button>
            </Flex>
            <Flex horizontal="between" vertical="center">
              <Text variant="body-default-s">Очистить логи</Text>
              <Button variant="danger" size="s">
                Очистить
              </Button>
            </Flex>
            <Flex horizontal="between" vertical="center">
              <Text variant="body-default-s">Очистить временные файлы</Text>
              <Button variant="danger" size="s">
                Очистить
              </Button>
            </Flex>
          </Column>
        </Column>
      </Card>
    </Column>
  );
}
