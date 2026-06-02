# Quiz Management Platform

A web-based quiz platform where admins can build and manage quizzes and users can take them, view scores, and track attempt history. Guests can take quizzes without an account; registered users get a persistent history dashboard.

Architecture decisions and extensibility design are documented in [ARCHITECTURE.md](ARCHITECTURE.md).
The full entity-relationship diagram, schema rationale, and extensibility analysis are in [DATA_MODEL.md](DATA_MODEL.md).

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
```

## Demo Data (Seeders)

The project ships with a complete set of seeders that populate the database with everything needed to explore all platform features without manually creating any data.

| Seeder | What it creates |
|--------|----------------|
| `UserTypeSeeder` | `admin` and `user` role records |
| `QuestionTypeSeeder` | 5 question types: binary, single choice, multiple choice, number input, text input |
| `AdminUserSeeder` | One admin account (credentials from `.env`) |
| `UserSeeder` | One regular user account (credentials from `.env`) |
| `QuestionBankSeeder` | 41 published questions across all types (true/false, MCQ, numeric, open-ended) |
| `QuizSeeder` | 8 published quizzes covering CS topics, each with time limits and a mix of question types |

**All seeders run in the correct dependency order via `DatabaseSeeder`.**

### Demo credentials

Credentials are read from your `.env` file. The defaults (from `.env.example`) are:

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `Admin@1234` |
| User | `user@example.com` | `User@1234` |

To use different credentials, update these keys in your `.env` before running the seeders:

```env
# Admin account
ADMIN_FIRST_NAME=Super
ADMIN_LAST_NAME=Admin
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=Admin@1234

# Regular user account
TEST_USER_FIRST_NAME=Test
TEST_USER_LAST_NAME=User
TEST_USER_EMAIL=user@example.com
TEST_USER_PASSWORD=User@1234
```

### Running the seeders

Make sure you have a valid MySQL database created and your `.env` `DB_*` values point to it, then run:

```bash
php artisan db:seed
```

> If you want a clean slate (drop all tables, re-run migrations, and re-seed):
> ```bash
> php artisan migrate:fresh --seed
> ```

## Starting the app

```bash
# 4. Start the development server
composer run dev
```

`composer run dev` starts Laravel, the queue worker, the log viewer, and Vite concurrently. The app is available at [http://localhost:8000](http://localhost:8000).

Log in as **admin** at `/admin` to manage quizzes, grade text responses, and view all submissions. Log in as a **regular user** at `/login` to take quizzes and track attempt history. Guests can take any published quiz without an account.
