# AI Usage — Quiz Management Platform

## Overview

I used **Claude Code** (Anthropic's Claude Sonnet, accessed via the Claude Code CLI) as an AI pair-programmer throughout this project. This document records what I asked it to do, what it got right, where I corrected it, and which decisions I made myself without relying on it.

I treated Claude Code the way I would treat a capable junior developer: useful for fast execution, worth reviewing carefully, not trusted to make architectural decisions on its own. Every design choice in this project is one I can explain and defend independently of what the AI suggested.

---

## Tool Used

| Tool | Usage |
|------|-------|
| **Claude Code (Claude Sonnet)** | Primary pair-programmer. Used across all phases — data modelling, Livewire component scaffolding, migration writing, seeder generation, and debugging. |

A `CLAUDE.md` file was maintained in the project root throughout development. This served as a standing brief to the AI: it described the domain model, status lifecycle rules, coding conventions, and constraints. Every session began with Claude Code reading this file, so context did not need to be re-explained each time. Keeping this brief accurate and up to date was itself a discipline that forced me to think precisely about what I was building.

---

## Phase 1 — Architecture and Data Modelling

I did not ask Claude Code "design a quiz system". I came to it with a plan and used it to pressure-test the plan.

**What I decided myself first:**

- The question bank should be independent of quizzes (reusable questions, not quiz-embedded ones). This came from thinking about the extensibility constraint: if questions belong to quizzes, retiring a question breaks quizzes; if they live in a bank, the pivot table (`quiz_questions`) handles the relationship cleanly.
- Immutability after publish instead of snapshotting. Snapshotting (copying question content into the attempt at submit time) is the common approach. I chose to lock content at publish time instead, because it gives the same integrity guarantee without duplicating data, and it makes the status lifecycle the single source of truth.
- `evaluation_mode` as a column on `question_types`, not hardcoded in PHP. The assignment constraint was "avoid hardcoded logic for each type in multiple places." Putting `evaluation_mode` and `renderer_hint` on a database row means adding a new question type never requires a PHP `switch` statement update — you insert a row and write one new evaluator class.

**Prompt given to Claude Code:**

> "I have a data model in mind. I want a `question_types` table with columns `question_type`, `renderer_hint`, and `evaluation_mode`. The evaluation_mode is either 'auto' or 'manual'. Questions belong to a type. Correct answers are stored separately in a `question_answers` table as JSON, not as columns on `questions`. Quiz-question relationships go through a `quiz_questions` pivot that also stores points and display_order. Attempts store per-response JSON in `quiz_attempt_responses`. Write the migrations for all of this."

**What Claude Code did well:** Generated clean, correctly ordered migrations with proper foreign key constraints and cascade/restrict rules on the right relationships.

**What I corrected:** The initial migration for `quiz_attempts` made `user_id` non-nullable. I changed it to nullable with `nullOnDelete()` because guests need to be able to create attempts without a user account — a constraint from the quiz-taking flow I had already decided to support.

---

## Phase 2 — Question Types and Answer Data Classes

The requirement is to support five question types. The naive implementation would have a `switch` statement everywhere answer data is read or written. I decided early that each type should have a typed PHP class wrapping its JSON structure.

**Prompt given to Claude Code:**

> "Create a set of answer data classes in `app/Data/Answers/`. Each class wraps the JSON stored in `answer_data` columns. Types needed: BinaryAnswerData (value: bool), SingleChoiceAnswerData (option_id: int), MultipleChoiceAnswerData (option_ids: int[]), NumberAnswerData (value: int|float), TextAnswerData (value: string, model_answer: ?string). Each class needs a toArray() and a static fromArray() method. Also create an AnswerDataFactory that takes a question_type string and raw array and returns the right class instance."

**What Claude Code did well:** Generated all five classes and the factory cleanly, with correct type declarations.

**What I corrected:** Claude's initial `AnswerDataFactory` used a `match` expression on the question type string with hardcoded case values. I asked it to refactor to read the type from the `QuestionType` model's `question_type` field rather than string literals scattered in PHP, so the factory stays in sync with the database values rather than duplicating them.

---

## Phase 3 — Status Lifecycle Enforcement

The publish/inactive rules are critical for data integrity. I did not want them in controllers or Livewire components — I wanted them enforced consistently regardless of entry point.

**Prompt given to Claude Code:**

> "In the admin Livewire components for quizzes and questions, any `publish()`, `markInactive()`, or `edit` action must check the current status before proceeding. A published quiz cannot be edited. An inactive resource cannot be re-published. Return a 403 abort if the transition is invalid. Apply the same pattern to both Quiz and Question."

**What Claude Code did well:** Applied the guard checks consistently across all admin components.

**Correction and honest limitation:** The `ARCHITECTURE.md` describes a `QuizAttemptService` and `GradingService`. These service classes are referenced in the architecture document as the intended home for this logic. In the actual implementation, the lifecycle and grading logic lives inside the Livewire components rather than in extracted service classes. This is a deviation I accepted under time pressure — the behaviour is correct, but the separation of concerns is less clean than the architecture doc implies. A production version would extract this into service classes. I am flagging this explicitly rather than pretending the architecture doc reflects the code exactly.

---

## Phase 4 — Livewire Components (Quiz Builder and Question Creator)

The most complex UI components are the quiz builder (search and select questions, set points, reorder) and the question creator (dynamic form that changes based on question type).

**Prompt for quiz builder:**

> "Create a Livewire component `Admin\Quizzes\Create`. It should have: a search input that filters published questions from the question bank, a filter by question type, a list of selected questions with per-question point input and up/down reorder actions, a computed property for total points, and two save actions: saveAsDraft() and saveAndPublish(). Publish must be rejected if no questions are selected."

**Prompt for question creator:**

> "Create a Livewire component `Admin\Questions\Create`. The form should change dynamically based on the selected question type. For binary: show a true/false toggle for the correct answer. For single_choice: show a list of text option inputs with a radio to mark the correct one. For multiple_choice: same but with checkboxes. For number_input: show a number field for the correct answer. For text_input: show a textarea for a model answer (used by admins during grading). Options should be addable, removable, and reorderable."

**What I corrected:** Claude's initial version of the question creator had a separate Livewire component per question type. I rejected this because it would mean duplicating the save logic and the option management logic. I asked it to implement a single component with conditional rendering blocks driven by the selected type — one `saveAsDraft()` and one `saveAndPublish()` that both work regardless of type.

---

## Phase 5 — Guest Attempt Tracking

The assignment says authentication is not required. I chose to implement authentication anyway (the admin panel and persistent user history both benefit from it), but I also preserved the guest path so unauthenticated users can take quizzes without creating an account.

**Prompt given to Claude Code:**

> "In `Quizzes\Take`, if the user is not authenticated, track the attempt using a session key `guest_attempt_{quiz_id}`. Store the attempt ID in the session on creation. On submit, complete the attempt as normal. The result page should be accessible to guests via the session-stored attempt ID."

**What I decided myself:** Whether to support guests at all. The spec does not require authentication, which means the simplest reading is "just don't add auth." I added auth for the admin and user-history features, but preserved the guest path to keep quiz-taking accessible without registration — a UX decision that made the product more complete.

---

## Phase 6 — Seeders and Sample Data

**Prompt given to Claude Code:**

> "Write seeders that create: two user types (admin, user), five question types with correct renderer_hint and evaluation_mode values, one admin user from env variables, one test user from env variables, a question bank of 40+ questions spread across all five types with realistic CS quiz content, and 8 published quizzes of varying length and time limit that use questions from the bank."

**What Claude Code did well:** Generated realistic CS quiz content covering data structures, algorithms, networking, databases, and general computer science. The variety was good without prompting.

**What I corrected:** The initial seeder set all quizzes to `draft` status. I changed this to `active` because the project needs sample quizzes that are immediately visible to users on first run — a `draft` quiz is invisible to non-admins and makes the demo experience poor.

---

## Phase 7 — Windows Development Environment

This project was developed on both macOS and Windows. Several environment issues surfaced on Windows that required debugging.

**Issue 1 — Laravel Pail crashes on Windows:**  
`php artisan pail` requires the `pcntl` extension, which is Unix-only. When run via `composer run dev` with `--kill-others`, Pail's crash terminates all other processes. I created `scripts/dev.php` — a PHP script that detects `PHP_OS_FAMILY === 'Windows'` at runtime and conditionally omits Pail from the `concurrently` invocation, keeping the command identical on macOS.

**Issue 2 — Norton Antivirus deletes vendor server.php:**  
Norton's Behavioral Protection flagged `vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php` as `IDP.Generic` and deleted it, crashing `php artisan serve`. Investigation of `ServeCommand.php` revealed it checks for `server.php` in the project root first before falling back to the vendor copy. Adding `server.php` to the project root (standard in earlier Laravel versions) fixed this permanently — Norton no longer targets the file, and macOS uses the same file via the same code path.

**Issue 3 — MySQL PDO driver not enabled:**  
`pdo_mysql` and `mysqli` were commented out in `C:\Program Files\php\php-8.5.6\php.ini`. Enabled both extensions; `php artisan migrate` succeeded immediately after.

These were not AI-assisted fixes in the traditional sense — they required reading error output, understanding the framework's fallback logic, and making targeted changes. Claude Code helped identify the `ServeCommand` fallback path by reading the relevant source file.

---

## Design Decisions Made Without AI Input

These are choices I made based on my own judgment before or instead of asking Claude Code:

1. **Adding authentication despite the spec saying it is not required.** The admin panel needs it, and persistent user history is a meaningful feature. I added Fortify and kept the guest path working in parallel.

2. **Going beyond the suggested 5-table structure.** The spec suggests `quizzes`, `questions`, `options`, `attempts`, `answers`. I split `question_types` and `user_types` into their own tables to drive runtime behaviour, and separated `quiz_questions` as a proper pivot to support reusable questions.

3. **Choosing Livewire + Flux over plain Blade + vanilla JS.** The spec allows either. Livewire keeps all state in PHP, eliminates a context switch between backend and frontend, and Flux provides consistent UI components without pulling in a JS framework.

4. **Soft deletes on questions and quizzes.** The spec does not mention this. I added it because hard-deleting a quiz that has attempt history would break foreign key references. Soft deletes keep history intact while hiding the resource from users.

5. **Backing all status fields with PHP enums.** `QuizStatusEnum`, `QuestionStatusEnum`, etc. This was my choice — it makes status comparisons type-safe and eliminates a class of bugs where a typo in a string comparison silently fails.

---

## Where I Disagree With or Rejected AI Output

- **Rejected a repository layer.** Claude Code initially suggested wrapping models in repository classes. I rejected this because the project does not have the complexity that warrants the indirection, and CLAUDE.md explicitly states "no repositories unless complexity demands it."

- **Rejected client-side time enforcement alone.** Claude Code implemented the quiz timer purely in JavaScript. I kept this but also added server-side validation on submission — if `time_taken_in_sec` exceeds `allotted_time_in_sec`, the submission is still accepted (to avoid data loss) but flagged. Pure client-side enforcement is trivially bypassed.

- **Rejected eager loading on every query.** Several generated components had `->with(['questions.options', 'questions.answer', ...])` on every page load. I trimmed this to load only what each specific view needs, avoiding over-fetching on list pages.

---

## What I Can Explain Without the AI

If asked in an interview, I can explain from first principles:

- Why `evaluation_mode` lives on `question_types` rather than in a PHP `match` block
- Why `quiz_attempt_responses` references `quiz_question_id` rather than `question_id` directly
- Why `user_id` on `quiz_attempts` is nullable
- How the status lifecycle prevents score corruption in historic attempts
- Why correct answers live in `question_answers` rather than as a column on `questions`
- The trade-off between snapshotting and immutability, and why I chose immutability
- How adding a new question type works end-to-end without changing existing code

---

## Reflection

Claude Code significantly accelerated implementation — particularly migrations, seeder content, and repetitive Livewire scaffolding. It is good at generating structurally correct code when given precise requirements.

It is not good at making architectural decisions: it will generate whatever structure seems locally reasonable without considering how pieces fit together at scale. Every significant structural decision in this project — the question bank pattern, immutability, `evaluation_mode` as a database column, typed answer data classes — was something I specified, not something Claude Code proposed.

The discipline of writing CLAUDE.md forced me to think carefully about what I was building before writing any code. In retrospect, that upfront precision was as valuable as the code generation itself — it kept the AI producing consistent output and gave me a document I could check generated code against.
