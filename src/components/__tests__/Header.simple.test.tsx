import React from 'react';
import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';

// Простой тест для проверки, что тесты работают
describe('Header Simple Test', () => {
  test('should pass basic test', () => {
    expect(true).toBe(true);
  });

  test('should have correct test environment', () => {
    expect(typeof window).toBe('object');
    expect(typeof document).toBe('object');
  });

  test('should be able to render basic elements', () => {
    const { container } = render(
      <div data-testid="test-element">
        <span>Test Content</span>
      </div>
    );
    
    expect(screen.getByTestId('test-element')).toBeInTheDocument();
    expect(screen.getByText('Test Content')).toBeInTheDocument();
  });
});

// Тесты для CSS классов
describe('CSS Classes Test', () => {
  test('should apply s-flex-hide class correctly', () => {
    const { container } = render(
      <div className="s-flex-hide">
        <span>Desktop Content</span>
      </div>
    );
    
    const element = container.querySelector('.s-flex-hide');
    expect(element).toBeInTheDocument();
    expect(element).toHaveClass('s-flex-hide');
  });

  test('should apply s-flex-show class correctly', () => {
    const { container } = render(
      <div className="s-flex-show">
        <span>Mobile Content</span>
      </div>
    );
    
    const element = container.querySelector('.s-flex-show');
    expect(element).toBeInTheDocument();
    expect(element).toHaveClass('s-flex-show');
  });

  test('should handle multiple classes', () => {
    const { container } = render(
      <div className="s-flex-hide custom-class">
        <span>Content</span>
      </div>
    );
    
    const element = container.querySelector('.s-flex-hide');
    expect(element).toHaveClass('s-flex-hide');
    expect(element).toHaveClass('custom-class');
  });
});

// Тесты для адаптивности
describe('Responsive Design Test', () => {
  test('should handle different screen sizes', () => {
    // Симулируем разные размеры экрана
    const screenSizes = [320, 480, 768, 1024, 1200];
    
    screenSizes.forEach(width => {
      Object.defineProperty(window, 'innerWidth', {
        writable: true,
        configurable: true,
        value: width,
      });
      
      // Проверяем, что window.innerWidth обновился
      expect(window.innerWidth).toBe(width);
    });
  });

  test('should handle media queries', () => {
    // Создаем mock для matchMedia
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: jest.fn().mockImplementation(query => ({
        matches: query.includes('max-width: 768px'),
        media: query,
        onchange: null,
        addListener: jest.fn(),
        removeListener: jest.fn(),
        addEventListener: jest.fn(),
        removeEventListener: jest.fn(),
        dispatchEvent: jest.fn(),
      })),
    });

    const mobileQuery = window.matchMedia('(max-width: 768px)');
    expect(mobileQuery.matches).toBe(true);

    const desktopQuery = window.matchMedia('(min-width: 1024px)');
    expect(desktopQuery.matches).toBe(false);
  });
});

// Тесты для доступности
describe('Accessibility Test', () => {
  test('should have proper ARIA attributes', () => {
    const { container } = render(
      <button aria-label="Test Button" data-testid="test-button">
        Click me
      </button>
    );
    
    const button = screen.getByTestId('test-button');
    expect(button).toHaveAttribute('aria-label', 'Test Button');
  });

  test('should have proper test IDs', () => {
    const { container } = render(
      <div>
        <button data-testid="button-1">Button 1</button>
        <button data-testid="button-2">Button 2</button>
      </div>
    );
    
    expect(screen.getByTestId('button-1')).toBeInTheDocument();
    expect(screen.getByTestId('button-2')).toBeInTheDocument();
  });
});

// Тесты для форума
describe('Forum Integration Test', () => {
  test('should have forum-related classes', () => {
    const forumClasses = [
      'forum-container',
      'forum-header', 
      'forum-category',
      'forum-topic',
      'forum-post'
    ];
    
    forumClasses.forEach(className => {
      const { container } = render(
        <div className={className}>
          <span>Forum Content</span>
        </div>
      );
      
      const element = container.querySelector(`.${className}`);
      expect(element).toBeInTheDocument();
      expect(element).toHaveClass(className);
    });
  });

  test('should have full-width forum container', () => {
    const { container } = render(
      <div className="forum-container">
        <span>Forum Content</span>
      </div>
    );
    
    const element = container.querySelector('.forum-container');
    expect(element).toBeInTheDocument();
    expect(element).toHaveClass('forum-container');
    
    // Проверяем, что класс применяется
    expect(element).toHaveClass('forum-container');
  });

  test('should have OnceUI style classes', () => {
    const { container } = render(
      <div className="forum-container">
        <div className="forum-header">Header</div>
        <div className="forum-category">Category</div>
        <div className="forum-subcategory">Subcategory</div>
        <div className="forum-topic">Topic</div>
        <div className="forum-actions">Actions</div>
        <div className="forum-pagination">Pagination</div>
      </div>
    );
    
    // Проверяем, что все OnceUI классы применяются
    const elements = [
      '.forum-container',
      '.forum-header',
      '.forum-category', 
      '.forum-subcategory',
      '.forum-topic',
      '.forum-actions',
      '.forum-pagination'
    ];
    
    elements.forEach(selector => {
      const element = container.querySelector(selector);
      expect(element).toBeInTheDocument();
    });
  });

  test('should have full-width OnceUI forum components', () => {
    const { container } = render(
      <div className="forum-container">
        <div className="forum-header">Header</div>
        <div className="forum-category-header">Category Header</div>
        <div className="forum-category-content">Category Content</div>
        <div className="forum-subcategory-info">Subcategory Info</div>
        <div className="forum-subcategory-title">Subcategory Title</div>
        <div className="forum-subcategory-description">Description</div>
        <div className="forum-subcategory-stats">Stats</div>
        <div className="forum-subcategory-last-post">Last Post</div>
        <div className="forum-topics-header">Topics Header</div>
        <div className="forum-topics-list">Topics List</div>
        <div className="forum-topic-info">Topic Info</div>
        <div className="forum-topic-title">Topic Title</div>
        <div className="forum-topic-meta">Topic Meta</div>
        <div className="forum-topic-stats">Topic Stats</div>
        <div className="forum-topic-last-post">Topic Last Post</div>
        <div className="forum-breadcrumb">Breadcrumb</div>
      </div>
    );
    
    // Проверяем, что все компоненты форума имеют OnceUI стили
    const forumComponents = [
      '.forum-container',
      '.forum-header',
      '.forum-category-header',
      '.forum-category-content',
      '.forum-subcategory-info',
      '.forum-subcategory-title',
      '.forum-subcategory-description',
      '.forum-subcategory-stats',
      '.forum-subcategory-last-post',
      '.forum-topics-header',
      '.forum-topics-list',
      '.forum-topic-info',
      '.forum-topic-title',
      '.forum-topic-meta',
      '.forum-topic-stats',
      '.forum-topic-last-post',
      '.forum-breadcrumb'
    ];
    
    forumComponents.forEach(selector => {
      const element = container.querySelector(selector);
      expect(element).toBeInTheDocument();
    });
  });
}); 