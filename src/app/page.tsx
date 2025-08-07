import { Header } from '@/components/Header';

export default function HomePage() {
  return (
    <div>
      <Header />
      <main className="container mx-auto px-4 py-8">
        <h1 className="text-4xl font-bold mb-8">Добро пожаловать в CloudMasters Forum</h1>
        <p className="text-lg mb-4">
          Форум для обсуждения магии, эзотерики и духовного развития.
        </p>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
          <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Форум</h2>
            <p className="text-gray-600 dark:text-gray-300 mb-4">
              Обсуждение различных тем и вопросов
            </p>
            <a href="/forum" className="text-blue-600 hover:text-blue-800">
              Перейти к форуму →
            </a>
          </div>
          <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Профиль</h2>
            <p className="text-gray-600 dark:text-gray-300 mb-4">
              Управление вашим профилем
            </p>
            <a href="/profile" className="text-blue-600 hover:text-blue-800">
              Перейти к профилю →
            </a>
          </div>
          <div className="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h2 className="text-xl font-semibold mb-4">Авторизация</h2>
            <p className="text-gray-600 dark:text-gray-300 mb-4">
              Вход в систему
            </p>
            <a href="/auth/login" className="text-blue-600 hover:text-blue-800">
              Войти →
            </a>
          </div>
        </div>
      </main>
    </div>
  );
}
