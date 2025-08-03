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
  Schema,
  Badge
} from "@once-ui-system/core";
import { baseURL } from "@/resources";
import { useRouter } from "next/navigation";

export default function RegisterPage() {
  const [formData, setFormData] = useState({
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: "",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | undefined>(undefined);
  const [success, setSuccess] = useState(false);
  const router = useRouter();

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({
      ...prev,
      [field]: value
    }));
    setError(undefined);
  };

  const validateForm = () => {
    if (formData.password !== formData.confirmPassword) {
      setError("Пароли не совпадают");
      return false;
    }
    if (formData.password.length < 8) {
      setError("Пароль должен содержать минимум 8 символов");
      return false;
    }
    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    setLoading(true);
    setError(undefined);

    try {
      const response = await fetch("/api/auth/register", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          firstName: formData.firstName,
          lastName: formData.lastName,
          email: formData.email,
          password: formData.password,
        }),
      });

      const data = await response.json();

      if (response.ok) {
        setSuccess(true);
        setTimeout(() => {
          router.push("/auth/login");
        }, 2000);
      } else {
        setError(data.message || "Ошибка регистрации");
      }
    } catch (err) {
      setError("Ошибка сети");
    } finally {
      setLoading(false);
    }
  };

  if (success) {
    return (
      <Column fillWidth horizontal="center" paddingY="xl">
        <Card padding="xl" radius="l" shadow="l" maxWidth="s">
          <Column gap="l" horizontal="center">
            <Flex gap="m" vertical="center">
              <Icon name="check-circle" size="l" />
              <Heading variant="display-strong-s">Регистрация успешна!</Heading>
            </Flex>
            
            <Text variant="body-default-s" onBackground="neutral-weak" align="center">
              Ваш аккаунт создан. Сейчас вы будете перенаправлены на страницу входа.
            </Text>

            <Badge background="success-medium">
              Аккаунт создан
            </Badge>
          </Column>
        </Card>
      </Column>
    );
  }

  return (
    <Column fillWidth horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Регистрация - CloudMasters"
        description="Страница регистрации нового пользователя"
        path="/auth/register"
      />
      
      <Card padding="xl" radius="l" shadow="l" maxWidth="s">
        <Column gap="l" horizontal="center">
          <Flex gap="m" vertical="center">
            <Icon name="user-plus" size="l" />
            <Heading variant="display-strong-s">Регистрация</Heading>
          </Flex>
          
          <Text variant="body-default-s" onBackground="neutral-weak" align="center">
            Создайте новый аккаунт для доступа к админ-панели
          </Text>

          <form onSubmit={handleSubmit} style={{ width: "100%" }}>
            <Column gap="m" fillWidth>
              <Flex gap="m" fillWidth>
                <Input
                  id="firstName"
                  label="Имя"
                  value={formData.firstName}
                  onChange={(e) => handleInputChange("firstName", e.target.value)}
                  required
                />
                
                <Input
                  id="lastName"
                  label="Фамилия"
                  value={formData.lastName}
                  onChange={(e) => handleInputChange("lastName", e.target.value)}
                  required
                />
              </Flex>
              
              <Input
                id="email"
                label="Email"
                type="email"
                value={formData.email}
                onChange={(e) => handleInputChange("email", e.target.value)}
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
              
              <PasswordInput
                id="confirmPassword"
                label="Подтвердите пароль"
                value={formData.confirmPassword}
                onChange={(e) => handleInputChange("confirmPassword", e.target.value)}
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
                {loading ? "Регистрация..." : "Зарегистрироваться"}
              </Button>
            </Column>
          </form>

          <Flex gap="s" vertical="center">
            <Text variant="body-default-s" onBackground="neutral-weak">
              Уже есть аккаунт?
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
    </Column>
  );
} 