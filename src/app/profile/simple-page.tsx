"use client";

import React, { useState, useEffect } from "react";
import { useRouter } from "next/navigation";

interface User {
  id: string;
  username: string;
  email: string;
  displayName: string;
  role: string;
  isAdmin: boolean;
  memberGroupId: number;
  joined: number;
  lastVisit: number;
  posts: number;
}

export default function SimpleProfilePage() {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const router = useRouter();

  useEffect(() => {
    checkAuth();
  }, []);

  const checkAuth = async () => {
    try {
      const response = await fetch("/api/auth/me");
      if (response.ok) {
        const data = await response.json();
        setUser(data.user);
      } else {
        router.push("/auth/login");
        return;
      }
    } catch (error) {
      console.error("Ошибка проверки аутентификации:", error);
      router.push("/auth/login");
      return;
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = async () => {
    try {
      await fetch("/api/auth/logout", { method: "POST" });
      router.push("/auth/login");
    } catch (error) {
      console.error("Ошибка выхода:", error);
    }
  };

  const formatDate = (timestamp: number) => {
    return new Date(timestamp * 1000).toLocaleDateString('ru-RU');
  };

  if (loading) {
    return (
      <div style={{ padding: '2rem', textAlign: 'center' }}>
        <h2>Загрузка профиля...</h2>
      </div>
    );
  }

  if (!user) {
    return null;
  }

  return (
    <div style={{ maxWidth: '800px', margin: '0 auto', padding: '2rem' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '2rem' }}>
        <h1>Личный кабинет</h1>
        <button onClick={handleLogout} style={{ padding: '0.5rem 1rem', background: '#f0f0f0', border: '1px solid #ccc', borderRadius: '4px' }}>
          Выйти
        </button>
      </div>

      <div style={{ background: '#f9f9f9', padding: '1.5rem', borderRadius: '8px', marginBottom: '2rem' }}>
        <h2>Информация профиля</h2>
        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem', marginTop: '1rem' }}>
          <div>
            <strong>Имя пользователя:</strong> {user.username}
          </div>
          <div>
            <strong>Email:</strong> {user.email}
          </div>
          <div>
            <strong>Отображаемое имя:</strong> {user.displayName}
          </div>
          <div>
            <strong>Роль:</strong> {user.isAdmin ? 'Администратор' : 'Пользователь'}
          </div>
          <div>
            <strong>Дата регистрации:</strong> {formatDate(user.joined)}
          </div>
          <div>
            <strong>Сообщений:</strong> {user.posts}
          </div>
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1rem', marginBottom: '2rem' }}>
        <div style={{ background: '#e8f5e8', padding: '1rem', borderRadius: '8px', textAlign: 'center' }}>
          <h3>Сообщения</h3>
          <div style={{ fontSize: '2rem', fontWeight: 'bold' }}>{user.posts}</div>
        </div>
        <div style={{ background: '#e8f0ff', padding: '1rem', borderRadius: '8px', textAlign: 'center' }}>
          <h3>Последний визит</h3>
          <div>{formatDate(user.lastVisit)}</div>
        </div>
        <div style={{ background: '#fff3e8', padding: '1rem', borderRadius: '8px', textAlign: 'center' }}>
          <h3>Статус</h3>
          <div style={{ color: 'green', fontWeight: 'bold' }}>Активен</div>
        </div>
      </div>

      {user.isAdmin && (
        <div style={{ background: '#e8f5e8', padding: '1.5rem', borderRadius: '8px', marginBottom: '2rem' }}>
          <h3>Панель администратора</h3>
          <p>У вас есть доступ к панели администратора для управления сайтом.</p>
          <button 
            onClick={() => router.push('/admin')}
            style={{ padding: '0.5rem 1rem', background: '#4CAF50', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
          >
            Перейти в админ-панель
          </button>
        </div>
      )}

      <div style={{ background: '#f9f9f9', padding: '1.5rem', borderRadius: '8px' }}>
        <h3>Быстрые действия</h3>
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1rem', marginTop: '1rem' }}>
          <button style={{ padding: '0.5rem', background: '#f0f0f0', border: '1px solid #ccc', borderRadius: '4px' }}>
            Настройки
          </button>
          <button style={{ padding: '0.5rem', background: '#f0f0f0', border: '1px solid #ccc', borderRadius: '4px' }}>
            Сообщения
          </button>
          <button style={{ padding: '0.5rem', background: '#f0f0f0', border: '1px solid #ccc', borderRadius: '4px' }}>
            Мои публикации
          </button>
          <button style={{ padding: '0.5rem', background: '#f0f0f0', border: '1px solid #ccc', borderRadius: '4px' }}>
            Безопасность
          </button>
        </div>
      </div>
    </div>
  );
} 