# Taskboard Laravel Backend

This repository contains the Laravel API for Taskboard, a project and task management app. It authenticates users, applies role and assignment rules, stores projects and tasks, and serves JSON data to the separate React frontend. It also integrates with the Django overdue-task service.

## What the backend does

- Authenticates users with Laravel Sanctum bearer tokens.
- Returns projects and tasks based on the signed-in user's role and task assignments.
- Lets administrators create, edit and delete projects and tasks, and assign tasks to users.
- Lets administrators and task assignees request task status changes, subject to the overdue service's transition rules.
- Synchronizes overdue task status with the Django service when project/task data is read.
- Provides API validation, resource responses, and policy checks for protected operations.

## How a request flows

API routes are declared in `routes/api.php`. Controllers validate inputs through form-request classes, call application services, and return consistent JSON responses. Services query and update the database with Eloquent models. Sanctum protects authenticated routes; project/task policies and role checks determine which records and actions a user can access.

The React app uses the API at `http://127.0.0.1:8000/api` by default. The API returns JSON and expects bearer-token authorization for protected endpoints.

## API endpoints

The following paths are relative to `/api`:

| Method | Path | Access | Purpose |
| --- | --- | --- | --- |
| `POST` | `/register` | Public | Register an account |
| `POST` | `/login` | Public; throttled to 10 attempts per minute | Sign in and receive a Sanctum token |
| `POST` | `/logout` | Authenticated | Revoke the current token |
| `GET` | `/me` | Authenticated | Return the current user |
| `GET` | `/users` | Administrator | List users available for task assignment |
| `GET` | `/projects` | Authenticated | List visible projects |
| `POST` | `/projects` | Administrator | Create a project |
| `GET` | `/projects/{project}` | Authenticated and authorized | Get a project and its visible tasks |
| `PUT`/`PATCH` | `/projects/{project}` | Administrator | Edit a project's name or description |
| `DELETE` | `/projects/{project}` | Administrator | Delete a project and its tasks |
| `GET` | `/tasks` | Authenticated | List visible tasks |
| `POST` | `/tasks` | Administrator | Create a task |
| `PUT`/`PATCH` | `/tasks/{task}` | Administrator | Edit a task's title, description, assignee, priority, due date or project |
| `DELETE` | `/tasks/{task}` | Administrator | Delete a task |
| `PATCH` | `/tasks/{task}/status` | Administrator or assignee | Request a task status change |

Administrators can see all projects and tasks. Regular users see projects/tasks associated with their assignments. Authorization is enforced on the API, not only in the frontend.

## Database

### Default database and setup

The default connection is MySQL (database `task_manager`), the same database the Django overdue service reads. Migrations and seeders are the portable, tracked source of the schema and sample records.

For a fresh local database:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS task_manager;"
php artisan migrate --seed
```

`migrate` creates or updates the schema. `--seed` runs `DatabaseSeeder` to add the demo accounts, sample projects, and sample tasks. Do not repeatedly seed an already seeded database: the seeder creates those rows and is intended for a fresh development database.

To completely rebuild a development database and re-seed it:

```bash
php artisan migrate:fresh --seed
```

**Warning:** `migrate:fresh` drops every table in the configured database. Use it only when you are certain the selected database contains no data you need.

Set your local MySQL credentials in `.env`. Do not commit production connection details.

### Main tables

| Table | Contents and relationships |
| --- | --- |
| `users` | Name, unique email, hashed password, and role (`admin` or `user`). |
| `projects` | Project name and optional description; `created_by` references the creating user and cascades on user deletion. |
| `tasks` | Task title/description, status, priority, due date, project, and optional assignee. Deleting a project cascades to its tasks; deleting an assignee leaves the task unassigned. An index supports status/due-date queries. |
| `personal_access_tokens` | Sanctum API tokens. |
| `password_reset_tokens`, `sessions` | Laravel password-reset and session storage. |
| `cache`, `cache_locks` | Database-backed cache and cache locks. |
| `jobs`, `job_batches`, `failed_jobs` | Database-backed queues and failed-job records. |

Task statuses are `TODO`, `IN_PROGRESS`, `DONE`, and `OVERDUE`; priorities are `LOW`, `MEDIUM`, and `HIGH`. `OVERDUE` is assigned by the Django overdue service rather than selected as a user status transition.

### Demo accounts

The seeder creates these demo users, both locally and on the live deployment. Passwords are hashed by the User model before being stored.

| Role | Email | Password |
| --- | --- | --- |
| **Admin** | [admin@example.com](mailto:admin@example.com) | `Admin@123` |
| User | [user@example.com](mailto:user@example.com) | `User@123` |
| User | [aarav@example.com](mailto:aarav@example.com) | `Aarav@123` |
| User | [meera@example.com](mailto:meera@example.com) | `Meera@123` |
| User | [rohan@example.com](mailto:rohan@example.com) | `Rohan@123` |

These are development/test credentials only. Do not use them in production or reuse their passwords for real accounts.

### Seeded demo data

The seeder creates 6 projects and 26 tasks spread across the accounts above, so each login shows something different.

| Account | Role | Assigned tasks | Overdue | Projects visible |
| --- | --- | --- | --- | --- |
| admin@example.com | Admin | all 26 | all overdue tasks | all 6 |
| user@example.com | User | 6 | 1 | 4 (no Internal Tools) |
| aarav@example.com | User | 6 | 3 | 4 (no Internal Tools) |
| meera@example.com | User | 6 | 1 | 5 (all except Archive Cleanup) |
| rohan@example.com | User | 7 | 1 | 4 (no Customer Support Portal) |

Things worth trying:

- A regular user only sees projects that contain a task assigned to them, and only their own tasks inside each project. Only the admin sees the empty "Archive Cleanup" project and the unassigned task "Choose hosting plan".
- Overdue tasks (past due and not `DONE`) are locked for regular users: they cannot move them to `IN_PROGRESS` or close them. Only the admin can close them. Example: `user@example.com` has "Write API docs"; `aarav@example.com` has three overdue tasks.
- Meera's "Launch email draft" is due today, so it is **not** overdue.
- Rohan's "Set up CI pipeline" is already stored as `OVERDUE`; the others become `OVERDUE` on the next sync.

## Django overdue-service integration

The Laravel API calls a separate Django service to synchronize overdue task statuses and validate requested transitions. The default service URL is `http://127.0.0.1:8001`; configure it in the local `.env`:

```env
DJANGO_URL=http://127.0.0.1:8001
DJANGO_SERVICE_KEY=your-local-service-key
DJANGO_TIMEOUT=10
```

The shared key must match the Django service configuration and should remain private. Laravel sends it in the `X-Service-Key` header. A sync failure is logged without blocking project/task reads. A status-transition validation failure returns an error instead of accepting an unverified change.

## Run locally

### Requirements

- PHP 8.3 or later and Composer
- MySQL 8 (shared with the Django overdue service)
- Node.js and npm for compiling the optional Vite assets
- The Django overdue service for overdue synchronization and status validation

### Install and start

From this directory:

```bash
composer install
cp .env.example .env
php artisan key:generate
mysql -u root -e "CREATE DATABASE IF NOT EXISTS task_manager;"
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Run the Django service separately on the configured `DJANGO_URL`. If using the React frontend, configure its API URL to point to this Laravel API and allow the frontend's origin in the API's CORS configuration.

### Tests and useful commands

```bash
composer test
php artisan route:list --path=api
php artisan migrate:status
```

The Vite assets can be installed and built separately if needed:

```bash
npm ci
npm run build
```

## Repository contents and ignored files

The repository should contain application source, API routes, configuration templates, Composer manifests/lockfile, migrations, factories, seeders, and tests. The local `.gitignore` excludes `.env` and `.env.*` variants except `.env.example`, dependencies (`vendor/`, `node_modules/`), generated frontend assets (`public/build/`), local SQLite database files (through `database/.gitignore`), and the unrelated zero-byte root artifacts `@php` and `yes`. `.env.example` is a template and is intended to be tracked.

Keep actual credentials, API keys, database contents, generated dependencies, and build outputs out of Git. `.env.example` is a configuration template; put machine-specific values only in `.env`. Before publishing, inspect the staged file list and confirm it contains only intended source and documentation.

## Project structure

```text
app/
  Enums/          Task status and priority values
  Http/           API controllers, requests, resources, and responses
  Models/         User, project, and task Eloquent models
  Policies/       Record-level authorization
  Services/       Authentication, project/task logic, Django client
database/
  migrations/     Database schema history
  seeders/        Demo accounts and sample project/task data
routes/api.php    JSON API routes
tests/            Automated tests
```

## Technology

- PHP 8.3+
- Laravel 13
- Laravel Sanctum
- MySQL (shared with the Django overdue service)
- PHPUnit for tests

## Live deployment

| Piece | URL | Health check |
| --- | --- | --- |
| Frontend (React, Vercel) | https://ang-reactfrontend.vercel.app | open the site |
| API (Laravel, Render) | https://ang-laravelbackend.onrender.com/api | https://ang-laravelbackend.onrender.com/up |
| Overdue service (Django, Render) | https://ang-djangooverdue.onrender.com | https://ang-djangooverdue.onrender.com/health |

```bash
curl -i https://ang-laravelbackend.onrender.com/up
curl https://ang-djangooverdue.onrender.com/health
```

Hosting is on free tiers, so the first request after a period of inactivity can take about a minute while the services wake up. Log in with the demo accounts above.

## Design notes

- Django runs as a backend rules service. Laravel calls it over HTTP to synchronize overdue tasks and to validate every status change.
- Laravel and Django share one MySQL database. Laravel owns the schema; Django maps the `tasks` table with `managed = False`.
- An overdue task cannot be reopened (to `TODO` or `IN_PROGRESS`), because the next sync would mark it `OVERDUE` again. It can only be closed (`DONE`), and only by an admin.
- When an administrator edits an overdue task and moves its due date to today or later, the task returns to `TODO`, because it is no longer overdue. Status itself is never set through the edit endpoint. It only changes through `PATCH /tasks/{task}/status`, which the Django service validates. Regular users cannot edit or delete anything.
