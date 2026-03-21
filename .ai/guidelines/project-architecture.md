# Project Architecture Guidelines

These guidelines describe the embedded features, conventions and architectural decisions of this starter kit. Follow them when implementing new features.

## PostgreSQL Schema-Based Domain Structure

This project uses PostgreSQL named schemas instead of the default `public` schema. Schemas act as domain boundaries.

### Schema Layout

| Schema | Purpose |
|---|---|
| `client` | User-facing domain tables |
| `authentication` | Auth-related tables (`password_reset_tokens`, `sessions`) |
| `storage` | Cache tables (`cache`, `cache_locks`) |
| `queue` | Job tables (`jobs`, `job_batches`, `failed_jobs`) |

### Schema Creation Migration

The migration `0000_00_00_000000_create_initial_schemas.php` shows how to run `CREATE SCHEMA IF NOT EXISTS` statements. When adding a new domain, create a new migration following that pattern.

### Model Table Declaration

Every model must explicitly declare its schema-qualified table name:

```php
protected $table = 'client.users';
```

### Migration Table References

All migrations must use `schema.table` notation:

```php
Schema::create('client.orders', function (Blueprint $table) { ... });
```

## Domain-Driven Directory Structure

Mirror database schemas into PHP namespaces and frontend directories wherever possible.

### Backend

```
app/
  Models/Client/User.php
  Http/Controllers/Client/UserController.php
  Http/Requests/Client/StoreUserRequest.php
  Actions/Client/CreateUserAction.php
  Data/Client/CreateUserData.php
  Exceptions/Client/BadCredentialsException.php
tests/
  Feature/Client/UserTest.php
```

### Routes

Domain-specific route files live in `routes/domain/`:

```
routes/
  web.php              ← includes domain route files
  domain/
    client.php
```

Include them in `routes/web.php` using `require` or in `bootstrap/app.php` via `withRouting()`.

### Frontend

```
resources/js/
  pages/client/         ← Inertia pages grouped by domain
  components/
    domain/client/      ← domain-specific components
    ui/                 ← shadcn/ui primitives (never edit unless extending variants)
    ux/                 ← custom reusable UX components (composition pattern)
```

## Action-Based Architecture

All business logic lives in Action classes. Controllers never contain business logic directly.

### Generating Actions

```bash
php artisan make:action Client/CreateUser
# → app/Actions/Client/CreateUserAction.php (suffix auto-appended)

php artisan make:action Client/CreateUser -d
# → also creates app/Data/Client/CreateUserData.php
```

Actions are plain classes with a `handle()` method:

```php
class CreateUserAction
{
    public function handle(CreateUserData $data): User
    {
        return User::query()->create([...]);
    }
}
```

### When to Use Actions

- Any operation beyond a simple Eloquent call
- Reusable logic shared across controllers, commands, or jobs
- Operations requiring multiple steps or side effects

## Data Transfer Objects (DTOs)

When an action needs more than one parameter, use a DTO.

### Generating DTOs

```bash
php artisan make:data Client/CreateUser
# → app/Data/Client/CreateUserData.php (suffix auto-appended)
```

DTOs are `final readonly` classes with constructor property promotion:

```php
final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
```

## Form Requests

Always use Form Request classes for validation — never inline in controllers. Form Requests handle validation rules, custom error messages, and data transformation.

### getData() Method

Include a `getData()` method to transform validated request data into a DTO:

```php
class StoreUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:client.users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function getData(): CreateUserData
    {
        return new CreateUserData(
            name: $this->string('name'),
            email: $this->string('email'),
            password: $this->string('password'),
        );
    }
}
```

## Thin Controller Pattern

Controllers orchestrate — they don't contain logic. Follow this parameter order and flow:

```
FormRequest → Route params → Action (injected via method DI)
```

### Controller Flow

```
Controller → Form Request validation → Action handle → Session/Flash → Redirect
```

Each step only when needed. Example:

```php
class StoreUserController extends Controller
{
    public function __invoke(StoreUserRequest $request, CreateUserAction $action): RedirectResponse
    {
        $action->handle($request->getData());

        Inertia::notify('User created successfully.', ResponseStyle::TRANSIENT);

        return back(); // back() also can be chained from notify()
    }
}
```

### Key Rules

- Inject Form Requests first, then route model bindings, then Actions
- Call `$action->handle()` passing `$request->getData()` when a DTO is needed
- Use `$request->session()` for session interactions (on the Form Request or base `Request`)
- Use the `Inertia::notify()` macro for flash messages
- Return `back()` or `to_route(...)` as the response

## Exception Handling via AppException

`AppException` serves as the base exception class. Create specific exceptions extending it for each domain or error case:

```php
class InsufficientBalanceException extends AppException
{
    public function __construct()
    {
        parent::__construct('Insufficient balance.', ResponseStyle::CALLOUT, EmphasisVariant::DESTRUCTIVE);
    }
}
```

Then throw the specific exception — it automatically flashes the message via `Inertia::notify()` and returns `back()`:

```php
throw new InsufficientBalanceException();
```

- Avoid wrapping code in try-catch unless interacting with external services that require explicit error handling

## Inertia Notify Macro & Frontend Feedback

### Backend: Inertia::notify()

Declared in `AppServiceProvider::declareMacros()`. Flashes a message to the frontend:

```php
Inertia::notify(string $message, ResponseStyle $style, EmphasisVariant $variant = EmphasisVariant::AFFIRMATIVE);
```

- `ResponseStyle::CALLOUT` → persistent inline alert rendered by `<ResponseCallout />`
- `ResponseStyle::TRANSIENT` → auto-dismissing Sonner toast via `useTransientListener()`

### Frontend Components

- **`<ResponseCallout />`** (`components/ux/messages/response-callout.tsx`) — reads `usePage().flash.callout`, renders an `<Alert>` with emphasis variant styling
- **`useTransientListener()`** (`hooks/use-transient-listener.ts`) — reads `usePage().flash.transient`, fires the appropriate Sonner toast. Must be called at layout level
- **`<Transient />`** (`components/ui/sonner.tsx`) — Sonner provider with emphasis-variant icons and custom color tokens

### Inertia Module Augmentation

Shared props and flash data are typed in `resources/js/types/global.d.ts`:

```ts
declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: { name: string; auth: Auth; sidebarOpen: boolean; };
        flashDataType: { callout?: Response; transient?: Response; };
    }
}
```

Update this file when adding new shared props or flash channels.

## Emphasis Variant System

A semantic color/icon system that spans backend to frontend.

### Backend Enum

`App\Enums\Frontend\EmphasisVariant` — `NEUTRAL`, `AFFIRMATIVE`, `INFORMATIVE`, `PREVENTIVE`, `DESTRUCTIVE`, `INTERROGATIVE`.

### Wayfinder Bridge

Wayfinder generates TypeScript constants and types automatically. Import from:

```ts
import EmphasisVariant from '@/wayfinder/App/Enums/Frontend/EmphasisVariant';
import type { App } from '@/wayfinder/types';
```

### CSS Custom Properties

Each variant has `--{variant}` and `--{variant}-foreground` tokens defined in `resources/css/app.css` using `oklch()` for both light and dark themes.

### Frontend Decoration Pattern

The `useDecorator()` hook resolves a decoration record by variant value:

```ts
const decorator = useDecorator(EmphasisDecoration, variant);
// decorator.icon → LucideIcon component
```

The `Decoration` type (`types/ui/decoration.ts`) defines all available decoration properties (`label`, `description`, `emphasis`, `color`, `icon`). Decoration records use `Pick<Decoration, ...>` to select only the properties they need — see `decorations/ui/emphasis-decoration.ts` for the pattern. New decoration records should follow the same approach.

## Appearance / Theme

The `useAppearance()` hook manages light/dark/system themes. It persists to `localStorage` and a cookie (for SSR). The `HandleAppearance` middleware reads the cookie server-side.

## shadcn/ui + Tailwind CSS

- UI primitives live in `components/ui/` and come from shadcn/ui registry
- Use the shadcn MCP tools to search, view, and install components before writing custom ones
- Custom reusable UX components live in `components/ux/` using the composition pattern
- shadcn `Alert` is extended with emphasis variant classes (`affirmative`, `informative`, `preventive`, `interrogative`, `neutral`)
- Tailwind v4 is configured via `resources/css/app.css` with `@import 'shadcn/tailwind.css'`

## Wayfinder (Dev Next)

Wayfinder generates fully-typed TypeScript from Laravel code. Custom guidelines and skills exist in `.ai/guidelines/wayfinder/` and `.ai/skills/wayfinder-development/`. Always activate the `wayfinder-development` skill when referencing backend constructs in frontend.

Key points:
- All generated files live under `resources/js/wayfinder/` — never edit them manually
- The Vite plugin auto-regenerates during `dev`; otherwise run `php artisan wayfinder:generate`
- Import types from `@/wayfinder/types`, enum constants from `@/wayfinder/App/Enums/...`

## Application Defaults

Configured in `AppServiceProvider::configureDefaults()`:

- `CarbonImmutable` as default date class
- Destructive DB commands prohibited in production
- Strict model mode outside production
- Aggressive Vite prefetching
- HTTPS forced in production
- Password defaults enforced in production (min 12, mixed case, letters, numbers, symbols, uncompromised)

## Quality Check

Run the full quality pipeline:

```bash
composer run quality
```

This executes in order:
1. `pnpm run format` — Prettier
2. `pnpm run lint` — ESLint
3. `pint --parallel app/` — Laravel Pint
4. `phpstan analyse app/ --memory-limit=2G` — Larastan

Run before committing or pushing. Also available:
- `composer run test` — config clear + Pint test + `php artisan test`
- `composer run lint` — Pint only

## Future Feature Implementation Notes

When implementing a new domain feature:

1. Generate a new `create_{domain}_schema_migration` for PostgreSQL schema if new domain
3. Create Model in `app/Models/{Domain}/` with explicit `$table` and `casts()` method
4. Create Factory and Seeder
5. Create Form Request in `app/Http/Requests/{Domain}/` with `getData()` returning a DTO
6. Create Action in `app/Actions/{Domain}/` with typed `handle()` method
7. Create DTO in `app/Data/{Domain}/` as `final readonly` if action needs multiple params
8. Create thin Controller in `app/Http/Controllers/{Domain}/` following injection order
9. Add route in `routes/domain/{domain}.php`, include it in `routes/web.php`
10. Create Inertia page in `resources/js/pages/{domain}/`
11. Use Wayfinder types — don't duplicate backend types manually
12. Use `Inertia::notify()` for user feedback, `AppException` for error flows
13. Write Pest feature tests in `tests/Feature/{Domain}/`
14. Run `composer run quality` to validate
