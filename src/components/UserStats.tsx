"use client";

import React, { useState, useEffect } from "react";
import { Text } from "@once-ui-system/core";

interface UserStatsProps {
  userId: number;
  className?: string;
}

interface UserStatsData {
  id: number;
  name: string;
  display_name: string;
  joined: string;
  posts_count: number;
}

export default function UserStats({ userId, className }: UserStatsProps) {
  const [stats, setStats] = useState<UserStatsData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await fetch(`/api/user/${userId}/stats`);
        if (response.ok) {
          const data = await response.json();
          setStats(data);
        } else {
          setError(true);
        }
      } catch (error) {
        console.error('Error fetching user stats:', error);
        setError(true);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, [userId]);

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
  };

  if (loading) {
    return (
      <div style={{ 
        fontSize: '12px', 
        color: 'var(--neutral-on-background-weak)',
        lineHeight: '1.4'
      }}>
        <div>Загрузка...</div>
      </div>
    );
  }

  if (error || !stats) {
    return (
      <div style={{ 
        fontSize: '12px', 
        color: 'var(--neutral-on-background-weak)',
        lineHeight: '1.4'
      }}>
        <div>Сообщений: 0</div>
        <div>Регистрация: неизвестно</div>
      </div>
    );
  }

  return (
    <div style={{ 
      fontSize: '12px', 
      color: 'var(--neutral-on-background-weak)',
      lineHeight: '1.4'
    }}>
      <div>Сообщений: {stats.posts_count}</div>
      <div>Регистрация: {formatDate(stats.joined)}</div>
    </div>
  );
} 