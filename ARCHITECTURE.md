# Architecture — Quiz Management Platform

## Overview

This is a fully server-rendered Laravel application built on the **Livewire + Blade** stack. There is no separate frontend framework or API layer — all rendering happens on the server, and Livewire handles interactivity by diffing DOM over a persistent HTTP connection.

The system is designed around three concerns:

1. **Content management** — admins build a reusable question bank and compose quizzes from it
2. **Quiz-taking** — guests and registered users attempt quizzes; attempts are recorded with full response detail
3. **Evaluation** — objective questions are auto-graded on submission; subjective (text) questions are reviewed manually by admins

---

## Stack Decisions

| Choice | Rationale |
|--------|-----------|
| **Laravel 13 + Livewire 4** | Server-rendered, no SPA complexity. Components stay in PHP — no context switching between backend and frontend code. |
| **Flux UI** | Livewire-native component library. Keeps the UI layer consistent without pulling in a JS framework. |
| **MySQL** | Relational data with strict foreign key constraints fits the quiz domain well — attempts reference specific quiz-question snapshots that must never be modified. |
| **Laravel Fortify** | Handles auth boilerplate (login, register, password reset) without tying the app to a full starter kit opinionated about UI. |
| **Database-backed sessions, cache, and queues** | Keeps the dev setup simple — no Redis required locally. Can be swapped to Redis in production with a single env change. |

---

## Application Layers

```
routes/
  web.php          → public quiz routes (guest + auth)
  settings.php     → auth routes (Fortify)
  admin.php        → /admin/* (auth + admin middleware)

app/
  Http/Middleware/ → EnsureUserIsAdmin, etc.
  Livewire/        → all interactive UI components
    Admin/         → quiz builder, question bank, grading UI
    Quiz/          → quiz listing, quiz attempt flow
    Dashboard/     → user attempt history
  Models/          → Eloquent models, casts, relationships
  Services/        → business logic (publishing, grading, scoring)
  Actions/         → single-purpose action classes (Fortify hooks)

resources/views/
  livewire/        → Blade templates for Livewire components
  layouts/         → app shell, admin shell
```

Business logic lives in **service classes**, not in Livewire components or controllers. Components are responsible for UI state and dispatching to services. This keeps components testable in isolation and keeps the rules in one place.

---

## Data Model Design

### The question bank is independent of quizzes

Questions are not created inside a quiz — they live in a shared bank. A quiz references questions via the `quiz_questions` pivot, which also stores the point value and display order for that specific quiz context.

This means:
- The same question can appear in multiple quizzes with different point values
- Questions can be authored and reviewed before being attached to any quiz
- Retiring a question does not affect any quiz it was already used in

### Snapshots via immutability, not duplication

A common approach in exam systems is to snapshot (copy) question content into the attempt at submission time. This project takes a different approach: **content is locked at publish time** and never changes thereafter.

- A question moves `draft → published` — from that point its text, options, and correct answer are frozen
- A quiz moves `draft → published` — from that point its question list, point values, and order are frozen
- All `quiz_attempt_responses` reference `quiz_question_id`, which is itself a frozen record

This gives the same integrity guarantee as snapshotting, without duplicating data. Historic attempts always resolve to exactly the question and answer key that existed when the user took the quiz.

### Answer storage as JSON

Both correct answers (`question_answers.answer_data`) and user responses (`quiz_attempt_responses.answer_data`) are stored as JSON. This is intentional:

- Different question types have structurally different answers (a boolean, a single ID, a set of IDs, a free-text string)
- A typed JSON column avoids needing separate answer tables per type
- The evaluation logic reads this JSON through a strategy — each question type knows how to interpret its own answer shape

---

## Extensibility: Adding a New Question Type

The system is designed so that adding a new question type — say, `ranking` or `number_input` — requires no changes to the core quiz or attempt flow. The steps are:

1. **Insert a row** into `question_types` with the appropriate `value`, `renderer_hint`, and `evaluation_mode`
2. **Write an evaluator** — a small class that implements a common `Evaluatable` interface, receiving `answer_data` (correct) and `answer_data` (user) and returning a boolean + points
3. **Register the evaluator** — map the new `question_type.value` to the evaluator class in a service provider or config file
4. **Add a Livewire component** for rendering the question (guided by `renderer_hint`) and one for the answer input
5. If `evaluation_mode` is `manual`, the admin grading UI already handles it generically — no changes needed there

No existing code changes. No switch statements to update. The `question_types` table drives runtime behaviour.

### Evaluation strategy (simplified)

```php
// GradingService.php
public function evaluate(QuizAttemptResponse $response): void
{
    $type = $response->quizQuestion->question->questionType;
    $evaluator = $this->resolveEvaluator($type->value); // registry lookup
    $result = $evaluator->evaluate(
        correct: $response->quizQuestion->question->answer->answer_data,
        given:   $response->answer_data,
    );
    $response->update([
        'is_correct'      => $result->isCorrect,
        'allotted_points' => $result->points,
    ]);
}
```

The `resolveEvaluator()` method is the only place a question type name appears — a single registry, not scattered conditionals.

---

## Status Lifecycle

Both quizzes and questions follow the same state machine:

```
draft ──→ published ──→ inactive
            │
          (locked — no edits allowed)
```

**Why not allow editing after publish?**

`quiz_attempt_responses` reference specific question content via foreign keys to `quiz_questions`. If a published question could be edited, a past attempt's response would silently point to different content than what the user actually answered. Scores would become meaningless. Immutability is the only correct guarantee here — it is enforced in the service layer and returns a 403 for any mutation attempt on a published resource.

Soft deletes (`deleted_at`) are used instead of hard deletes on quizzes and questions so that all attempt history remains referentially valid even after a quiz is retired.

---

## Access Control

Three tiers, enforced at the route/middleware layer:

```
Public (no auth)
  GET /                     → quiz listing
  GET /quizzes/{quiz}       → quiz detail
  POST /quizzes/{quiz}/attempt → start/submit attempt (session-tracked)

Authenticated users (auth middleware)
  GET /dashboard            → attempt history and scores

Admin (auth + EnsureUserIsAdmin middleware)
  /admin/quizzes/*          → quiz CRUD, publish, deactivate
  /admin/questions/*        → question bank management
  /admin/attempts/*         → view all attempts, manual grading
```

Guest users can take any active quiz. Their attempt is recorded against a session identifier. Registered users get the same attempt recorded against their `user_id`, enabling the dashboard history view.

The admin area is a separate route group with its own layout and middleware. There is no role dropdown or permission table — admin is a boolean flag on the user, checked once at middleware. This is intentional simplicity for the current scope.

---

## Grading Flow

```
User submits attempt
        │
        ▼
QuizAttemptService::submit()
        │
        ├─ For each response:
        │     QuestionType.evaluation_mode == 'auto'?
        │        YES → GradingService::evaluate() → marks is_correct + allotted_points
        │        NO  → leave evaluation_status = 'pending' (awaits admin)
        │
        ├─ Sum allotted_points where graded → quiz_attempts.total_points_awarded
        │
        └─ Set quiz_attempts.evaluation_status:
              all auto   → 'auto_graded'
              any manual → 'pending'
              all done   → 'fully_graded'
```

When an admin grades a pending text response, a separate `GradingService::manualGrade()` call updates that response row. A listener then recalculates `total_points_awarded` on the parent attempt. When all responses on an attempt reach a graded state, `evaluation_status` is promoted to `fully_graded`.

---

## What This Design Prioritises

- **Data integrity over convenience** — immutability after publish means the system never serves ambiguous results
- **Extensibility without modification** — new question types slot in without touching existing evaluation or rendering code
- **Separation of concerns** — Livewire components handle UI state; services own business rules; models own data shape
- **Simplicity where warranted** — no event sourcing, no CQRS, no microservices; this is a Laravel monolith and that is the right tool for this scope
