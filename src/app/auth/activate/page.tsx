"use client";

import { useState, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import {
  Card,
  Column,
  Text,
  Button,
  Icon,
  Schema,
  Flex,
  Heading,
  Badge,
} from "@once-ui-system/core";

export default function ActivatePage() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [status, setStatus] = useState<"loading" | "success" | "error">("loading");
  const [message, setMessage] = useState("");

  useEffect(() => {
    const activateAccount = async () => {
      const code = searchParams.get("code");
      const email = searchParams.get("email");

      if (!code || !email) {
        setStatus("error");
        setMessage("Неверная ссылка активации");
        return;
      }

      try {
        const response = await fetch(`/api/auth/activate?code=${code}&email=${encodeURIComponent(email)}`);
        const data = await response.json();

        if (response.ok) {
          setStatus("success");
          setMessage(data.message);
        } else {
          setStatus("error");
          setMessage(data.message);
        }
      } catch (error) {
        setStatus("error");
        setMessage("Произошла ошибка при активации аккаунта");
      }
    };

    activateAccount();
  }, [searchParams]);

  const getStatusIcon = () => {
    switch (status) {
      case "loading":
        return "loading";
      case "success":
        return "check-circle";
      case "error":
        return "x-circle";
      default:
        return "info";
    }
  };

  const getStatusColor = () => {
    switch (status) {
      case "loading":
        return "neutral";
      case "success":
        return "success";
      case "error":
        return "danger";
      default:
        return "neutral";
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-cyan-50 to-blue-50 p-4">
      <Schema
        as="webPage"
        baseURL={process.env.NEXT_PUBLIC_BASE_URL || "http://89.111.170.207"}
        title="Активация аккаунта - CloudMasters"
        description="Страница активации аккаунта"
        path="/auth/activate"
      />

      <Card className="w-full max-w-md">
        <Column gap="l" align="center">
          <div className="text-center">
            <Icon
              name={getStatusIcon()}
              size="xl"
              className={`mb-4 ${
                status === "loading" ? "animate-spin" : ""
              }`}
            />
            <Heading size="l" align="center">
              Активация аккаунта
            </Heading>
          </div>

          <div className="text-center">
            <Badge>
              {status === "loading" && "Активация..."}
              {status === "success" && "Успешно!"}
              {status === "error" && "Ошибка"}
            </Badge>
          </div>

          <Text variant="body-default-m" align="center" onBackground="neutral-weak">
            {message}
          </Text>

          {status === "success" && (
            <Flex gap="m" vertical="center">
              <Button
                variant="primary"
                href="/auth/login"
                prefixIcon="login"
              >
                Войти в систему
              </Button>
              <Button
                variant="secondary"
                href="/"
                prefixIcon="home"
              >
                На главную
              </Button>
            </Flex>
          )}

          {status === "error" && (
            <Flex gap="m" vertical="center">
              <Button
                variant="primary"
                href="/auth/register"
                prefixIcon="userPlus"
              >
                Зарегистрироваться
              </Button>
              <Button
                variant="secondary"
                href="/"
                prefixIcon="home"
              >
                На главную
              </Button>
            </Flex>
          )}

          {status === "loading" && (
            <Text variant="body-default-s" align="center" onBackground="neutral-weak">
              Пожалуйста, подождите...
            </Text>
          )}
        </Column>
      </Card>
    </div>
  );
} 