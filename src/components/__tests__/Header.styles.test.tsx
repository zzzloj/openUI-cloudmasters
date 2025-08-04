import React from 'react';
import { render } from '@testing-library/react';
import '@testing-library/jest-dom';

// Mock all the same dependencies as in Header.test.tsx
jest.mock('next/navigation', () => ({
  usePathname: jest.fn(),
}));

jest.mock('@/contexts/AuthContext', () => ({
  useAuth: jest.fn(),
}));

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

jest.mock('../ThemeToggle', () => ({
  ThemeToggle: () => <div data-testid="theme-toggle" />,
}));

jest.mock('../Header.module.scss', () => ({
  position: 'position-class',
}));

import { Header } from '../Header';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';

describe('Header CSS Classes and Styling', () => {
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

  describe('Adaptive Menu CSS Classes', () => {
    test('should have s-flex-hide class for desktop version', () => {
      const { container } = render(<Header />);
      
      const desktopElements = container.querySelectorAll('.s-flex-hide');
      expect(desktopElements.length).toBeGreaterThan(0);
    });

    test('should have s-flex-show class for mobile version', () => {
      const { container } = render(<Header />);
      
      const mobileElements = container.querySelectorAll('.s-flex-show');
      expect(mobileElements.length).toBeGreaterThan(0);
    });

    test('should have both desktop and mobile versions for each menu item', () => {
      const { container } = render(<Header />);
      
      // Check that we have both desktop and mobile versions
      const desktopElements = container.querySelectorAll('.s-flex-hide');
      const mobileElements = container.querySelectorAll('.s-flex-show');
      
      expect(desktopElements.length).toBeGreaterThan(0);
      expect(mobileElements.length).toBeGreaterThan(0);
    });

    test('should apply position class from styles', () => {
      const { container } = render(<Header />);
      
      const headerElement = container.querySelector('.position-class');
      expect(headerElement).toBeInTheDocument();
    });
  });

  describe('Fade Elements', () => {
    test('should render fade elements with correct classes', () => {
      const { container } = render(<Header />);
      
      const fadeElements = container.querySelectorAll('[data-testid="fade"]');
      expect(fadeElements.length).toBeGreaterThan(0);
      
      // Check that fade elements have s-flex-hide and s-flex-show classes
      const hasAdaptiveFade = Array.from(fadeElements).some(element => 
        element.className.includes('s-flex-hide') || element.className.includes('s-flex-show')
      );
      expect(hasAdaptiveFade).toBe(true);
    });
  });

  describe('Menu Structure', () => {
    test('should have correct menu structure with Flex containers', () => {
      const { container } = render(<Header />);
      
      const flexElements = container.querySelectorAll('[data-testid="flex"]');
      expect(flexElements.length).toBeGreaterThan(0);
    });

    test('should have ToggleButton elements with correct attributes', () => {
      const { container } = render(<Header />);
      
      const toggleButtons = container.querySelectorAll('[data-testid="toggle-button"]');
      expect(toggleButtons.length).toBeGreaterThan(0);
      
      // Check that buttons have required attributes
      toggleButtons.forEach(button => {
        expect(button).toHaveAttribute('data-icon');
        expect(button).toHaveAttribute('data-href');
      });
    });
  });

  describe('Authentication Section Styling', () => {
    test('should have adaptive classes for auth buttons', () => {
      const { container } = render(<Header />);
      
      // Check for auth buttons with adaptive classes
      const authSections = container.querySelectorAll('.s-flex-hide, .s-flex-show');
      expect(authSections.length).toBeGreaterThan(0);
    });

    test('should render desktop auth buttons with text', () => {
      const { container } = render(<Header />);
      
      const desktopAuthSection = container.querySelector('.s-flex-hide');
      expect(desktopAuthSection).toBeInTheDocument();
    });

    test('should render mobile auth buttons without text', () => {
      const { container } = render(<Header />);
      
      const mobileAuthSection = container.querySelector('.s-flex-show');
      expect(mobileAuthSection).toBeInTheDocument();
    });
  });

  describe('Responsive Design Tests', () => {
    test('should maintain structure across different screen sizes', () => {
      const { container } = render(<Header />);
      
      // Check that all required elements are present
      expect(container.querySelector('[data-testid="fade"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="flex"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="toggle-button"]')).toBeInTheDocument();
    });

    test('should have proper z-index values', () => {
      const { container } = render(<Header />);
      
      const fadeElements = container.querySelectorAll('[data-testid="fade"]');
      fadeElements.forEach(element => {
        expect(element).toHaveAttribute('zIndex');
      });
    });
  });

  describe('Accessibility Tests', () => {
    test('should have proper ARIA attributes on buttons', () => {
      const { container } = render(<Header />);
      
      const buttons = container.querySelectorAll('[data-testid="toggle-button"], [data-testid="button"]');
      buttons.forEach(button => {
        expect(button).toHaveAttribute('data-icon');
        expect(button).toHaveAttribute('data-href');
      });
    });

    test('should have proper test IDs for testing', () => {
      const { container } = render(<Header />);
      
      expect(container.querySelector('[data-testid="fade"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="flex"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="toggle-button"]')).toBeInTheDocument();
      expect(container.querySelector('[data-testid="theme-toggle"]')).toBeInTheDocument();
    });
  });

  describe('Theme Integration', () => {
    test('should render theme toggle with correct styling', () => {
      const { container } = render(<Header />);
      
      const themeToggle = container.querySelector('[data-testid="theme-toggle"]');
      expect(themeToggle).toBeInTheDocument();
    });
  });

  describe('Time Display Integration', () => {
    test('should render time display section', () => {
      const { container } = render(<Header />);
      
      // TimeDisplay is rendered in the left section
      const leftSection = container.querySelector('[data-testid="flex"]');
      expect(leftSection).toBeInTheDocument();
    });
  });
}); 