'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { Card, Text, Flex, Button, Input, PasswordInput } from '@once-ui-system/core';
import Link from 'next/link';

export function LoginForm() {
  const [mounted, setMounted] = useState(false);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    setMounted(true);
  }, []);

  const { login } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const result = await login({ email, password });
      
      if (result.success) {
        // Перенаправление будет обработано в AuthContext
        window.location.href = '/';
      } else {
        setError(result.error || 'Ошибка авторизации');
      }
    } catch (error) {
      setError('Произошла ошибка при авторизации');
    } finally {
      setLoading(false);
    }
  };

  if (!mounted) {
    return (
      <Card padding="xl" style={{ maxWidth: '400px', width: '100%' }}>
        <Flex direction="column" gap="l">
          <Text variant="h2" align="center">
            Загрузка...
          </Text>
        </Flex>
      </Card>
    );
  }

  return (
    <Card padding="xl" style={{ maxWidth: '400px', width: '100%' }}>
      <Flex direction="column" gap="l">
        <Text variant="h2" align="center">
          Вход в аккаунт
        </Text>
        
        {error && (
          <Text color="error" align="center">
            {error}
          </Text>
        )}

        <form onSubmit={handleSubmit}>
          <Flex direction="column" gap="m">
            <div>
              <Text variant="label" marginBottom="xs">
                Email
              </Text>
              <Input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Введите ваш email"
                required
                disabled={loading}
              />
            </div>

            <div>
              <Text variant="label" marginBottom="xs">
                Пароль
              </Text>
              <PasswordInput
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Введите ваш пароль"
                required
                disabled={loading}
              />
            </div>

            <Button
              type="submit"
              disabled={loading || !email || !password}
              style={{ width: '100%' }}
            >
              {loading ? 'Вход...' : 'Войти'}
            </Button>
          </Flex>
        </form>

        <Flex direction="column" gap="s" align="center">
          <Text variant="body" color="weak">
            Нет аккаунта?{' '}
            <Link href="/auth/register" style={{ color: 'var(--brand-background-strong)' }}>
              Зарегистрироваться
            </Link>
          </Text>
          
          <Link href="/auth/reset-password" style={{ color: 'var(--brand-background-strong)' }}>
            Забыли пароль?
          </Link>
        </Flex>
      </Flex>
    </Card>
  );
} 