# Quiz Management Platform — CLAUDE.md

## Project Overview

A fully server-rendered quiz management platform built with Laravel + Livewire. Three access tiers exist: **guests** who take quizzes without an account (session-only history), **registered users** who take quizzes and track history, and **admins** who create questions/quizzes and manually grade text responses.

## Tech Stack

- **PHP** 8.3+
- **Laravel** 13.x (Livewire starter kit)
- **Livewire** 4.x + **Flux UI** 2.x — all UI is Livewire components, no SPA/API layer
- **Laravel Fortify** — authentication (registration, login, password reset, email verification, passkeys, 2FA)
- **MySQL** — primary database (`quiz_management` DB, local)
- **Vite** — asset bundling
- **Laravel Pint** — code style (PSR-12)
- **PHPUnit** — testing

## Local Development

```bash
composer run dev        # starts server, queue, and vite (+ pail on macOS/Linux only)
composer run lint       # fix code style with pint
composer run test       # clear config + pint check + phpunit
php artisan migrate     # run migrations
php artisan db:seed     # seed question bank and 8 sample quizzes
php artisan tinker      # REPL
```

App runs at `http://localhost:8000`.

**Windows note:** `php artisan pail` requires the `pcntl` extension which is Unix-only. The dev script (`scripts/dev.php`) detects Windows and skips pail automatically — other processes (server, queue, Vite) are unaffected. To tail logs manually on Windows: `Get-Content storage/logs/laravel.log -Wait -Tail 50`.

**Windows note:** A `server.php` file lives at the project root. `php artisan serve` uses it instead of the vendor fallback, which avoids a path that Windows Defender / Norton flags as suspicious.

## Database

- **Connection**: MySQL on `127.0.0.1:3306`
- **Database**: `quiz_management`
- **Credentials**: stored in `.env` (DB_USERNAME / DB_PASSWORD)
- Sessions, cache, and queues all use the `database` driver
- **Seeded admin**: `ADMIN_EMAIL` / `ADMIN_PASSWORD` from `.env`
- **Seeded test user**: `TEST_USER_EMAIL` / `TEST_USER_PASSWORD` from `.env`

## Authentication & Access Model

### Three access tiers

| Tier | Auth | Entry point | Capabilities |
|------|------|-------------|-------------|
| **Guest** | None | `/` (home) | Browse and attempt any active quiz; results shown at end of session only; attempt tracked via `guest_attempt_{quiz_id}` session key |
| **Registered user** | Email + password | `/login`, `/register` | Same as guest + persistent dashboard showing all past attempts and scores |
| **Admin** | Email + password + admin flag | `/admin` | Full quiz & question management, view all submissions, manual grading of text answers, user management |

### Route groups

```
/                     → public (guests + authenticated users)
  GET /               → home (welcome)
  GET /quizzes        → Quizzes\Index
  GET /quizzes/{quiz}/take → Quizzes\Take
  GET /attempts/{attempt}  → Quizzes\Result

/dashboard            → auth + verified middleware → UserDashboard

/admin/*              → auth + admin middleware (EnsureUserIsAdmin)
  GET /admin/dashboard
  GET /admin/quizzes          (index, create, show, edit)
  GET /admin/questions        (index, create, show, edit)
  GET /admin/submissions      (index, review)
  GET /admin/users            (index)

/settings/*           → auth middleware
  GET /settings/security
```

Auth is provided by **Laravel Fortify** (email + password, passkeys, 2FA). Authentication is optional for quiz-taking — guests use session-based tracking. Registered users get persistent attempt history.

Roles are stored in the `user_types` table; `users.user_type_id` is the FK. Admin access is checked via the `EnsureUserIsAdmin` middleware registered as `admin` — never inline role checks in views or components. The `User::isAdmin()` method encapsulates the check.

## Domain Model

### Core entities

**`user_types`** — two seeded rows: `admin`, `user`
- `user_type`, `role_description`

**`question_types`** — five seeded rows, defines how a question is rendered and graded
- `question_type`: `binary`, `single_choice`, `multiple_choice`, `number_input`, `text_input`
- `renderer_hint`: `toggle`, `radio`, `checkbox`, `number`, `textarea`
- `evaluation_mode`: `auto` (binary/single_choice/multiple_choice/number_input) or `manual` (text_input)

**`questions`** — reusable question bank (not tied to a specific quiz)
- Status lifecycle: `Draft → Active → Inactive` (soft-deleted via `deleted_at`)
- Once **Active**, a question is locked — content, options, and correct answer cannot be changed
- **Inactive** questions cannot be added to new quizzes but remain referenced by existing `quiz_questions` rows
- `created_by_id` / `updated_by_id` — track authoring admin

**`options`** — answer choices for `single_choice`, `multiple_choice`, and `binary` questions
- `display_order` controls rendering order

**`question_answers`** — stores the correct answer(s) as JSON, keyed to a question
- `answer_data` (JSON, cast to array)

**`quizzes`**
- Status lifecycle: `Draft → Active → Inactive`
- Once **Active**, a quiz is locked — questions, points, and order cannot be changed; it accepts attempts immediately
- **Inactive** means the quiz no longer accepts new attempts; all past attempts and responses are preserved
- Soft-deleted via `deleted_at` — a soft-deleted quiz is hidden but all attempt data remains
- `allotted_time_in_sec` — optional time limit

**`quiz_questions`** — pivot linking quizzes to questions
- `points` — per-question point value
- `display_order` — question order within the quiz

**`quiz_attempts`** — one row per user attempt
- `user_id` (nullable — NULL for guest attempts)
- `attempt_number` — supports multiple attempts per quiz per user
- `completion_status` — `InProgress`, `Completed`, `Abandoned`
- `evaluation_status` — `Pending`, `AutoGraded`, `FullyGraded`
- `total_points_awarded`, `time_taken_in_sec`, `started_at`, `completed_at`

**`quiz_attempt_responses`** — one row per question answered in an attempt
- `answer_data` (JSON, cast to array) — user's raw answer
- `is_correct` (nullable bool) — set during auto-grading
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

| Type | `evaluation_mode` | Renderer | Grading |
|------|-------------------|----------|---------|
| `binary` | `auto` | toggle | Compare bool value to `question_answers` |
| `single_choice` | `auto` | radio | Compare selected option id |
| `multiple_choice` | `auto` | checkbox | Compare selected option ids (order-independent) |
| `number_input` | `auto` | number | Compare numeric value |
| `text_input` | `manual` | textarea | Admin reviews response, sets `allotted_points` + `comment` |

Auto-grading runs when an attempt is submitted. Manual grading is done via `Admin\Submissions\Review` — when all text responses are graded, `quiz_attempts.total_points_awarded` is recalculated.

## Enums

All in `app/Enums/` (backed string enums):

| Enum | Values |
|------|--------|
| `QuizStatusEnum` | `Draft`, `Active`, `Inactive` |
| `QuestionStatusEnum` | `Draft`, `Active`, `Inactive` |
| `QuizAttemptCompletionStatus` | `InProgress`, `Completed`, `Abandoned` |
| `QuizAttemptEvaluationStatus` | `Pending`, `AutoGraded`, `FullyGraded` |
| `UserStatusEnum` | `Active`, `Inactive` |

## Answer Data Classes

All in `app/Data/Answers/` — type-safe wrappers around `answer_data` JSON with `toArray()` and `fromArray()` methods:

- `BinaryAnswerData` — `value: bool`
- `SingleChoiceAnswerData` — `option_id: int`
- `MultipleChoiceAnswerData` — `option_ids: int[]`
- `NumberAnswerData` — `value: int|float`
- `TextAnswerData` — `value: string`, `model_answer: ?string`
- `AnswerDataFactory` — deserializes raw `answer_data` JSON back to the correct class

Always use these classes when reading or writing `answer_data` — never write raw arrays.

## Livewire Component Map

All components live in `app/Livewire/` with corresponding views in `resources/views/livewire/`.

**Public / User**

| Component | Route | Purpose |
|-----------|-------|---------|
| `Quizzes\Index` | `/quizzes` | Browse active quizzes, search, paginated (12/page) |
| `Quizzes\Take` | `/quizzes/{quiz}/take` | Core quiz-taking UI; handles guest + auth, auto-grading on submit |
| `Quizzes\Result` | `/attempts/{attempt}` | Show completed attempt results and correct answers |
| `UserDashboard` | `/dashboard` | Attempt history, stats (total, completed, points earned) |

**Admin — Quizzes**

| Component | Route | Purpose |
|-----------|-------|---------|
| `Admin\Dashboard` | `/admin/dashboard` | Stats overview (quiz/question/submission counts) |
| `Admin\Quizzes\Index` | `/admin/quizzes` | List quizzes, filter by status, publish/activate/deactivate/delete |
| `Admin\Quizzes\Create` | `/admin/quizzes/create` | Build quiz: search questions, set points, reorder, save draft or publish |
| `Admin\Quizzes\Show` | `/admin/quizzes/{quiz}` | View quiz details and status actions |
| `Admin\Quizzes\Edit` | `/admin/quizzes/{quiz}/edit` | Edit draft quiz only (same capabilities as Create) |

**Admin — Questions**

| Component | Route | Purpose |
|-----------|-------|---------|
| `Admin\Questions\Index` | `/admin/questions` | List questions, search, filter by type/status |
| `Admin\Questions\Create` | `/admin/questions/create` | Create question with dynamic form per type; add/reorder options; save draft or publish |
| `Admin\Questions\Show` | `/admin/questions/{question}` | View question, options, correct answer |
| `Admin\Questions\Edit` | `/admin/questions/{question}/edit` | Edit draft question only; hard-deletes draft questions |

**Admin — Submissions & Users**

| Component | Route | Purpose |
|-----------|-------|---------|
| `Admin\Submissions\Index` | `/admin/submissions` | Tabs: pending manual review vs fully graded |
| `Admin\Submissions\Review` | `/admin/submissions/{attempt}` | Grade text responses one by one; marks attempt FullyGraded when done |
| `Admin\Users\Index` | `/admin/users` | List users, search, activate/deactivate |

## Layouts

Three layout families in `resources/views/layouts/`:

- `public.blade.php` — unauthenticated quiz-taking pages
- `app.blade.php` — authenticated user pages (dashboard, settings)
- `admin.blade.php` — admin panel (with sidebar navigation)

## Key Conventions

- All views are Blade templates; interactive UI is Livewire components (no Vue/React)
- Livewire components live in `app/Livewire/`; views mirror the same directory structure in `resources/views/livewire/`
- Standard Laravel MVC: models in `app/Models/`, no service classes — business logic lives in Livewire components
- Use Eloquent; avoid raw SQL unless strictly necessary
- Soft deletes (`deleted_at`) on `questions` and `quizzes` — never hard-delete these
- `answer_data` fields are JSON columns cast to `array`; always use the `app/Data/Answers/` classes to read/write them
- Enum casts on all status columns — never compare status as a raw string, use the enum cases
- Role checks via `EnsureUserIsAdmin` middleware or `#[Middleware]` attribute — do not inline role logic in views or components
- Run `composer run lint` before committing

## Status Lifecycle Rules

### Quiz

```
Draft → Active      allowed (locks the quiz; sets published_at)
Active → Inactive   allowed (stops new attempts)
Inactive → Active   NOT allowed
any → soft-delete   allowed only if Draft or Inactive
editing an Active quiz  NOT allowed — return 403
```

### Question (question bank)

```
Draft → Active      allowed (locks question + options + answer)
Active → Inactive   allowed (excluded from quiz builder)
Inactive → Active   NOT allowed
editing an Active/Inactive question  NOT allowed — return 403
```

### Why immutability matters
Active questions and quizzes are referenced by `quiz_attempt_responses`. Allowing edits would retroactively change the meaning of submitted answers, breaking score integrity. Enforce these rules inside Livewire action methods — not in views.
