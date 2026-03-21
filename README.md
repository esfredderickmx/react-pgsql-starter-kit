# Laravel Starter Kit — Inertia React + PostgreSQL

An opinionated Laravel starter kit built on **PostgreSQL schemas**, **Inertia v2 + React 19**, and an **action-based architecture**. Designed for developers who want clear domain boundaries, type safety across the full stack, and sensible defaults from day one.

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.4 |
| Frontend | React 19, TypeScript, Inertia v2 |
| Database | PostgreSQL (named schemas) |
| Styling | Tailwind CSS v4, shadcn/ui |
| Type Bridge | Laravel Wayfinder (dev-next) |
| Testing | Pest 4 |
| Static Analysis | Larastan (PHPStan), ESLint, Prettier |
| Code Style | Laravel Pint |

## Getting Started

> Requires PHP 8.2+, PostgreSQL, Node.js, and **pnpm**.

Create your app using [Laravel installer](https://laravel.com/docs/12.x/starter-kits#community-maintained-starter-kits):
```bash
laravel new my-app --using=esfredderick/laravel-blank-inertia-react-pgsql-starter-kit
```

### Initial setup

```bash
cd example-app

# runs migrations using your own db credentials
composer setup

# runs the dev server
composer dev
```

## Architecture Overview

> [!IMPORTANT]
> This section describes the **conventions and patterns** embedded in this starter kit — not automatic behaviors. They define how the codebase is structured and how new features should be built. Detailed guidelines live in `.ai/guidelines/project-architecture.md`.

### PostgreSQL Schemas as Domain Boundaries

Tables live in named schemas (`client`, `authentication`, `storage`, `queue`) instead of the default `public` schema. Schemas are created via a dedicated initial migration. Models declare their table with schema-qualified names:

```php
protected $table = 'client.users';
```

A `PgsqlVerificationService` automatically creates the database and migration schema when they don't exist — no manual setup required (only for those).

### Domain-Driven Directory Structure

Database schemas mirror into PHP namespaces and frontend directories:

```
app/
  Models/Client/              Http/Controllers/Client/
  Http/Requests/Client/       Actions/Client/
  Data/Client/                Exceptions/Client/
resources/js/
  pages/client/               components/domain/client/
routes/
  domain/client.php
tests/
  Feature/Client/
```

### Action-Based Business Logic

All business logic goes into Action classes — controllers stay thin. Custom artisan commands generate both:

```bash
php artisan make:action Client/CreateUser    # → app/Actions/Client/CreateUserAction.php (-d to also create DTO)
php artisan make:data Client/CreateUser      # → app/Data/Client/CreateUserData.php
```

Actions are plain classes with a `handle()` method. DTOs are `final readonly` classes used when actions need more than one parameter.

### Thin Controllers

Controllers follow a strict injection order and flow:

```
FormRequest → Route params → Action (via method DI)
```

Form Requests handle validation and expose a `getData()` method that transforms input into a DTO. The controller calls `$action->handle($request->getData())`, flashes feedback via `Inertia::notify()`, and redirects.

### Exception Handling

`AppException` is the base exception class. Domain-specific exceptions extend it and auto-render as flash messages — no try-catch needed:

```php
throw new InsufficientBalanceException(); // flashes + redirects back automatically
```

### Frontend Feedback System

Two flash channels from backend to frontend:

- **Callout** — persistent inline alert via `<ResponseCallout />`
- **Transient** — auto-dismissing Sonner toast via `useTransientListener()`

Both are triggered through a single macro:

```php
Inertia::notify('Done!', ResponseStyle::TRANSIENT);
Inertia::notify('Check this.', ResponseStyle::CALLOUT, EmphasisVariant::INFORMATIVE);
```

### Emphasis Variant System

A semantic color/icon system spanning the full stack:

- **Backend**: `EmphasisVariant` enum (`AFFIRMATIVE`, `INFORMATIVE`, `PREVENTIVE`, `DESTRUCTIVE`, `INTERROGATIVE`, `NEUTRAL`)
- **Type Bridge**: Wayfinder auto-generates TypeScript constants and types
- **CSS**: Custom `oklch()` color tokens per variant (light + dark)
- **Components**: shadcn `Alert` extended with variant classes, `useDecorator()` hook for icon resolution

### Wayfinder Type Bridge

[Laravel Wayfinder](https://github.com/laravel/wayfinder) (dev-next) generates typed TypeScript from Laravel routes, models, enums, form requests, and Inertia page props. All output lives under `resources/js/wayfinder/` and auto-regenerates via Vite plugin during development.

Inertia shared props and flash data are typed through module augmentation in `resources/js/types/global.d.ts`.

### Frontend Conventions

- **UI primitives** from shadcn/ui live in `components/ui/`
- **Custom reusable components** live in `components/ux/` using composition pattern
- **Decoration records** map enum variants to UI properties via `Pick<Decoration, ...>` — see `decorations/ui/emphasis-decoration.ts`
- **Theme** managed by `useAppearance()` hook (light/dark/system, cookie-persisted for SSR)

## Custom Available Commands

### Development (via artisan)

| Command | Description                                            |
|---|--------------------------------------------------------|
| `app:configure-database` | Re-configures db postgres credentials directly in .env |

### Code Quality

| Command | Description |
|---|---|
| `composer run quality` | Prettier + ESLint + Pint + Larastan |

### Code Generation

| Command | Description |
|---|---|
| `php artisan make:action {name}` | Action class (auto-suffixed, `-d` to also create DTO) |
| `php artisan make:data {name}` | DTO class (auto-suffixed) |

All standard `php artisan make:*` commands (model, controller, request, migration, etc.) are available as usual.

## Application Defaults

Configured in `AppServiceProvider`:

- `CarbonImmutable` as default date class
- Strict model mode outside production
- Destructive DB commands prohibited in production
- Aggressive Vite prefetching
- HTTPS forced in production
- Password rules enforced in production (min 12, mixed case, symbols, uncompromised)

## License

MIT
