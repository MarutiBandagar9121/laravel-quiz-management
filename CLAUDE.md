# Quiz Management Platform — CLAUDE.md

## Project Overview

A fully server-rendered quiz management platform built with Laravel + Livewire. Two user roles exist: **admins** who create and grade quizzes, and **regular users** who take quizzes, earn points, and track their history.

## Tech Stack

- **PHP** 8.3+
- **Laravel** 13.x (Livewire starter kit)
- **Livewire** 4.x + **Flux UI** 2.x — all UI is Livewire components, no SPA/API layer
- **Laravel Fortify** — authentication (registration, login, password reset, email verification)
- **MySQL** — primary database (`quiz_management` DB, local)
- **Vite** — asset bundling
- **Laravel Pint** — code style (PSR-12)
- **PHPUnit** — testing

## Local Development

```bash
composer run dev        # starts server, queue, pail log viewer, and vite concurrently
composer run lint       # fix code style with pint
composer run test       # clear config + pint check + phpunit
php artisan migrate     # run migrations
php artisan tinker      # REPL
```

App runs at `http://localhost:8000`.

## Database

- **Connection**: MySQL on `127.0.0.1:3306`
- **Database**: `quiz_management`
- **Credentials**: stored in `.env` (DB_USERNAME / DB_PASSWORD)
- Sessions, cache, and queues all use the `database` driver

## Authentication & Access Model

### Three access tiers

| Tier | Auth | Entry point | Capabilities |
|------|------|-------------|-------------|
| **Guest** | None | `/` (home) | Browse and attempt any active quiz; results shown at end of session only; no history persisted |
| **Registered user** | Email + password | `/login`, `/register` | Same as guest + dashboard showing all past attempts, scores, per-quiz history |
| **Admin** | Email + password + admin flag | `/admin` | Full quiz & question management, view all submissions, manual grading of text answers |

### Route groups

```
/                   → public (guests + authenticated users)
/dashboard          → auth middleware only
/admin/*            → auth + admin middleware only
```

Auth is provided by **Laravel Fortify** (email + password, no OAuth). Authentication is optional for quiz-taking — guests use session-based tracking scoped to that browser session. Registered users get persistent attempt history.

Roles are stored in `user_types` table; `users.user_type_id` is the FK. Admin flag is checked via dedicated middleware on the `/admin` prefix — never inline role checks in views or components.

## Domain Model (from ER diagram)

### Core entities

**`question_types`** — defines the kind of question and how it is evaluated
- `value`: e.g. `bool`, `single_option`, `multiple_option`, `text`
- `renderer_hint`: UI hint for the frontend component to render
- `evaluation_mode`: `auto` or `manual` — drives grading logic

**`questions`** — reusable question bank (not tied to a specific quiz)
- Status lifecycle: `draft → published → inactive` (soft-deleted via `deleted_at`)
- Once **published**, a question is locked — content, options, and correct answer cannot be changed
- **Inactive** questions cannot be added to new quizzes but remain referenced by existing `quiz_questions` rows (data integrity preserved)
- `created_by_id` / `updated_by_id` — track authoring admin

**`options`** — answer choices for `single_option` / `multiple_option` / `bool` questions
- `display_order` controls rendering order

**`question_answers`** — stores the correct answer(s) as JSON, keyed to a question

**`quizzes`**
- Status lifecycle: `draft → published → inactive`
- Once **published**, a quiz is locked — questions, points, and order cannot be changed; it accepts attempts immediately
- **Inactive** means the quiz no longer accepts new attempts; all past attempts and responses are preserved
- Soft-deleted via `deleted_at` — a soft-deleted quiz is hidden from users but all attempt data remains intact
- `allotted_time_in_sec` — optional time limit, enforced on the client and validated on submission

**`quiz_questions`** — pivot linking quizzes to questions
- `points` — per-question point value
- `display_order` — question order within the quiz

**`quiz_attempts`** — one row per user attempt
- `attempt_number` — supports multiple attempts per quiz per user
- `completion_status` — tracks whether the attempt was finished
- `evaluation_status` — `pending`, `auto_graded`, `fully_graded`
- `total_points_awarded` — aggregate score
- `time_taken_in_sec`

**`quiz_attempt_responses`** — one row per question answered in an attempt
- `answer_data` (JSON) — user's raw answer
- `is_correct` (bool) — set during auto-grading
- `evaluation_status`, `allotted_points`, `comment`, `graded_by_id`, `graded_at` — manual grading fields

### Key relationships

```
question_types  ←── questions ───→ options
                        │
                   question_answers (correct answer JSON)
                        │
quiz ────────── quiz_questions ──── questions
  │
quiz_attempts ─────── quiz_attempt_responses ──── quiz_questions
  │
users (user_type_id → user_types)
```

## Question Types & Grading

| Type | `evaluation_mode` | Grading |
|------|-------------------|---------|
| `bool` | `auto` | Compare answer JSON to `question_answers` |
| `single_option` | `auto` | Compare selected option id |
| `multiple_option` | `auto` | Compare selected option ids (order-independent) |
| `text` | `manual` | Admin reviews `quiz_attempt_responses`, sets `allotted_points` + `comment` |

Auto-grading runs when an attempt is submitted. Manual grading is done via an admin UI that updates individual `quiz_attempt_responses` rows and, when all responses are graded, recalculates `quiz_attempts.total_points_awarded`.

## Key Conventions

- All views are Blade templates; interactive UI is Livewire components (no Vue/React)
- Livewire components live in `app/Livewire/`
- Standard Laravel MVC: models in `app/Models/`, no repositories unless complexity demands it
- Use Eloquent; avoid raw SQL unless strictly necessary
- Soft deletes (`deleted_at`) on `questions` and `quizzes` — never hard-delete these
- `answer_data` fields are JSON columns; cast to `array` in Eloquent models
- Role checks via middleware or Livewire `#[Middleware]` attribute — do not inline role logic in views
- Run `composer run lint` before committing

## Status Lifecycle Rules (enforced at service/model layer)

### Quiz

```
draft → published   allowed (locks the quiz)
published → inactive  allowed (stops new attempts)
inactive → published  NOT allowed
any → deleted (soft)  allowed only if quiz is draft or inactive
editing a published quiz  NOT allowed — return 403
```

### Question (question bank)

```
draft → published   allowed (locks question + options + answer)
published → inactive  allowed (excluded from quiz builder)
inactive → published  NOT allowed
editing a published/inactive question  NOT allowed — return 403
```

### Why immutability matters
Published questions and quizzes are referenced by `quiz_attempt_responses`. Allowing edits would retroactively change the meaning of submitted answers, breaking score integrity. Enforce these rules in a dedicated service class (e.g. `QuizPublishingService`, `QuestionPublishingService`), not in controllers or Livewire components.

## Auth

Fortify handles all auth flows. The dashboard (`/dashboard`) is behind `auth` middleware. The `/admin/*` prefix is behind a custom `EnsureUserIsAdmin` middleware. Quiz-taking routes are fully public — no auth middleware.
