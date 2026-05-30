# Quiz Management Platform

A web-based quiz platform where admins can build and manage quizzes and users can take them, view scores, and track attempt history. Guests can take quizzes without an account; registered users get a persistent history dashboard.

Architecture decisions and extensibility design are documented in [ARCHITECTURE.md](ARCHITECTURE.md).

## Stack

- **PHP** 8.3+, **Laravel** 13
- **Livewire** 4 + **Flux UI** — server-rendered UI, no SPA
- **MySQL** 8+
- **Tailwind CSS** 4 via Vite

## Local Setup

**Requirements:** PHP 8.3+, Composer, Node 20+, MySQL 8+

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
```

Update `.env` with your local MySQL credentials:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=quiz_management
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

```bash
# 3. Create the database, then run migrations
php artisan migrate

# 4. Start the development server
composer run dev
```

`composer run dev` starts Laravel, the queue worker, the log viewer, and Vite concurrently. The app is available at [http://localhost:8000](http://localhost:8000).
