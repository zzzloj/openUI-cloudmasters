"use client";

import React, { useState } from "react";
import {
  Column,
  Flex,
  Heading,
  Text,
  Button,
  Card,
  Icon,
  Grid,
  Badge,
  Input,
  Select,
  Schema
} from "@once-ui-system/core";
import { baseURL } from "@/resources";

interface SEOData {
  title: string;
  description: string;
  keywords: string;
  ogTitle: string;
  ogDescription: string;
  ogImage: string;
  twitterCard: string;
  canonicalUrl: string;
  robots: string;
  structuredData: string;
}

const defaultSEO: SEOData = {
  title: "CloudMasters Portfolio",
  description: "Портфолио разработчика с использованием Once UI",
  keywords: "portfolio, developer, once ui, nextjs, typescript",
  ogTitle: "CloudMasters Portfolio",
  ogDescription: "Портфолио разработчика с использованием Once UI",
  ogImage: "/images/og/home.jpg",
  twitterCard: "summary_large_image",
  canonicalUrl: "https://89.111.170.207",
  robots: "index, follow",
  structuredData: ""
};

export default function SEOManagement() {
  const [seoData, setSeoData] = useState<SEOData>(defaultSEO);
  const [isSaving, setIsSaving] = useState(false);
  const [seoScore, setSeoScore] = useState(85);

  const handleSave = async () => {
    setIsSaving(true);
    // Имитация сохранения
    await new Promise(resolve => setTimeout(resolve, 1000));
    setIsSaving(false);
    alert("SEO настройки сохранены!");
  };

  const calculateSEOScore = (data: SEOData) => {
    let score = 0;
    if (data.title.length > 0) score += 10;
    if (data.description.length > 50) score += 15;
    if (data.keywords.length > 0) score += 10;
    if (data.ogTitle.length > 0) score += 10;
    if (data.ogDescription.length > 0) score += 10;
    if (data.ogImage.length > 0) score += 10;
    if (data.canonicalUrl.length > 0) score += 10;
    if (data.structuredData.length > 0) score += 15;
    return Math.min(score, 100);
  };

  const handleInputChange = (field: keyof SEOData, value: string) => {
    const newData = { ...seoData, [field]: value };
    setSeoData(newData);
    setSeoScore(calculateSEOScore(newData));
  };

  return (
    <Column maxWidth="xl" gap="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="SEO Management - Admin Panel"
        description="Управление SEO настройками сайта"
        path="/admin/seo"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Heading variant="display-strong-l">SEO управление</Heading>
        <Button 
          variant="primary" 
          prefixIcon="save"
          onClick={handleSave}
          loading={isSaving}
        >
          Сохранить
        </Button>
      </Flex>

      {/* SEO Score */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Flex horizontal="between" vertical="center">
            <Heading variant="display-strong-s">SEO Рейтинг</Heading>
            <Badge 
              background={seoScore >= 80 ? "success-medium" : seoScore >= 60 ? "warning-medium" : "danger-medium"}
            >
              {seoScore}/100
            </Badge>
          </Flex>
          <div style={{ width: "100%", height: "8px", background: "var(--neutral-weak)", borderRadius: "4px" }}>
            <div 
              style={{ 
                width: `${seoScore}%`, 
                height: "100%", 
                background: seoScore >= 80 ? "var(--success-medium)" : seoScore >= 60 ? "var(--warning-medium)" : "var(--danger-medium)",
                borderRadius: "4px",
                transition: "width 0.3s ease"
              }} 
            />
          </div>
          <Text variant="body-default-s" onBackground="neutral-weak">
            {seoScore >= 80 ? "Отличный SEO рейтинг!" : 
             seoScore >= 60 ? "Хороший SEO рейтинг, есть возможности для улучшения" : 
             "SEO рейтинг требует улучшения"}
          </Text>
        </Column>
      </Card>

      {/* Basic SEO */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Основные SEO настройки</Heading>
          
          <Grid columns={2} gap="l">
            <Input
              id="seo-title"
              label="Заголовок страницы"
              value={seoData.title}
              onChange={(e) => handleInputChange("title", e.target.value)}
              placeholder="Введите заголовок страницы"
            />
            
            <Input
              id="seo-canonical"
              label="Canonical URL"
              value={seoData.canonicalUrl}
              onChange={(e) => handleInputChange("canonicalUrl", e.target.value)}
              placeholder="https://example.com"
            />
          </Grid>
          
          <Input
            id="seo-description"
            label="Meta Description"
            value={seoData.description}
            onChange={(e) => handleInputChange("description", e.target.value)}
            placeholder="Краткое описание страницы (150-160 символов)"
          />
          
          <Input
            id="seo-keywords"
            label="Keywords"
            value={seoData.keywords}
            onChange={(e) => handleInputChange("keywords", e.target.value)}
            placeholder="ключевое слово 1, ключевое слово 2"
          />
          
          <Select
            id="seo-robots"
            label="Robots"
            value={seoData.robots}
            onChange={(e) => handleInputChange("robots", e.target.value)}
            options={[
              { value: "index, follow", label: "Индексировать и следовать ссылкам" },
              { value: "noindex, follow", label: "Не индексировать, следовать ссылкам" },
              { value: "index, nofollow", label: "Индексировать, не следовать ссылкам" },
              { value: "noindex, nofollow", label: "Не индексировать, не следовать ссылкам" }
            ]}
          />
        </Column>
      </Card>

      {/* Open Graph */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Open Graph (Социальные сети)</Heading>
          
          <Grid columns={2} gap="l">
            <Input
              id="og-title"
              label="OG Title"
              value={seoData.ogTitle}
              onChange={(e) => handleInputChange("ogTitle", e.target.value)}
              placeholder="Заголовок для соцсетей"
            />
            
            <Input
              id="og-image"
              label="OG Image"
              value={seoData.ogImage}
              onChange={(e) => handleInputChange("ogImage", e.target.value)}
              placeholder="/images/og/image.jpg"
            />
          </Grid>
          
          <Input
            id="og-description"
            label="OG Description"
            value={seoData.ogDescription}
            onChange={(e) => handleInputChange("ogDescription", e.target.value)}
            placeholder="Описание для соцсетей"
          />
          
          <Select
            id="twitter-card"
            label="Twitter Card Type"
            value={seoData.twitterCard}
            onChange={(e) => handleInputChange("twitterCard", e.target.value)}
            options={[
              { value: "summary", label: "Summary" },
              { value: "summary_large_image", label: "Summary Large Image" },
              { value: "app", label: "App" },
              { value: "player", label: "Player" }
            ]}
          />
        </Column>
      </Card>

      {/* Structured Data */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">Структурированные данные (JSON-LD)</Heading>
          
          <Input
            id="structured-data"
            label="JSON-LD Schema"
            value={seoData.structuredData}
            onChange={(e) => handleInputChange("structuredData", e.target.value)}
            placeholder='{"@context": "https://schema.org", "@type": "WebPage", "name": "Page Title"}'
          />
          
          <Text variant="body-default-s" onBackground="neutral-weak">
            Вставьте JSON-LD схему для улучшения понимания контента поисковыми системами
          </Text>
        </Column>
      </Card>

      {/* SEO Checklist */}
      <Card padding="xl" radius="l">
        <Column gap="l">
          <Heading variant="display-strong-s">SEO Чек-лист</Heading>
          
          <Grid columns={2} gap="m">
            <Flex gap="m" vertical="center">
              <Icon name="check" size="s" />
              <Text>Заголовок страницы</Text>
            </Flex>
            <Flex gap="m" vertical="center">
              <Icon name="check" size="s" />
              <Text>Meta description</Text>
            </Flex>
            <Flex gap="m" vertical="center">
              <Icon name="check" size="s" />
              <Text>Open Graph теги</Text>
            </Flex>
            <Flex gap="m" vertical="center">
              <Icon name="check" size="s" />
              <Text>Canonical URL</Text>
            </Flex>
            <Flex gap="m" vertical="center">
              <Icon name="check" size="s" />
              <Text>Robots meta</Text>
            </Flex>
            <Flex gap="m" vertical="center">
              <Icon name="check" size="s" />
              <Text>Structured data</Text>
            </Flex>
          </Grid>
        </Column>
      </Card>
    </Column>
  );
} 