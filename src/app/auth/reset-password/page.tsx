"use client";

import React, { useState } from "react";
import { useSearchParams } from "next/navigation";
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
  Badge,
  PasswordInput
} from "@once-ui-system/core";
import { baseURL } from "@/resources";

export default function ResetPasswordPage() {
  const searchParams = useSearchParams();
  const [emailOrUsername, setEmailOrUsername] = useState("");
  const [code, setCode] = useState(searchParams.get("code") || "");
  const [newPassword, setNewPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | undefined>(undefined);
  const [success, setSuccess] = useState(false);
  const [step, setStep] = useState<"request" | "confirm">(
    searchParams.get("code") ? "confirm" : "request"
  );

  const handleRequestCode = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(undefined);

    try {
      const response = await fetch("/api/auth/reset-password", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ emailOrUsername }),
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

  const handleConfirmReset = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(undefined);

    if (newPassword !== confirmPassword) {
      setError("Пароли не совпадают");
      setLoading(false);
      return;
    }

    if (newPassword.length < 8) {
      setError("Пароль должен содержать минимум 8 символов");
      setLoading(false);
      return;
    }

    try {
      const response = await fetch("/api/auth/reset-password/confirm", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ 
          email: emailOrUsername, 
          code, 
          newPassword 
        }),
      });

      const data = await response.json();

      if (response.ok) {
        setSuccess(true);
      } else {
        setError(data.message || "Ошибка обновления пароля");
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
                  <Heading variant="display-strong-s">
                    {step === "request" ? "Проверьте email" : "Пароль обновлен"}
                  </Heading>
                </Flex>
                
                <Text variant="body-default-s" onBackground="neutral-weak" align="center">
                  {step === "request" 
                    ? "Мы отправили код восстановления пароля на ваш email."
                    : "Ваш пароль успешно обновлен! Теперь вы можете войти в систему."
                  }
                </Text>

                <Badge background="success-medium">
                  {step === "request" ? "Email отправлен" : "Успешно"}
                </Badge>

                <Button 
                  variant="primary" 
                  href="/auth/login"
                  prefixIcon="login"
                >
                  Войти в систему
                </Button>
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
        title="Восстановление пароля - CloudMasters"
        description="Страница восстановления пароля"
        path="/auth/reset-password"
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
                <Icon name="key" size="l" />
                <Heading variant="display-strong-s">Восстановление пароля</Heading>
              </Flex>
              
                              <Text variant="body-default-s" onBackground="neutral-weak" align="center">
                {step === "request" 
                  ? "Введите ваш email или имя пользователя для получения кода восстановления пароля"
                  : "Введите код восстановления и новый пароль"
                }
              </Text>

              {step === "request" ? (
                <form onSubmit={handleRequestCode} style={{ width: "100%" }}>
                  <Column gap="m" fillWidth>
                    <Input
                      id="emailOrUsername"
                      label="Email или Имя пользователя"
                      value={emailOrUsername}
                      onChange={(e) => {
                        setEmailOrUsername(e.target.value);
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
                      {loading ? "Отправка..." : "Отправить код"}
                    </Button>
                  </Column>
                </form>
              ) : (
                <form onSubmit={handleConfirmReset} style={{ width: "100%" }}>
                  <Column gap="m" fillWidth>
                    <Input
                      id="emailOrUsername"
                      label="Email"
                      value={emailOrUsername}
                      onChange={(e) => {
                        setEmailOrUsername(e.target.value);
                        setError(undefined);
                      }}
                      required
                      disabled
                    />
                    
                    <Input
                      id="code"
                      label="Код восстановления"
                      value={code}
                      onChange={(e) => {
                        setCode(e.target.value);
                        setError(undefined);
                      }}
                      required
                      errorMessage={error}
                    />
                    
                    <PasswordInput
                      id="newPassword"
                      label="Новый пароль"
                      value={newPassword}
                      onChange={(e) => {
                        setNewPassword(e.target.value);
                        setError(undefined);
                      }}
                      required
                    />
                    
                    <PasswordInput
                      id="confirmPassword"
                      label="Подтвердите пароль"
                      value={confirmPassword}
                      onChange={(e) => {
                        setConfirmPassword(e.target.value);
                        setError(undefined);
                      }}
                      required
                    />
                    
                    <Button 
                      type="submit" 
                      variant="primary" 
                      fillWidth
                      loading={loading}
                      disabled={loading}
                    >
                      {loading ? "Обновление..." : "Обновить пароль"}
                    </Button>
                  </Column>
                </form>
              )}

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
    </Flex>
  );
} 