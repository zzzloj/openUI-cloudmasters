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
    username: "",
    firstName: "",
    lastName: "",
    email: "",
    password: "",
    confirmPassword: "",
    securityAnswer: "",
    agreeToTerms: false,
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | undefined>(undefined);
  const [success, setSuccess] = useState(false);
  const router = useRouter();

  const handleInputChange = (field: string, value: string | boolean) => {
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
    if (!formData.agreeToTerms) {
      setError("Необходимо согласиться с правилами портала");
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
          username: formData.username,
          firstName: formData.firstName,
          lastName: formData.lastName,
          email: formData.email,
          password: formData.password,
          securityAnswer: formData.securityAnswer,
          agreeToTerms: formData.agreeToTerms,
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
      <Flex fillWidth horizontal="center" paddingY="xl">
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
          </Flex>
        </Flex>
      </Flex>
    );
  }

  return (
    <Flex fillWidth horizontal="center" paddingY="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Регистрация - CloudMasters"
        description="Страница регистрации нового пользователя"
        path="/auth/register"
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
                <Icon name="user-plus" size="l" />
                <Heading variant="display-strong-s">Регистрация</Heading>
              </Flex>
              


              <form onSubmit={handleSubmit} style={{ width: "100%" }}>
                <Column gap="m" fillWidth>
                  <Input
                    id="username"
                    label="Имя пользователя"
                    value={formData.username}
                    onChange={(e) => handleInputChange("username", e.target.value)}
                    required
                  />
                  
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
                  
                  <Input
                    id="securityAnswer"
                    label="Ответ на контрольный вопрос"
                    value={formData.securityAnswer}
                    onChange={(e) => handleInputChange("securityAnswer", e.target.value)}
                    required
                  />
                  
                  <Flex gap="m" vertical="center" paddingY="s">
                    <input
                      type="checkbox"
                      id="agreeToTerms"
                      checked={formData.agreeToTerms}
                      onChange={(e) => handleInputChange("agreeToTerms", e.target.checked)}
                      style={{
                        width: "16px",
                        height: "16px",
                        accentColor: "var(--brand-background-strong)"
                      }}
                    />
                    <Text variant="body-default-s">
                      Я согласен с{" "}
                      <Button 
                        variant="secondary" 
                        href="/terms"
                        prefixIcon="document"
                        style={{ padding: 0, textDecoration: "underline" }}
                      >
                        правилами портала
                      </Button>
                    </Text>
                  </Flex>
                  
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
        </Flex>
      </Flex>
    </Flex>
  );
} 