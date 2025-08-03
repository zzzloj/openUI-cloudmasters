"use client";

import React, { useState } from "react";
import {
  Column,
  Flex,
  Heading,
  Text,
  Button,
  Card,
  Input,
  Icon,
  Schema,
  Badge
} from "@once-ui-system/core";
import { baseURL } from "@/resources";

export default function ResetPasswordPage() {
  const [email, setEmail] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | undefined>(undefined);
  const [success, setSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(undefined);

    try {
      const response = await fetch("/api/auth/reset-password", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ email }),
      });

      const data = await response.json();

      if (response.ok) {
        setSuccess(true);
      } else {
        setError(data.message || "Ошибка отправки запроса");
      }
    } catch (err) {
      setError("Ошибка сети");
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <Flex fillWidth horizontal="center" paddingY="xl">
        <Flex fillWidth horizontal="center">
          <Card padding="xl" radius="l" shadow="l" maxWidth="s">
          <Column gap="l" horizontal="center">
            <Flex gap="m" vertical="center">
              <Icon name="mail" size="l" />
              <Heading variant="display-strong-s">Проверьте email</Heading>
            </Flex>
            
            <Text variant="body-default-s" onBackground="neutral-weak" align="center">
              Мы отправили инструкции по восстановлению пароля на ваш email.
            </Text>

            <Badge background="info-medium">
              Email отправлен
            </Badge>

            <Button 
              variant="secondary" 
              href="/auth/login"
              prefixIcon="arrow-left"
            >
              Вернуться к входу
            </Button>
                  </Column>
      </Card>
      </Flex>
    </Flex>
    );
  }

  return (
    <Flex fillWidth horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Восстановление пароля - CloudMasters"
        description="Страница восстановления пароля"
        path="/auth/reset-password"
      />
      
      <Flex fillWidth horizontal="center">
        <Card padding="xl" radius="l" shadow="l" maxWidth="s">
        <Column gap="l" horizontal="center">
          <Flex gap="m" vertical="center">
            <Icon name="key" size="l" />
            <Heading variant="display-strong-s">Восстановление пароля</Heading>
          </Flex>
          
          <Text variant="body-default-s" onBackground="neutral-weak" align="center">
            Введите ваш email для получения инструкций по восстановлению пароля
          </Text>

          <form onSubmit={handleSubmit} style={{ width: "100%" }}>
            <Column gap="m" fillWidth>
              <Input
                id="email"
                label="Email"
                type="email"
                value={email}
                onChange={(e) => {
                  setEmail(e.target.value);
                  setError(undefined);
                }}
                required
                errorMessage={error}
              />
              
              <Button 
                type="submit" 
                variant="primary" 
                fillWidth
                loading={loading}
                disabled={loading}
              >
                {loading ? "Отправка..." : "Отправить инструкции"}
              </Button>
            </Column>
          </form>

          <Flex gap="s" vertical="center">
            <Text variant="body-default-s" onBackground="neutral-weak">
              Вспомнили пароль?
            </Text>
            <Button 
              variant="secondary" 
              href="/auth/login"
              prefixIcon="login"
            >
              Войти
            </Button>
          </Flex>
        </Column>
      </Card>
      </Flex>
    </Flex>
  );
} 