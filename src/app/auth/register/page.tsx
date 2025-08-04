"use client";

import { RegisterForm } from '@/components/Auth/RegisterForm';
import { Flex } from '@once-ui-system/core';

export default function RegisterPage() {
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
      <RegisterForm />
    </Flex>
  );
} 