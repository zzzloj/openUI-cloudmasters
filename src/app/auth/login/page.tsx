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
  PasswordInput,
  Icon,
  Schema
} from "@once-ui-system/core";
import { baseURL } from "@/resources";
import { useRouter } from "next/navigation";

export default function LoginPage() {
  const [formData, setFormData] = useState({
    emailOrUsername: "",
    password: "",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | undefined>(undefined);
  const router = useRouter();

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
    setError(undefined);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(undefined);

    try {
      const response = await fetch("/api/auth/login", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(formData),
      });

      const data = await response.json();

      if (response.ok) {
        // Успешный вход - все пользователи перенаправляются в профиль
        router.push("/profile");
      } else {
        setError(data.message || "Ошибка входа");
      }
    } catch (err) {
      setError("Ошибка сети");
    } finally {
      setLoading(false);
    }
  };

  return (
    <Flex fillWidth horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Вход - CloudMasters"
        description="Страница входа в систему"
        path="/auth/login"
      />
      
      <Flex fillWidth horizontal="center">
        <Flex
          background="page"
          border="neutral-alpha-weak"
          radius="m-4"
          shadow="l"
          padding="4"
          horizontal="center"
          zIndex={1}
        >
          <Card padding="xl" radius="l" shadow="l" maxWidth="s">
            <Column gap="l" horizontal="center">
              <Flex gap="m" vertical="center">
                <Icon name="login" size="l" />
                <Heading variant="display-strong-s">Вход в систему</Heading>
              </Flex>
              
              <Text variant="body-default-s" onBackground="neutral-weak" align="center">
                Введите ваши учетные данные для входа в систему
              </Text>

              <form onSubmit={handleSubmit} style={{ width: "100%" }}>
                <Column gap="m" fillWidth>
                  <Input
                    id="emailOrUsername"
                    label="Email или Имя пользователя"
                    value={formData.emailOrUsername}
                    onChange={(e) => handleInputChange("emailOrUsername", e.target.value)}
                    required
                    errorMessage={error}
                  />
                  
                  <PasswordInput
                    id="password"
                    label="Пароль"
                    value={formData.password}
                    onChange={(e) => handleInputChange("password", e.target.value)}
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
                    {loading ? "Вход..." : "Войти"}
                  </Button>
                </Column>
              </form>

              <Flex gap="s" vertical="center">
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Нет аккаунта?
                </Text>
                <Button 
                  variant="secondary" 
                  href="/auth/register"
                  prefixIcon="user-plus"
                >
                  Зарегистрироваться
                </Button>
              </Flex>

              <Flex gap="s" vertical="center">
                <Text variant="body-default-s" onBackground="neutral-weak">
                  Забыли пароль?
                </Text>
                <Button 
                  variant="secondary" 
                  href="/auth/reset-password"
                  prefixIcon="key"
                >
                  Восстановить
                </Button>
              </Flex>
            </Column>
          </Card>
        </Flex>
      </Flex>
    </Flex>
  );
} 