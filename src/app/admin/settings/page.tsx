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

interface SiteSettings {
  // Основные настройки
  siteName: string;
  siteDescription: string;
  siteUrl: string;
  contactEmail: string;
  
  // Тема и дизайн
  theme: "light" | "dark" | "system";
  primaryColor: string;
  accentColor: string;
  neutralColor: string;
  
  // Функциональность
  enableBlog: boolean;
  enableProjects: boolean;
  enableGallery: boolean;
  enableNewsletter: boolean;
  
  // Аналитика
  googleAnalyticsId: string;
  googleTagManagerId: string;
  
  // Социальные сети
  socialLinks: {
    github: string;
    linkedin: string;
    twitter: string;
    email: string;
  };
  
  // Безопасность
  enablePasswordProtection: boolean;
  protectedRoutes: string[];
}

const defaultSettings: SiteSettings = {
  siteName: "CloudMasters Portfolio",
  siteDescription: "Портфолио разработчика с использованием Once UI",
  siteUrl: "https://89.111.170.207",
  contactEmail: "admin@cloudmasters.com",
  
  theme: "system",
  primaryColor: "#0891b2",
  accentColor: "#dc2626",
  neutralColor: "#6b7280",
  
  enableBlog: true,
  enableProjects: true,
  enableGallery: true,
  enableNewsletter: true,
  
  googleAnalyticsId: "",
  googleTagManagerId: "",
  
  socialLinks: {
    github: "https://github.com/zzzloj",
    linkedin: "https://linkedin.com/in/cloudmasters",
    twitter: "https://twitter.com/cloudmasters",
    email: "mailto:admin@cloudmasters.com"
  },
  
  enablePasswordProtection: false,
  protectedRoutes: []
};

export default function SiteSettings() {
  const [settings, setSettings] = useState<SiteSettings>(defaultSettings);
  const [isSaving, setIsSaving] = useState(false);
  const [activeTab, setActiveTab] = useState("general");

  const handleSave = async () => {
    setIsSaving(true);
    // Имитация сохранения
    await new Promise(resolve => setTimeout(resolve, 1000));
    setIsSaving(false);
    alert("Настройки сохранены!");
  };

  const handleSettingChange = (field: keyof SiteSettings, value: any) => {
    setSettings({ ...settings, [field]: value });
  };

  const handleSocialLinkChange = (platform: keyof SiteSettings['socialLinks'], value: string) => {
    setSettings({
      ...settings,
      socialLinks: {
        ...settings.socialLinks,
        [platform]: value
      }
    });
  };

  const tabs = [
    { id: "general", label: "Основные", icon: "settings" },
    { id: "design", label: "Дизайн", icon: "palette" },
    { id: "features", label: "Функции", icon: "grid" },
    { id: "analytics", label: "Аналитика", icon: "analytics" },
    { id: "social", label: "Соцсети", icon: "share" },
    { id: "security", label: "Безопасность", icon: "lock" }
  ];

  return (
    <Column maxWidth="xl" gap="xl">
      <Schema
        as="webPage"
        baseURL={baseURL}
        title="Site Settings - Admin Panel"
        description="Настройки сайта"
        path="/admin/settings"
      />
      
      {/* Header */}
      <Flex fillWidth horizontal="between" vertical="center" paddingY="l">
        <Heading variant="display-strong-l">Настройки сайта</Heading>
        <Button 
          variant="primary" 
          prefixIcon="save"
          onClick={handleSave}
          loading={isSaving}
        >
          Сохранить
        </Button>
      </Flex>

      {/* Tabs */}
      <Card padding="l" radius="m">
        <Flex gap="m" wrap>
          {tabs.map((tab) => (
            <Button
              key={tab.id}
              variant={activeTab === tab.id ? "primary" : "secondary"}
              prefixIcon={tab.icon}
              onClick={() => setActiveTab(tab.id)}
            >
              {tab.label}
            </Button>
          ))}
        </Flex>
      </Card>

      {/* General Settings */}
      {activeTab === "general" && (
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Основные настройки</Heading>
            
            <Grid columns={2} gap="l">
              <Input
                id="site-name"
                label="Название сайта"
                value={settings.siteName}
                onChange={(e) => handleSettingChange("siteName", e.target.value)}
              />
              
              <Input
                id="site-url"
                label="URL сайта"
                value={settings.siteUrl}
                onChange={(e) => handleSettingChange("siteUrl", e.target.value)}
              />
            </Grid>
            
            <Input
              id="site-description"
              label="Описание сайта"
              value={settings.siteDescription}
              onChange={(e) => handleSettingChange("siteDescription", e.target.value)}
            />
            
            <Input
              id="contact-email"
              label="Email для связи"
              value={settings.contactEmail}
              onChange={(e) => handleSettingChange("contactEmail", e.target.value)}
              type="email"
            />
          </Column>
        </Card>
      )}

      {/* Design Settings */}
      {activeTab === "design" && (
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Настройки дизайна</Heading>
            
            <Select
              id="theme-select"
              label="Тема"
              value={settings.theme}
              onChange={(e) => handleSettingChange("theme", e.target.value)}
              options={[
                { value: "light", label: "Светлая" },
                { value: "dark", label: "Темная" },
                { value: "system", label: "Системная" }
              ]}
            />
            
            <Grid columns={3} gap="l">
              <Column gap="s">
                <Text variant="body-default-s">Основной цвет</Text>
                <Input
                  id="primary-color"
                  value={settings.primaryColor}
                  onChange={(e) => handleSettingChange("primaryColor", e.target.value)}
                  type="color"
                />
              </Column>
              
              <Column gap="s">
                <Text variant="body-default-s">Акцентный цвет</Text>
                <Input
                  id="accent-color"
                  value={settings.accentColor}
                  onChange={(e) => handleSettingChange("accentColor", e.target.value)}
                  type="color"
                />
              </Column>
              
              <Column gap="s">
                <Text variant="body-default-s">Нейтральный цвет</Text>
                <Input
                  id="neutral-color"
                  value={settings.neutralColor}
                  onChange={(e) => handleSettingChange("neutralColor", e.target.value)}
                  type="color"
                />
              </Column>
            </Grid>
          </Column>
        </Card>
      )}

      {/* Features Settings */}
      {activeTab === "features" && (
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Функциональность</Heading>
            
            <Grid columns={2} gap="l">
              <Flex horizontal="between" vertical="center" paddingY="s">
                <Text>Блог</Text>
                <input
                  type="checkbox"
                  checked={settings.enableBlog}
                  onChange={(e) => handleSettingChange("enableBlog", e.target.checked)}
                />
              </Flex>
              
              <Flex horizontal="between" vertical="center" paddingY="s">
                <Text>Проекты</Text>
                <input
                  type="checkbox"
                  checked={settings.enableProjects}
                  onChange={(e) => handleSettingChange("enableProjects", e.target.checked)}
                />
              </Flex>
              
              <Flex horizontal="between" vertical="center" paddingY="s">
                <Text>Галерея</Text>
                <input
                  type="checkbox"
                  checked={settings.enableGallery}
                  onChange={(e) => handleSettingChange("enableGallery", e.target.checked)}
                />
              </Flex>
              
              <Flex horizontal="between" vertical="center" paddingY="s">
                <Text>Рассылка</Text>
                <input
                  type="checkbox"
                  checked={settings.enableNewsletter}
                  onChange={(e) => handleSettingChange("enableNewsletter", e.target.checked)}
                />
              </Flex>
            </Grid>
          </Column>
        </Card>
      )}

      {/* Analytics Settings */}
      {activeTab === "analytics" && (
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Аналитика</Heading>
            
            <Input
              id="ga-id"
              label="Google Analytics ID"
              value={settings.googleAnalyticsId}
              onChange={(e) => handleSettingChange("googleAnalyticsId", e.target.value)}
              placeholder="G-XXXXXXXXXX"
            />
            
            <Input
              id="gtm-id"
              label="Google Tag Manager ID"
              value={settings.googleTagManagerId}
              onChange={(e) => handleSettingChange("googleTagManagerId", e.target.value)}
              placeholder="GTM-XXXXXXX"
            />
            
            <Text variant="body-default-s" onBackground="neutral-weak">
              Оставьте поля пустыми, если не используете Google Analytics или Tag Manager
            </Text>
          </Column>
        </Card>
      )}

      {/* Social Settings */}
      {activeTab === "social" && (
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Социальные сети</Heading>
            
            <Grid columns={2} gap="l">
              <Input
                id="github-link"
                label="GitHub"
                value={settings.socialLinks.github}
                onChange={(e) => handleSocialLinkChange("github", e.target.value)}
                placeholder="https://github.com/username"
              />
              
              <Input
                id="linkedin-link"
                label="LinkedIn"
                value={settings.socialLinks.linkedin}
                onChange={(e) => handleSocialLinkChange("linkedin", e.target.value)}
                placeholder="https://linkedin.com/in/username"
              />
              
              <Input
                id="twitter-link"
                label="Twitter"
                value={settings.socialLinks.twitter}
                onChange={(e) => handleSocialLinkChange("twitter", e.target.value)}
                placeholder="https://twitter.com/username"
              />
              
              <Input
                id="email-link"
                label="Email"
                value={settings.socialLinks.email}
                onChange={(e) => handleSocialLinkChange("email", e.target.value)}
                placeholder="mailto:email@example.com"
              />
            </Grid>
          </Column>
        </Card>
      )}

      {/* Security Settings */}
      {activeTab === "security" && (
        <Card padding="xl" radius="l">
          <Column gap="l">
            <Heading variant="display-strong-s">Безопасность</Heading>
            
            <Flex horizontal="between" vertical="center" paddingY="s">
              <Text>Защита паролем</Text>
              <input
                type="checkbox"
                checked={settings.enablePasswordProtection}
                onChange={(e) => handleSettingChange("enablePasswordProtection", e.target.checked)}
              />
            </Flex>
            
            <Input
              id="protected-routes"
              label="Защищенные маршруты"
              value={settings.protectedRoutes.join(", ")}
              onChange={(e) => handleSettingChange("protectedRoutes", e.target.value.split(", "))}
              placeholder="/admin, /private, /secret"
            />
            
            <Text variant="body-default-s" onBackground="neutral-weak">
              Укажите маршруты через запятую, которые должны быть защищены паролем
            </Text>
          </Column>
        </Card>
      )}
    </Column>
  );
} 