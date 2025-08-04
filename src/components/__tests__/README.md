# Тесты для Header компонента

Этот каталог содержит регрессионные юнит-тесты для Header компонента и связанных с ним стилей.

## Структура тестов

### Header.test.tsx
Основные тесты для Header компонента, включающие:

- **Desktop Menu Tests**: Проверка отображения меню на десктопе
- **Mobile Menu Tests**: Проверка отображения меню на мобильных устройствах
- **Authentication Tests**: Тесты аутентификации и авторизации
- **Loading State Tests**: Тесты состояний загрузки
- **TimeDisplay Tests**: Тесты компонента отображения времени
- **Theme Toggle Tests**: Тесты переключения темы
- **CSS Classes Tests**: Тесты CSS классов
- **Navigation Tests**: Тесты навигации
- **Error Handling Tests**: Тесты обработки ошибок

### Header.styles.test.tsx
Тесты для CSS стилей Header компонента:

- **Adaptive Menu CSS Classes**: Тесты адаптивных CSS классов
- **Fade Elements**: Тесты fade элементов
- **Menu Structure**: Тесты структуры меню
- **Authentication Section Styling**: Тесты стилей секции аутентификации
- **Responsive Design Tests**: Тесты адаптивного дизайна
- **Accessibility Tests**: Тесты доступности
- **Theme Integration**: Тесты интеграции с темой
- **Time Display Integration**: Тесты интеграции с отображением времени

## Запуск тестов

```bash
# Запуск всех тестов
npm test

# Запуск тестов в режиме watch
npm run test:watch

# Запуск тестов с покрытием
npm run test:coverage

# Запуск только тестов Header
npm run test:header

# Запуск только тестов CSS
npm run test:css
```

## Покрытие тестами

Тесты покрывают следующие аспекты:

### Функциональность
- ✅ Отображение всех пунктов меню
- ✅ Правильные иконки для каждого пункта
- ✅ Состояние выбранного пункта меню
- ✅ Адаптивность (десктоп/мобильные)
- ✅ Аутентификация (вход/выход/регистрация)
- ✅ Отображение информации пользователя
- ✅ Кнопки администратора для админов
- ✅ Состояния загрузки

### Стили
- ✅ CSS классы `s-flex-hide` и `s-flex-show`
- ✅ Адаптивные стили для разных размеров экрана
- ✅ Правильное применение стилей из модулей
- ✅ Интеграция с OnceUI компонентами
- ✅ Доступность и ARIA атрибуты

### Интеграция
- ✅ Работа с AuthContext
- ✅ Интеграция с Next.js роутингом
- ✅ Совместимость с OnceUI
- ✅ Обработка ошибок

## Регрессионные тесты

Эти тесты гарантируют, что следующие проблемы не повторятся:

1. **Дублирование иконок в меню**
2. **Неправильное отображение на мобильных устройствах**
3. **Отсутствие текста в десктопной версии**
4. **Проблемы с аутентификацией**
5. **Неправильная работа адаптивных классов**

## Добавление новых тестов

При добавлении новой функциональности в Header:

1. Добавьте тесты в соответствующий describe блок
2. Убедитесь, что покрытие остается выше 80%
3. Добавьте тесты для новых CSS классов
4. Проверьте совместимость с OnceUI

## Примеры тестов

```typescript
// Тест адаптивности
test('should have s-flex-hide class for desktop version', () => {
  const { container } = render(<Header />);
  const desktopElements = container.querySelectorAll('.s-flex-hide');
  expect(desktopElements.length).toBeGreaterThan(0);
});

// Тест аутентификации
test('should show login and register buttons when user is not authenticated', () => {
  render(<Header />);
  expect(screen.getByText('Войти')).toBeInTheDocument();
  expect(screen.getByText('Регистрация')).toBeInTheDocument();
});

// Тест навигации
test('should show selected state for current page', () => {
  mockUsePathname.mockReturnValue('/about');
  render(<Header />);
  const aboutButton = screen.getByTestId('toggle-button');
  expect(aboutButton).toHaveAttribute('data-selected', 'true');
});
``` 