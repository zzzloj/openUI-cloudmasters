'use client';

import { useState, useEffect } from 'react';
import { useAuth } from '@/contexts/AuthContext';
import { Card, Text, Flex, Button, Input, PasswordInput } from '@once-ui-system/core';
import Link from 'next/link';

export function RegisterForm() {
  const [mounted, setMounted] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [displayName, setDisplayName] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    setMounted(true);
  }, []);

  const { register } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    setSuccess('');

    // Валидация
    if (password !== confirmPassword) {
      setError('Пароли не совпадают');
      setLoading(false);
      return;
    }

    if (password.length < 6) {
      setError('Пароль должен содержать минимум 6 символов');
      setLoading(false);
      return;
    }

    try {
      await register(email, name, password);
      setSuccess('Регистрация успешна! Вы будете перенаправлены на главную страницу.');
      setTimeout(() => {
        window.location.href = '/';
      }, 2000);
    } catch (error) {
      setError(error instanceof Error ? error.message : 'Произошла ошибка при регистрации');
    } finally {
      setLoading(false);
    }
  };

  if (!mounted) {
    return (
      <Card padding="xl" style={{ maxWidth: '500px', width: '100%' }}>
        <Flex direction="column" gap="l">
          <Text variant="body-strong-xl" align="center">
            Загрузка...
          </Text>
        </Flex>
      </Card>
    );
  }

  return (
    <Card padding="xl" style={{ maxWidth: '500px', width: '100%' }}>
      <Flex direction="column" gap="l">
        <Text variant="body-strong-xl" align="center">
          Регистрация
        </Text>
        
        {error && (
          <Text color="error" align="center">
            {error}
          </Text>
        )}

        {success && (
          <Text color="success" align="center">
            {success}
          </Text>
        )}

        <form onSubmit={handleSubmit}>
          <Flex direction="column" gap="m">
            <div>
              <Text variant="body-strong-s" marginBottom="xs">
                Имя пользователя *
              </Text>
              <Input
                id="username"
                type="text"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Введите имя пользователя"
                required
                disabled={loading}
              />
            </div>

            <div>
              <Text variant="body-strong-s" marginBottom="xs">
                Отображаемое имя
              </Text>
              <Input
                id="displayName"
                type="text"
                value={displayName}
                onChange={(e) => setDisplayName(e.target.value)}
                placeholder="Введите отображаемое имя (необязательно)"
                disabled={loading}
              />
            </div>

            <div>
              <Text variant="body-strong-s" marginBottom="xs">
                Email *
              </Text>
              <Input
                id="email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Введите ваш email"
                required
                disabled={loading}
              />
            </div>

            <div>
              <Text variant="body-strong-s" marginBottom="xs">
                Пароль *
              </Text>
              <PasswordInput
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Введите пароль"
                required
                disabled={loading}
              />
            </div>

            <div>
              <Text variant="body-strong-s" marginBottom="xs">
                Подтвердите пароль *
              </Text>
              <PasswordInput
                id="confirmPassword"
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="Повторите пароль"
                required
                disabled={loading}
              />
            </div>

            <Button
              type="submit"
              disabled={loading || !name || !email || !password || !confirmPassword}
              style={{ width: '100%' }}
            >
              {loading ? 'Регистрация...' : 'Зарегистрироваться'}
            </Button>
          </Flex>
        </form>

        <Flex direction="column" gap="s" align="center">
          <Text variant="body-default-s" color="weak">
            Уже есть аккаунт?{' '}
            <Link href="/auth/login" style={{ color: 'var(--brand-background-strong)' }}>
              Войти в систему
            </Link>
          </Text>
        </Flex>
      </Flex>
    </Card>
  );
} 