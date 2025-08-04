"use client";

import { LoginForm } from '@/components/Auth/LoginForm';
import { Flex } from '@once-ui-system/core';

export default function LoginPage() {
  return (
    <Flex 
      direction="column" 
      align="center" 
      horizontal="center"
      style={{ 
        minHeight: 'calc(100vh - 200px)', 
        padding: '20px' 
      }}
    >
      <LoginForm />
    </Flex>
  );
} 