# Laravel Docker Приложение

Современный стек: **Laravel 11 + PHP 8.4 + PostgreSQL + Nginx + Docker**

## 🚀 Быстрый запуск

```bash
git clone https://github.com/Rifat172/laravel-task.git
cd laravel-task

cp .env.example .env

# Первый запуск (сборка образа)
docker compose up -d --build

# Установка зависимостей
docker compose exec app composer install --no-interaction --optimize-autoloader

# Laravel настройки
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate

# Остановить контейнеры (данные сохраняются)
docker compose stop