"use client";

import React, { useState, useEffect } from "react";
import { Avatar } from "@once-ui-system/core";

interface AvatarWithAPIProps {
  userId: number;
  size?: "s" | "m" | "l" | "xl";
  className?: string;
}

export default function AvatarWithAPI({ userId, size = "m", className }: AvatarWithAPIProps) {
  const [avatarUrl, setAvatarUrl] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(false);

  useEffect(() => {
    const fetchAvatar = async () => {
      try {
        const response = await fetch(`/api/avatar/${userId}`);
        if (response.ok) {
          const data = await response.json();
          setAvatarUrl(data.avatar_url);
        } else {
          setError(true);
        }
      } catch (error) {
        console.error('Error fetching avatar:', error);
        setError(true);
      } finally {
        setLoading(false);
      }
    };

    fetchAvatar();
  }, [userId]);

  if (loading) {
    return <Avatar size={size} className={className} />;
  }

  if (error || !avatarUrl) {
    return <Avatar size={size} className={className} />;
  }

  return <Avatar size={size} src={avatarUrl} className={className} />;
} 