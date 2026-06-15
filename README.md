# Laravel Docker Приложение

Современный стек: **Laravel 11 + PHP 8.4 + PostgreSQL + Nginx + Docker**

## 🚀 Быстрый запуск

### 1. Клонируйте репозиторий
```bash
git clone <ваш-репозиторий>
cd <название-папки>

cp .env.example .env

# Первый запуск (сборка образа)
docker compose up -d --build

# Обычный запуск в будущем
docker compose up -d

# Генерация ключа приложения
docker compose exec app php artisan key:generate

# Выполнение миграций
docker compose exec app php artisan migrate

# (Опционально) Заполнение тестовыми данными
docker compose exec app php artisan db:seed