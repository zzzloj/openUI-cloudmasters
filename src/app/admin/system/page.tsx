'use client';

import { useEffect, useState } from 'react';
import { Card, Button, Text, Column, Flex, Icon, Badge, Heading, Input, Select, Grid } from '@once-ui-system/core';
import { 
  FaCog, 
  FaDatabase, 
  FaServer, 
  FaShieldAlt,
  FaEye,
  FaDownload
} from 'react-icons/fa';

interface SystemInfo {
  version: string;
  database: string;
  server: string;
  uptime: string;
  memory: string;
  disk: string;
}

export default function SystemAdminPage() {
  const [systemInfo, setSystemInfo] = useState<SystemInfo>({
    version: '1.0.0',
    database: 'MySQL 8.0',
    server: 'Node.js 18.x',
    uptime: '5 дней',
    memory: '512 MB',
    disk: '2.1 GB'
  });

  return (
    <Column maxWidth="xl" gap="xl">
      {/* Заголовок */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Column gap="s">
          <Heading variant="display-strong-l">Системные настройки</Heading>
          <Text variant="body-default-s" onBackground="neutral-weak">
            Управление системой и мониторинг
          </Text>
        </Column>
      </Flex>

      {/* Информация о системе */}
      <Grid columns={2} gap="l">
        <Card padding="l" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Системная информация</Heading>
            <Column gap="m">
              <Flex horizontal="between" vertical="center">
                <Text variant="body-default-s">Версия системы</Text>
                <Badge background="info-medium">{systemInfo.version}</Badge>
              </Flex>
              <Flex horizontal="between" vertical="center">
                <Text variant="body-default-s">База данных</Text>
                <Badge background="success-medium">{systemInfo.database}</Badge>
              </Flex>
              <Flex horizontal="between" vertical="center">
                <Text variant="body-default-s">Сервер</Text>
                <Badge background="warning-medium">{systemInfo.server}</Badge>
              </Flex>
              <Flex horizontal="between" vertical="center">
                <Text variant="body-default-s">Время работы</Text>
                <Badge background="neutral-medium">{systemInfo.uptime}</Badge>
              </Flex>
            </Column>
          </Column>
        </Card>

        <Card padding="l" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Ресурсы</Heading>
            <Column gap="m">
              <Flex horizontal="between" vertical="center">
                <Text variant="body-default-s">Память</Text>
                <Badge background="success-medium">{systemInfo.memory}</Badge>
              </Flex>
              <Flex horizontal="between" vertical="center">
                <Text variant="body-default-s">Диск</Text>
                <Badge background="warning-medium">{systemInfo.disk}</Badge>
              </Flex>
            </Column>
          </Column>
        </Card>
      </Grid>

      {/* Настройки безопасности */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Безопасность</Heading>
          <Grid columns={2} gap="m">
            <Button variant="secondary" prefixIcon="shield">
              Настройки безопасности
            </Button>
            <Button variant="secondary" prefixIcon="eye">
              Просмотр логов
            </Button>
            <Button variant="secondary" prefixIcon="download">
              Резервное копирование
            </Button>
            <Button variant="secondary" prefixIcon="cog">
              Обновление системы
            </Button>
          </Grid>
        </Column>
      </Card>

      {/* Логи системы */}
      <Card padding="l" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Последние логи</Heading>
          <Column gap="m">
            <Card padding="s" radius="s" background="neutral-weak">
              <Text variant="body-default-xs">[2024-01-15 14:30:25] INFO: Система запущена</Text>
            </Card>
            <Card padding="s" radius="s" background="neutral-weak">
              <Text variant="body-default-xs">[2024-01-15 14:25:10] WARNING: Высокое потребление памяти</Text>
            </Card>
            <Card padding="s" radius="s" background="neutral-weak">
              <Text variant="body-default-xs">[2024-01-15 14:20:15] INFO: Резервное копирование завершено</Text>
            </Card>
          </Column>
        </Column>
      </Card>
    </Column>
  );
}
