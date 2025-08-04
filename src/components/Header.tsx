"use client";

import { usePathname } from "next/navigation";
import { useEffect, useState } from "react";

import { Fade, Flex, Line, ToggleButton, Button, Text } from "@once-ui-system/core";

import { routes, display, person, about, blog, work, gallery } from "@/resources";
import { ThemeToggle } from "./ThemeToggle";
import styles from "./Header.module.scss";
import { useAuth } from "@/contexts/AuthContext";

type TimeDisplayProps = {
  fallbackTimeZone?: string;
  locale?: string;
};

const TimeDisplay: React.FC<TimeDisplayProps> = ({ fallbackTimeZone = "Europe/Moscow", locale = "ru-RU" }) => {
  const [currentTime, setCurrentTime] = useState("");
  const [userTimeZone, setUserTimeZone] = useState<string | null>(null);
  const [userLocation, setUserLocation] = useState<string>("");

  useEffect(() => {
    // Определяем часовой пояс пользователя
    const detectUserTimeZone = () => {
      try {
        const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        setUserTimeZone(timeZone);
        
        // Получаем название города из часового пояса
        const timeZoneNames: { [key: string]: string } = {
          "Europe/Moscow": "Москва",
          "Europe/London": "Лондон",
          "America/New_York": "Нью-Йорк",
          "America/Los_Angeles": "Лос-Анджелес",
          "Asia/Tokyo": "Токио",
          "Asia/Shanghai": "Шанхай",
          "Asia/Jakarta": "Джакарта",
          "Australia/Sydney": "Сидней",
          "Europe/Berlin": "Берлин",
          "Europe/Paris": "Париж",
          "Asia/Dubai": "Дубай",
          "Asia/Kolkata": "Мумбаи",
          "America/Sao_Paulo": "Сан-Паулу",
          "Africa/Cairo": "Каир",
          "Europe/Rome": "Рим",
          "Europe/Madrid": "Мадрид",
          "America/Toronto": "Торонто",
          "America/Chicago": "Чикаго",
          "America/Denver": "Денвер",
          "Pacific/Auckland": "Окленд",
          "Asia/Singapore": "Сингапур",
          "Asia/Bangkok": "Бангкок",
          "Asia/Ho_Chi_Minh": "Хошимин",
          "Asia/Manila": "Манила",
          "Asia/Kuala_Lumpur": "Куала-Лумпур",
          "Asia/Hong_Kong": "Гонконг",
          "Asia/Taipei": "Тайбэй",
          "Asia/Tehran": "Тегеран",
          "Asia/Karachi": "Карачи",
          "Asia/Dhaka": "Дакка",
          "Asia/Colombo": "Коломбо",
          "Asia/Kathmandu": "Катманду",
          "Asia/Ulaanbaatar": "Улан-Батор",
          "Asia/Vladivostok": "Владивосток",
          "Asia/Yekaterinburg": "Екатеринбург",
          "Asia/Novosibirsk": "Новосибирск",
          "Asia/Omsk": "Омск",
          "Asia/Krasnoyarsk": "Красноярск",
          "Asia/Irkutsk": "Иркутск",
          "Asia/Yakutsk": "Якутск",
          "Asia/Magadan": "Магадан",
          "Asia/Kamchatka": "Петропавловск-Камчатский",
          "Asia/Anadyr": "Анадырь",
        };
        
        const cityName = timeZoneNames[timeZone] || timeZone.split('/').pop()?.replace('_', ' ') || timeZone;
        setUserLocation(cityName);
      } catch (error) {
        console.error("Ошибка определения часового пояса:", error);
        setUserTimeZone(fallbackTimeZone);
        setUserLocation("Москва");
      }
    };

    detectUserTimeZone();
  }, [fallbackTimeZone]);

  useEffect(() => {
    const updateTime = () => {
      const now = new Date();
      const timeZone = userTimeZone || fallbackTimeZone;
      
      const options: Intl.DateTimeFormatOptions = {
        timeZone,
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
        hour12: false,
      };
      
      const timeString = new Intl.DateTimeFormat(locale, options).format(now);
      setCurrentTime(timeString);
    };

    if (userTimeZone || fallbackTimeZone) {
      updateTime();
      const intervalId = setInterval(updateTime, 1000);
      return () => clearInterval(intervalId);
    }
  }, [userTimeZone, fallbackTimeZone, locale]);

  return (
    <Flex gap="s" vertical="center">
      <span>{userLocation}</span>
      <span>{currentTime}</span>
    </Flex>
  );
};

export default TimeDisplay;

export const Header = () => {
  const pathname = usePathname() ?? "";
  const { user, loading, logout } = useAuth();

  return (
    <>
      <Fade className="s-flex-hide" fillWidth position="fixed" height="80" zIndex={9} />
      <Fade className="s-flex-show" fillWidth position="fixed" bottom="0" to="top" height="80" zIndex={9} />
      <Flex
        fitHeight
        position="unset"
        className={styles.position}
        as="header"
        zIndex={9}
        fillWidth
        padding="8"
        horizontal="center"
        data-border="rounded"
      >
        <Flex paddingLeft="12" fillWidth vertical="center" textVariant="body-default-s">
          {display.time && <TimeDisplay />}
        </Flex>
        <Flex fillWidth horizontal="center">
          <Flex
            background="page"
            border="neutral-alpha-weak"
            radius="m-4"
            shadow="l"
            padding="4"
            horizontal="center"
            zIndex={1}
          >
            <Flex gap="4" vertical="center" textVariant="body-default-s" suppressHydrationWarning>
              {routes["/"] && (
                <ToggleButton prefixIcon="home" href="/" selected={pathname === "/"} />
              )}
              <Line background="neutral-alpha-medium" vert maxHeight="24" />
              {routes["/about"] && (
                <Flex className="s-flex-hide">
                  <ToggleButton
                    prefixIcon="person"
                    href="/about"
                    label={about.label}
                    selected={pathname === "/about"}
                  />
                </Flex>
              )}
              {routes["/about"] && (
                <Flex className="s-flex-show">
                  <ToggleButton
                    prefixIcon="person"
                    href="/about"
                    selected={pathname === "/about"}
                  />
                </Flex>
              )}
              {routes["/work"] && (
                <Flex className="s-flex-hide">
                  <ToggleButton
                    prefixIcon="grid"
                    href="/work"
                    label={work.label}
                    selected={pathname.startsWith("/work")}
                  />
                </Flex>
              )}
              {routes["/work"] && (
                <Flex className="s-flex-show">
                  <ToggleButton
                    prefixIcon="grid"
                    href="/work"
                    selected={pathname.startsWith("/work")}
                  />
                </Flex>
              )}
              {routes["/blog"] && (
                <Flex className="s-flex-hide">
                  <ToggleButton
                    prefixIcon="book"
                    href="/blog"
                    label={blog.label}
                    selected={pathname.startsWith("/blog")}
                  />
                </Flex>
              )}
              {routes["/blog"] && (
                <Flex className="s-flex-show">
                  <ToggleButton
                    prefixIcon="book"
                    href="/blog"
                    selected={pathname.startsWith("/blog")}
                  />
                </Flex>
              )}
              {routes["/gallery"] && (
                <Flex className="s-flex-hide">
                  <ToggleButton
                    prefixIcon="gallery"
                    href="/gallery"
                    label={gallery.label}
                    selected={pathname.startsWith("/gallery")}
                  />
                </Flex>
              )}
              {routes["/gallery"] && (
                <Flex className="s-flex-show">
                  <ToggleButton
                    prefixIcon="gallery"
                    href="/gallery"
                    selected={pathname.startsWith("/gallery")}
                  />
                </Flex>
              )}
              <Flex className="s-flex-hide">
                <ToggleButton
                  prefixIcon="forum"
                  href="/forum"
                  label="Форум"
                  selected={pathname.startsWith("/forum")}
                />
              </Flex>
              <Flex className="s-flex-show">
                <ToggleButton
                  prefixIcon="forum"
                  href="/forum"
                  selected={pathname.startsWith("/forum")}
                />
              </Flex>
              {display.themeSwitcher && (
                <>
                  <Line background="neutral-alpha-medium" vert maxHeight="24" />
                  <ThemeToggle />
                </>
              )}
            </Flex>
          </Flex>
        </Flex>
        <Flex fillWidth horizontal="end" vertical="center">
          <Flex
            paddingRight="12"
            horizontal="end"
            vertical="center"
            textVariant="body-default-s"
            gap="20"
          >
            {!loading && (
              <>
                {user ? (
                  // Пользователь авторизован
                  <>
                    {/* Десктопная версия */}
                    <Flex className="s-flex-hide" gap="s" vertical="center">
                      <Text variant="body-default-xs" onBackground="neutral-weak">
                        {user.members_display_name || user.name || user.email}
                      </Text>
                      {user.member_group_id === 4 && (
                        <Button 
                          variant="secondary" 
                          size="s"
                          href="/admin"
                          prefixIcon="settings"
                        >
                          Админ
                        </Button>
                      )}
                      <Button 
                        variant="secondary" 
                        size="s"
                        href="/profile"
                        prefixIcon="user"
                      >
                        Профиль
                      </Button>
                      <Button 
                        variant="secondary" 
                        size="s"
                        onClick={logout}
                        prefixIcon="logout"
                      >
                        Выйти
                      </Button>
                    </Flex>
                    {/* Мобильная версия */}
                    <Flex className="s-flex-show" gap="s" vertical="center">
                      {user.member_group_id === 4 && (
                        <Button 
                          variant="secondary" 
                          size="s"
                          href="/admin"
                          prefixIcon="settings"
                        />
                      )}
                      <Button 
                        variant="secondary" 
                        size="s"
                        href="/profile"
                        prefixIcon="user"
                      />
                      <Button 
                        variant="secondary" 
                        size="s"
                        onClick={logout}
                        prefixIcon="logout"
                      />
                    </Flex>
                  </>
                ) : (
                  // Пользователь не авторизован
                  <>
                    {/* Десктопная версия */}
                    <Flex className="s-flex-hide" gap="s" vertical="center">
                      <Button 
                        variant="secondary" 
                        size="s"
                        href="/auth/login"
                        prefixIcon="login"
                      >
                        Войти
                      </Button>
                      <Button 
                        variant="primary" 
                        size="s"
                        href="/auth/register"
                        prefixIcon="user-plus"
                      >
                        Регистрация
                      </Button>
                    </Flex>
                    {/* Мобильная версия */}
                    <Flex className="s-flex-show" gap="s" vertical="center">
                      <Button 
                        variant="secondary" 
                        size="s"
                        href="/auth/login"
                        prefixIcon="login"
                      />
                      <Button 
                        variant="primary" 
                        size="s"
                        href="/auth/register"
                        prefixIcon="userPlus"
                      />
                    </Flex>
                  </>
                )}
              </>
            )}
          </Flex>
        </Flex>
      </Flex>
    </>
  );
};
