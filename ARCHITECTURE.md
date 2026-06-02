# Architecture — Quiz Management Platform

## Overview

This is a Laravel application built with **Livewire + Blade**. There is no separate frontend framework or API layer — Livewire handles interactivity within server-rendered Blade templates over a persistent HTTP connection.

The system is designed around three concerns:

1. **Content management** — admins build a reusable question bank and compose quizzes from it
2. **Quiz-taking** — guests and registered users attempt quizzes; attempts are recorded with full response detail
3. **Evaluation** — objective questions are auto-graded on submission; subjective (text) questions are reviewed manually by admins

---

## Stack Decisions

| Choice | Rationale |
|--------|-----------|
| **Laravel 13 + Livewire 4** | No SPA complexity. Components stay in PHP — no context switching between backend and frontend code. |
| **Flux UI** | Livewire-native component library. Keeps the UI layer consistent without pulling in a JS framework. |
| **MySQL** | Relational data with strict foreign key constraints fits the quiz domain well — attempts reference specific quiz-question snapshots that must never be modified. |
| **Laravel Fortify** | Handles login and registration. Auth is kept intentionally minimal — no password reset, no OAuth. |

---

## Data Model Design

### Tables and their intent

| Table | What it stores |
|-------|----------------|
| `user_types` | Lookup table for user roles (`admin`, `user`). Referenced by `users.user_type_id`. |
| `users` | User accounts — email, hashed password, name, and role FK. Guests have no row here; their attempts are tracked by session. |
| `question_types` | Defines each question kind (`bool`, `single_option`, `multiple_option`, `text`), a `renderer_hint` for the UI, and `evaluation_mode` (`auto` or `manual`). Drives both rendering and grading without hardcoded type switches. |
| `questions` | The shared question bank. Each question has a type, a status lifecycle, and authorship tracking. Not tied to any specific quiz. |
| `options` | Answer choices for `bool`, `single_option`, and `multiple_option` questions. Ordered by `display_order`. |
| `question_answers` | Stores the correct answer for a question as JSON. Kept separate from `questions` so the answer shape can vary freely by type without nullable columns. |
| `quizzes` | Quiz definitions — title, description, time limit, status. Once published, the quiz is locked against edits. |
| `quiz_questions` | Pivot linking a quiz to its questions. Adds `points` (per-question weight in this quiz) and `display_order`. Attempt responses reference this row — not the raw question — so point values and order are frozen per quiz. |
| `quiz_attempts` | One row per attempt by a user (or session, for guests). Tracks completion status, evaluation status, total score, and time taken. |
| `quiz_attempt_responses` | One row per question answered within an attempt. Stores the user's raw `answer_data` as JSON, grading outcome (`is_correct`, `allotted_points`), and manual grading metadata (`graded_by_id`, `comment`). |

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

---

## Status Lifecycle

Both quizzes and questions follow the same state machine:

```
draft ──→ published ──→ inactive
            │
          (locked — no edits allowed)
```

**Why this approach and how it ensures data consistency**

The status lifecycle is the primary mechanism for data integrity in this system. When a question or quiz moves to `published`, every piece of content that future attempt responses will reference — question text, options, correct answers, point values, question order — is frozen at that point in time. No further writes are permitted to those fields.

This matters because `quiz_attempt_responses` are permanently linked to a specific `quiz_question` row. If published content could be edited after attempts were recorded, a past response would silently describe a different question than the one the user actually answered. Scores, percentages, and admin grading decisions would all become untrustworthy. The lock is not a UI restriction — it is a data consistency guarantee enforced at the service layer, returning a 403 for any mutation attempt on a published resource.

The `inactive` state exists to retire content gracefully: a published quiz that should no longer accept new attempts becomes `inactive`, and a question no longer suitable for new quizzes becomes `inactive`. In both cases the existing attempt history is completely untouched and remains queryable.

Soft deletes (`deleted_at`) are used instead of hard deletes on quizzes and questions for the same reason — even a "deleted" quiz must keep its attempt data referentially valid.

**Lifecycle rules:**

| Transition | Allowed? |
|-----------|---------|
| `draft → published` | Yes — locks content |
| `published → inactive` | Yes — stops new attempts/usage |
| `inactive → published` | No |
| `draft` → permanent delete | Yes — no attempts exist, no references; hard delete keeps the DB clean |
| `inactive` → soft delete | Yes — attempt history must remain intact |
| Edit a published resource | No — returns 403 |

---

## Access Model

The system has two types of authenticated users, determined by `users.user_type_id`:

- **Regular users** log in and land on `/dashboard`, where they see their quiz attempt history, scores, and progress across all quizzes they have taken.
- **Admins** log in and land on `/admin`, where they manage the quiz catalogue, question bank, and review or grade submissions.

Guests (unauthenticated) can browse and take any active quiz without logging in. Their attempt is session-tracked rather than tied to a user account, so no history is persisted beyond the browser session.

The admin area is guarded by a dedicated `EnsureUserIsAdmin` middleware on the `/admin/*` route group. There is no permission table or role matrix — admin is a single flag on the user record, kept simple deliberately.

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
