import React from 'react';
import { render, screen } from '@testing-library/react';
import { usePathname } from 'next/navigation';
import { Header } from '../Header';
import { useAuth } from '@/contexts/AuthContext';

// Mock Next.js navigation
jest.mock('next/navigation', () => ({
  usePathname: jest.fn(),
}));

// Mock AuthContext
jest.mock('@/contexts/AuthContext', () => ({
  useAuth: jest.fn(),
}));

// Mock OnceUI components
jest.mock('@once-ui-system/core', () => ({
  Fade: ({ children, className, ...props }: any) => (
    <div data-testid="fade" className={className} {...props}>
      {children}
    </div>
  ),
  Flex: ({ children, className, ...props }: any) => (
    <div data-testid="flex" className={className} {...props}>
      {children}
    </div>
  ),
  Line: ({ ...props }: any) => <div data-testid="line" {...props} />,
  ToggleButton: ({ prefixIcon, href, label, selected, ...props }: any) => (
    <button data-testid="toggle-button" data-icon={prefixIcon} data-href={href} data-label={label} data-selected={selected} {...props}>
      {prefixIcon && <span data-testid="icon">{prefixIcon}</span>}
      {label && <span data-testid="label">{label}</span>}
    </button>
  ),
  Button: ({ children, prefixIcon, href, onClick, ...props }: any) => (
    <button data-testid="button" data-icon={prefixIcon} data-href={href} onClick={onClick} {...props}>
      {prefixIcon && <span data-testid="icon">{prefixIcon}</span>}
      {children}
    </button>
  ),
  Text: ({ children, ...props }: any) => (
    <span data-testid="text" {...props}>
      {children}
    </span>
  ),
}));

// Mock resources
jest.mock('@/resources', () => ({
  routes: {
    '/': true,
    '/about': true,
    '/work': true,
    '/blog': true,
    '/gallery': true,
  },
  display: {
    time: true,
    themeSwitcher: true,
  },
  about: { label: 'О проекте' },
  work: { label: 'Работы' },
  blog: { label: 'Блог' },
  gallery: { label: 'Галерея' },
}));

// Mock ThemeToggle
jest.mock('../ThemeToggle', () => ({
  ThemeToggle: () => <div data-testid="theme-toggle" />,
}));

// Mock styles
jest.mock('../Header.module.scss', () => ({
  position: 'position-class',
}));

describe('Header Component', () => {
  const mockUsePathname = usePathname as jest.MockedFunction<typeof usePathname>;
  const mockUseAuth = useAuth as jest.MockedFunction<typeof useAuth>;

  beforeEach(() => {
    mockUsePathname.mockReturnValue('/');
    mockUseAuth.mockReturnValue({
      user: null,
      loading: false,
      login: jest.fn(),
      register: jest.fn(),
      logout: jest.fn(),
      updateProfile: jest.fn(),
      changePassword: jest.fn(),
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  describe('Desktop Menu Tests', () => {
    test('should render all menu items with icons and labels on desktop', () => {
      render(<Header />);

      // Check that all menu items are present with labels
      expect(screen.getByTestId('toggle-button')).toBeInTheDocument();
      expect(screen.getByTestId('label')).toBeInTheDocument();
    });

    test('should render home button', () => {
      render(<Header />);
      
      const homeButton = screen.getByTestId('toggle-button');
      expect(homeButton).toHaveAttribute('data-icon', 'home');
      expect(homeButton).toHaveAttribute('data-href', '/');
    });

    test('should render about button with correct label', () => {
      render(<Header />);
      
      const aboutButton = screen.getByTestId('toggle-button');
      expect(aboutButton).toHaveAttribute('data-icon', 'person');
      expect(screen.getByText('О проекте')).toBeInTheDocument();
    });

    test('should render forum button with correct label', () => {
      render(<Header />);
      
      const forumButton = screen.getByTestId('toggle-button');
      expect(forumButton).toHaveAttribute('data-icon', 'forum');
      expect(screen.getByText('Форум')).toBeInTheDocument();
    });

    test('should show selected state for current page', () => {
      mockUsePathname.mockReturnValue('/about');
      render(<Header />);
      
      const aboutButton = screen.getByTestId('toggle-button');
      expect(aboutButton).toHaveAttribute('data-selected', 'true');
    });
  });

  describe('Mobile Menu Tests', () => {
    test('should render mobile-specific elements', () => {
      render(<Header />);
      
      // Check that s-flex-show and s-flex-hide classes are present
      const flexElements = screen.getAllByTestId('flex');
      const hasMobileClasses = flexElements.some(element => 
        element.className.includes('s-flex-show') || element.className.includes('s-flex-hide')
      );
      expect(hasMobileClasses).toBe(true);
    });
  });

  describe('Authentication Tests', () => {
    test('should show login and register buttons when user is not authenticated', () => {
      render(<Header />);
      
      const loginButton = screen.getByText('Войти');
      const registerButton = screen.getByText('Регистрация');
      
      expect(loginButton).toBeInTheDocument();
      expect(registerButton).toBeInTheDocument();
    });

    test('should show user info and logout button when user is authenticated', () => {
      mockUseAuth.mockReturnValue({
        user: {
          email: 'test@example.com',
          members_display_name: 'Test User',
          member_group_id: 1,
        },
        loading: false,
        login: jest.fn(),
        register: jest.fn(),
        logout: jest.fn(),
        updateProfile: jest.fn(),
        changePassword: jest.fn(),
      });

      render(<Header />);
      
      expect(screen.getByText('Test User')).toBeInTheDocument();
      expect(screen.getByText('Профиль')).toBeInTheDocument();
      expect(screen.getByText('Выйти')).toBeInTheDocument();
    });

    test('should show admin button for admin users', () => {
      mockUseAuth.mockReturnValue({
        user: {
          email: 'admin@example.com',
          members_display_name: 'Admin User',
          member_group_id: 4, // Admin group
        },
        loading: false,
        login: jest.fn(),
        register: jest.fn(),
        logout: jest.fn(),
        updateProfile: jest.fn(),
        changePassword: jest.fn(),
      });

      render(<Header />);
      
      expect(screen.getByText('Админ')).toBeInTheDocument();
    });

    test('should not show admin button for non-admin users', () => {
      mockUseAuth.mockReturnValue({
        user: {
          email: 'user@example.com',
          members_display_name: 'Regular User',
          member_group_id: 1, // Regular user group
        },
        loading: false,
        login: jest.fn(),
        register: jest.fn(),
        logout: jest.fn(),
        updateProfile: jest.fn(),
        changePassword: jest.fn(),
      });

      render(<Header />);
      
      expect(screen.queryByText('Админ')).not.toBeInTheDocument();
    });
  });

  describe('Loading State Tests', () => {
    test('should not show auth buttons when loading', () => {
      mockUseAuth.mockReturnValue({
        user: null,
        loading: true,
        login: jest.fn(),
        register: jest.fn(),
        logout: jest.fn(),
        updateProfile: jest.fn(),
        changePassword: jest.fn(),
      });

      render(<Header />);
      
      expect(screen.queryByText('Войти')).not.toBeInTheDocument();
      expect(screen.queryByText('Регистрация')).not.toBeInTheDocument();
    });
  });

  describe('TimeDisplay Tests', () => {
    test('should render TimeDisplay when display.time is true', () => {
      render(<Header />);
      
      // TimeDisplay should be rendered in the left section
      const leftSection = screen.getByTestId('flex');
      expect(leftSection).toBeInTheDocument();
    });
  });

  describe('Theme Toggle Tests', () => {
    test('should render theme toggle when display.themeSwitcher is true', () => {
      render(<Header />);
      
      expect(screen.getByTestId('theme-toggle')).toBeInTheDocument();
    });
  });

  describe('CSS Classes Tests', () => {
    test('should apply correct CSS classes for adaptive menu', () => {
      render(<Header />);
      
      const flexElements = screen.getAllByTestId('flex');
      
      // Check for s-flex-hide and s-flex-show classes
      const hasAdaptiveClasses = flexElements.some(element => 
        element.className.includes('s-flex-hide') || element.className.includes('s-flex-show')
      );
      
      expect(hasAdaptiveClasses).toBe(true);
    });

    test('should apply position class from styles', () => {
      render(<Header />);
      
      const header = screen.getByTestId('flex');
      expect(header).toHaveClass('position-class');
    });
  });

  describe('Navigation Tests', () => {
    test('should handle different pathnames correctly', () => {
      const testCases = [
        { path: '/', expected: 'home' },
        { path: '/about', expected: 'person' },
        { path: '/work', expected: 'grid' },
        { path: '/blog', expected: 'book' },
        { path: '/gallery', expected: 'gallery' },
        { path: '/forum', expected: 'forum' },
      ];

      testCases.forEach(({ path, expected }) => {
        mockUsePathname.mockReturnValue(path);
        const { unmount } = render(<Header />);
        
        const button = screen.getByTestId('toggle-button');
        expect(button).toHaveAttribute('data-icon', expected);
        
        unmount();
      });
    });
  });

  describe('Error Handling Tests', () => {
    test('should handle missing user data gracefully', () => {
      mockUseAuth.mockReturnValue({
        user: {
          email: 'test@example.com',
          // Missing members_display_name
        },
        loading: false,
        login: jest.fn(),
        register: jest.fn(),
        logout: jest.fn(),
        updateProfile: jest.fn(),
        changePassword: jest.fn(),
      });

      render(<Header />);
      
      // Should fallback to email
      expect(screen.getByText('test@example.com')).toBeInTheDocument();
    });
  });
}); 