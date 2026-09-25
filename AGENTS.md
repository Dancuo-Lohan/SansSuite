# Sans Suite — AI Contribution Guide

## Mission

Sans Suite is a local-first job application tracker. It must remain simple, calm, and fast: a user should be able to record an application in under one minute and add optional details only when the application progresses.

When requirements are ambiguous, prefer the smallest change that improves this core workflow without adding administrative overhead.

## Non-negotiable rules

- Never modify anything inside `CorianderCore/`.
- Keep the existing stack: CorianderPHP, PHP 8.2+, SQLite, TypeScript, and Tailwind CSS.
- Never commit the SQLite database, WAL/SHM/journal files, attachments, `.env`, backups, or personal user data.
- The local database contains real user data. Never delete, reset, recreate, seed, or overwrite it.
- Keep advanced fields and features optional and, when practical, collapsed by default.
- Preserve keyboard navigation, mobile usability, readable focus states, and accessible contrast.
- User-facing copy must be in French. Source-code comments and developer documentation must be in English.

## Before making changes

1. Inspect the relevant route, controller, service, repository, view, and tests before editing.
2. Follow existing project conventions instead of introducing a second pattern.
3. Keep the requested scope narrow. Do not perform unrelated refactors.
4. Check whether the change affects the current SQLite schema or real local data.
5. Do not modify generated or framework-owned files when a project-level solution exists.

## Architecture boundaries

- `src/Controllers/`: HTTP adaptation only. Parse the request, call a service, and return a response. Keep controllers short.
- `src/Services/`: use cases, transactions, and business orchestration.
- `src/Repositories/`: SQLite access through PDO and prepared statements.
- `src/Validation/`: input validation and normalization.
- `src/Domain/`: stable business concepts, statuses, types, and rules.
- `src/Support/`: rendering, HTTP responses, and shared infrastructure helpers.
- `public/public_views/`: presentation only. No SQL queries or business rules.
- `public/routes.php`: route declarations only.
- `database/migrations/`: the reproducible installation schema.
- `tests/`: application-owned automated tests. Do not include CorianderPHP framework tests.

Controllers delegate to services, and services delegate persistence to repositories. Do not bypass these boundaries for convenience.

## Database policy before the first release

The application is not released yet, so the repository should describe a clean first installation rather than its development history.

- Keep one consolidated initial migration and update it directly when the schema changes.
- Do not add incremental historical migrations unless the user explicitly asks to start maintaining release-to-release upgrades.
- For a schema change, update the initial migration for fresh installations and preserve the existing local database.
- Before altering the local database, inspect its current schema. Apply only the narrow, idempotent change required for the new code.
- Never add real data to migrations, fixtures, tests, documentation, or commits.
- Do not run destructive migration commands against `database/database.sqlite`.

## PHP and code quality

- Use `declare(strict_types=1);` in PHP source files.
- Prefer typed parameters, typed returns, immutable values, and explicit dependencies.
- Follow SOLID and DRY when they make the code easier to understand.
- Do not create abstractions for hypothetical future needs. A small amount of obvious code is better than premature architecture.
- Centralize repeated SQL, validation rules, and reusable UI patterns.
- Validate all input on the server, even when the browser also validates it.
- Keep comments short and useful. Explain intent or section boundaries, not syntax.
- At the top of PHP views, document injected variables with PHPDoc so static analysis tools such as Intelephense can understand them.
- Use brief HTML comments to mark major view sections when they improve navigation and debugging.

## Security requirements

- Protect every state-changing request with CorianderPHP's CSRF mechanism.
- Escape all user-controlled HTML output.
- Use prepared statements exclusively for user-provided values.
- Validate attachment type, extension, and size.
- Store attachments outside `public/` with random internal names.
- Protect CSV exports against spreadsheet formula injection.
- Require explicit confirmation before permanent deletion.
- Do not expose absolute storage paths or implementation details in user-facing errors.

## Product and interface rules

- Use a calm blue primary palette, light backgrounds, and restrained semantic colors.
- Do not add motivational messages, gamification, streaks, or guilt-inducing statistics.
- Keep the main navigation limited to: `Vue d'ensemble`, `Candidatures`, and `Calendrier`.
- Use explicit French labels; avoid vague wording such as a date field without explaining what the date controls.
- Keep application cards compact enough to scan quickly.
- Show future calendar tasks in yellow and before historical events for the same day.
- Contacts and interview preparation are optional and should appear only after the initial `En attente` phase.
- Destructive actions should be recognizable but visually restrained.
- Avoid full-width buttons when their width makes them look like passive panels.

## Verification checklist

Run checks proportional to the change. Before handing off a feature, normally complete all of the following:

1. Run PHP syntax checks on changed PHP files.
2. Run the application test suite:

   ```powershell
   composer test
   ```

3. Build production assets after any view, Tailwind, or TypeScript change:

   ```powershell
   Set-Location nodejs
   npm run build-prod
   ```

4. Verify the affected workflow in the browser at `http://sanssuite/`, including a mobile viewport when UI changed.
5. For broader changes, verify creation, editing, search, multiple follow-ups, archiving, deletion, calendar behavior, attachments, and CSV export as applicable.
6. Use `git status` when the directory is a Git working tree and confirm that no local database, attachment, environment file, or personal data is tracked.
7. Confirm that `CorianderCore/` was not modified.

Do not mutate real applications merely to perform a visual check. Prefer automated tests, temporary in-memory databases, or read-only browser verification.

## Installation commands

Use these commands for a fresh local installation:

```powershell
composer install
Copy-Item .env-example .env
php coriander migrate
Set-Location nodejs
npm install
npm run build-prod
```
