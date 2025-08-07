'use client';

import { useState, useEffect } from 'react';
import { Card, Text, Flex, Button, Avatar, Line, Column, Heading, Badge } from '@once-ui-system/core';
import AvatarWithAPI from '@/components/AvatarWithAPI';
import UserStats from '@/components/UserStats';
import Link from 'next/link';
import { useParams } from 'next/navigation';
import styles from './forum-topic.module.css';

interface ForumPost {
  id: number;
  topic_id: number;
  author_id: number;
  author_name: string;
  content: string;
  created_at: number;
  is_first_post: boolean;
  is_approved: boolean;
  topic_title: string;
  forum_id: number;
  forum_name: string;
}

interface ForumTopic {
  id: number;
  title: string;
  forum_id: number;
  author_id: number;
  author_name: string;
  posts_count: number;
  views_count: number;
  is_pinned: boolean;
  is_locked: boolean;
  is_approved: boolean;
  created_at: number;
  last_post_date: number | null;
  last_poster_name: string;
  forum_name: string;
}

export default function TopicPage() {
  const params = useParams();
  const topicId = params.id as string;
  
  const [topic, setTopic] = useState<ForumTopic | null>(null);
  const [posts, setPosts] = useState<ForumPost[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [newPost, setNewPost] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchTopicData();
  }, [topicId, page]);

  const fetchTopicData = async () => {
    try {
      // Получаем информацию о теме
      const topicResponse = await fetch(`/api/forum/topics/${topicId}`);
      const topicData = await topicResponse.json();
      
      // Получаем посты в теме
      const postsResponse = await fetch(`/api/forum/posts?topic_id=${topicId}&page=${page}&limit=20`);
      const postsData = await postsResponse.json();

      setTopic(topicData.topic);
      setPosts(postsData.posts || []);
      setTotalPages(postsData.pagination?.pages || 1);
    } catch (error) {
      console.error('Error fetching topic data:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmitPost = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!newPost.trim() || !topic) return;

    setSubmitting(true);
    try {
      const response = await fetch('/api/forum/posts', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          topic_id: topic.id,
          author_id: 1, // TODO: Получать из авторизации
          author_name: 'User', // TODO: Получать из авторизации
          content: newPost.trim()
        }),
      });

      if (response.ok) {
        setNewPost('');
        fetchTopicData(); // Обновляем данные
      } else {
        console.error('Failed to create post');
      }
    } catch (error) {
      console.error('Error creating post:', error);
    } finally {
      setSubmitting(false);
    }
  };

  const formatDate = (timestamp: number) => {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    return date.toLocaleDateString('ru-RU', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="forum-container">
        <div className="forum-header">
          <h1>Загрузка...</h1>
        </div>
      </div>
    );
  }

  if (!topic) {
    return (
      <div className="forum-container">
        <div className="forum-header">
          <h1>Тема не найдена</h1>
        </div>
      </div>
    );
  }

  return (
    <div className="forum-container">
      {/* Хлебные крошки */}
      <div className="forum-breadcrumb">
        <Link href="/forum">Форум</Link>
        <span>→</span>
        <Link href={`/forum/category/${topic.forum_id}`}>{topic.forum_name}</Link>
        <span>→</span>
        <span>{topic.title}</span>
      </div>

      {/* Заголовок темы */}
      <div className="forum-header">
        <h1>{topic.title}</h1>
        <div style={{ marginTop: '10px', fontSize: '14px', opacity: 0.8 }}>
          Автор: <Link 
            href={`/profile/${topic.author_id}`}
            style={{
              color: 'var(--brand-background-strong)',
              textDecoration: 'none',
              fontWeight: 'bold'
            }}
            onMouseEnter={(e) => {
              e.currentTarget.style.textDecoration = 'underline';
            }}
            onMouseLeave={(e) => {
              e.currentTarget.style.textDecoration = 'none';
            }}
          >
            {topic.author_name}
          </Link> • {formatDate(topic.created_at)} • 
          Ответов: {topic.posts_count} • Просмотров: {topic.views_count}
        </div>
      </div>

      {/* Посты в теме */}
      <div className="forum-topics-header">
        Сообщения в теме
      </div>
      <div className="forum-topics-list">
        {posts.length === 0 ? (
          <div style={{ padding: '20px', textAlign: 'center', color: 'var(--neutral-on-background-weak)' }}>
            В этой теме пока нет сообщений
          </div>
        ) : (
          posts.map((post, index) => (
            <Card key={post.id} style={{ marginBottom: '20px', overflow: 'hidden', width: '100%' }}>
              <div className={styles.postContainer}>
                {/* Левая панель с информацией о пользователе */}
                <div className={styles.userPanel}>
                  {/* Аватар и основная информация */}
                  <div className={styles.userInfo}>
                    {/* Аватар */}
                    <div className={styles.avatarContainer}>
                      <AvatarWithAPI userId={post.author_id} size="m" />
                    </div>
                    
                    {/* Имя пользователя */}
                    <div className={styles.userNameContainer}>
                      <Link 
                        href={`/profile/${post.author_id}`}
                        className={styles.userName}
                      >
                        {post.author_name}
                      </Link>
                    </div>
                  </div>

                  {/* Статус и статистика */}
                  <div className={styles.userStats}>
                    {/* Статус пользователя */}
                    <div className={styles.statusContainer}>
                      {post.is_first_post && (
                        <Badge background="brand-medium" className={styles.badgeWithMargin}>
                          Автор темы
                        </Badge>
                      )}
                      <Badge background="neutral-medium" className={styles.badge}>
                        Пользователь
                      </Badge>
                    </div>

                    {/* Дополнительная информация */}
                    <div className={styles.statsContainer}>
                      <UserStats userId={post.author_id} />
                    </div>
                  </div>
                </div>

                {/* Правая панель с контентом сообщения */}
                <div className={styles.contentPanel}>
                  {/* Заголовок сообщения */}
                  <div style={{ 
                    display: 'flex', 
                    justifyContent: 'space-between', 
                    alignItems: 'center',
                    marginBottom: '15px',
                    paddingBottom: '10px',
                    borderBottom: '1px solid var(--neutral-alpha-medium)'
                  }}>
                    <div style={{ fontSize: '14px', color: 'var(--neutral-on-background-weak)' }}>
                      {formatDate(post.created_at)}
                    </div>
                    <div style={{ fontSize: '14px', color: 'var(--neutral-on-background-weak)' }}>
                      #{index + 1}
                    </div>
                  </div>

                  {/* Контент сообщения */}
                  <div style={{ 
                    lineHeight: '1.6',
                    color: 'var(--neutral-on-background-strong)',
                    fontSize: '14px'
                  }}>
                    <div dangerouslySetInnerHTML={{ __html: post.content }} />
                  </div>

                  {/* Действия с сообщением */}
                  <div style={{ 
                    marginTop: '15px',
                    paddingTop: '10px',
                    borderTop: '1px solid var(--neutral-alpha-medium)',
                    display: 'flex',
                    gap: '10px'
                  }}>
                    <Button size="s" variant="secondary">
                      Цитировать
                    </Button>
                    <Button size="s" variant="secondary">
                      Ответить
                    </Button>
                    <Button size="s" variant="secondary">
                      Пожаловаться
                    </Button>
                  </div>
                </div>
              </div>
            </Card>
          ))
        )}
      </div>

      {/* Пагинация */}
      {totalPages > 1 && (
        <div className="forum-pagination">
          {Array.from({ length: totalPages }, (_, i) => i + 1).map((pageNum) => (
            <Link
              key={pageNum}
              href="#"
              className={pageNum === page ? 'active' : ''}
              onClick={(e) => {
                e.preventDefault();
                setPage(pageNum);
              }}
            >
              {pageNum}
            </Link>
          ))}
        </div>
      )}

      {/* Форма для нового поста */}
      {!topic.is_locked && (
        <div className="forum-actions">
          <div className="forum-actions-left">
            <h3 style={{ margin: '0 0 15px 0', color: 'var(--neutral-on-background-strong)' }}>
              Добавить ответ
            </h3>
            <form onSubmit={handleSubmitPost} style={{ width: '100%' }}>
              <textarea
                value={newPost}
                onChange={(e) => setNewPost(e.target.value)}
                placeholder="Введите ваш ответ..."
                style={{
                  width: '100%',
                  minHeight: '120px',
                  padding: '12px',
                  border: '1px solid var(--neutral-alpha-medium)',
                  borderRadius: '8px',
                  backgroundColor: 'var(--surface-background)',
                  color: 'var(--neutral-on-background-strong)',
                  fontSize: '14px',
                  resize: 'vertical',
                  fontFamily: 'inherit'
                }}
                required
              />
              <div style={{ marginTop: '10px' }}>
                <button
                  type="submit"
                  disabled={submitting || !newPost.trim()}
                  className="forum-button"
                  style={{
                    opacity: submitting || !newPost.trim() ? 0.6 : 1,
                    cursor: submitting || !newPost.trim() ? 'not-allowed' : 'pointer'
                  }}
                >
                  {submitting ? 'Отправка...' : 'Отправить ответ'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Действия */}
      <div className="forum-actions">
        <div className="forum-actions-left">
          <Link href={`/forum/category/${topic.forum_id}`} className="forum-button secondary">
            ← Назад к категории
          </Link>
        </div>
        <div className="forum-actions-right">
          <Link href="/forum" className="forum-button secondary">
            На главную форума
          </Link>
        </div>
      </div>
    </div>
  );
} 