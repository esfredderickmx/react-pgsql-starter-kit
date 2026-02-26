---
name: wayfinder-development
description: "Activates whenever referencing backend routes, types, models, enums, broadcast channels, or environment variables in frontend components. Use when importing from @/wayfinder, calling Laravel routes from TypeScript, working with Wayfinder route functions, using generated model types, form request types, PHP enum constants, Inertia page props, broadcast channels/events, or Vite env variable types."
license: MIT
metadata:
  author: laravel
---

# Wayfinder Development (next)

## When to Apply

Activate whenever referencing Laravel backend constructs in frontend TypeScript:
- Importing from `@/wayfinder/` (controllers, routes, types, enums, broadcast channels)
- Calling Laravel routes from TypeScript/JavaScript
- Creating links or navigation to backend endpoints
- Using generated TypeScript types for models, form requests, enums, or Inertia page props
- Working with broadcast channels or events
- Referencing typed Vite environment variables

## Documentation

Use `search-docs` for detailed Wayfinder patterns and documentation.

## Quick Reference

### Generate TypeScript

Run after route/model/enum changes if Vite plugin isn't installed:
```bash
php artisan wayfinder:generate
```

Command options:
| Option        | Description                          |
| ------------- | ------------------------------------ |
| `--path`      | Output directory for generated files |
| `--base-path` | Comma-separated base paths to scan   |
| `--app-path`  | Comma-separated app paths to scan    |
| `--fresh`     | Clear the cache before generating    |

### Import Patterns

All generated files live under `@/wayfinder/` by default (`resources/js/wayfinder`).

<!-- Controller Action Imports -->
```typescript
// Controller imports (follows PHP namespace)...
import { PostController } from '@/wayfinder/App/Http/Controllers/PostController'

// Named route imports...
import posts from '@/wayfinder/routes/posts'

// Type imports (models, form requests, enums, Inertia props)...
import { App, Inertia } from '@/wayfinder/types'

// Enum constants for runtime use...
import PostStatus from '@/wayfinder/App/Enums/PostStatus'

// Broadcast channel helpers...
import { BroadcastChannels } from '@/wayfinder/broadcast-channels'
```

### Routes & Controller Actions

<!-- Wayfinder Route Methods -->
```typescript
// Get route object (url + method)...
PostController.index()
// { url: '/posts', method: 'get' }

PostController.show({ post: 1 })
// { url: '/posts/1', method: 'get' }

// Get URL string only...
PostController.show.url({ post: 42 })
// '/posts/42'

// HTTP method variants...
PostController.index.get()
PostController.index.head()
PostController.update.patch({ post: 1 })

// Query parameters...
PostController.index({ query: { page: 2, sort: 'created_at' } })
// { url: '/posts?page=2&sort=created_at', method: 'get' }

// Merge with existing query string...
PostController.index({ mergeQuery: { page: 3 } })

// Form-safe routes (method spoofing for HTML forms)...
PostController.update.form({ post: 1 })
// { action: '/posts/1?_method=PATCH', method: 'post' }

PostController.destroy.form({ post: 1 })
// { action: '/posts/1?_method=DELETE', method: 'post' }
```

### Named Routes

```typescript
import posts from '@/wayfinder/routes/posts'

posts.index()
// { url: '/posts', method: 'get' }

posts.show({ post: 1 })
// { url: '/posts/1', method: 'get' }
```

### Form Request Types

```typescript
import { App } from '@/wayfinder/types'

// Use with Inertia's useForm
const form = useForm<App.Http.Controllers.PostController.Store.Request>()
```

### Eloquent Model Types

```typescript
import { App } from '@/wayfinder/types'

function displayUser(user: App.Models.User) {
    console.log(user.name, user.email)
}
```

### PHP Enums

```typescript
import PostStatus from '@/wayfinder/App/Enums/PostStatus'
import { App } from '@/wayfinder/types'

// Runtime constants
if (post.status === PostStatus.Published) { /* ... */ }

// Type-safe parameter
function setStatus(status: App.Enums.PostStatus) { /* ... */ }
```

## Wayfinder + Inertia

### Page Props Types

```typescript
// Vue
import { Inertia } from '@/wayfinder/types'
defineProps<Inertia.Pages.Dashboard>()

// React — use the generated page type for component props
```

### Form Usage (React)

```typescript
<form {...PostController.update.form({ post: post.id })}>
    {/* fields */}
</form>
```

### Shared Data Types

Wayfinder generates types from `HandleInertiaRequests` middleware automatically. Page prop types extend `Inertia.SharedData`.

## Broadcast Channels & Events

```typescript
import { BroadcastChannels } from '@/wayfinder/broadcast-channels'
import Echo from 'laravel-echo'

// Type-safe channel subscription
Echo.private(BroadcastChannels.orders(orderId))
    .listen('OrderShipped', (e) => {
        console.log(e.trackingNumber)
    })
```

With `@laravel/echo-vue` or `@laravel/echo-react`, Wayfinder generates type augmentations for full event type safety.

## Environment Variables

Wayfinder generates `vite-env.d.ts` for typed `import.meta.env` based on your `.env` `VITE_*` variables.

```typescript
const appName = import.meta.env.VITE_APP_NAME // TypeScript knows this is a string
```

## Configuration

Publish the config with:
```bash
php artisan vendor:publish --tag=wayfinder-config
```

Key config options in `config/wayfinder.php`:
| Option                           | Description                            | Default |
| -------------------------------- | -------------------------------------- | ------- |
| `generate.route.actions`         | Generate controller action files       | `true`  |
| `generate.route.named`           | Generate named route files             | `true`  |
| `generate.route.form_variant`    | Include `.form` method variants        | `true`  |
| `generate.route.ignore.urls`     | URL patterns to ignore                 | `[]`    |
| `generate.route.ignore.names`    | Route name patterns to ignore          | `['nova.*']` |
| `generate.models`                | Generate model types                   | `true`  |
| `generate.inertia.shared_data`   | Generate Inertia shared data types     | `true`  |
| `generate.broadcast.channels`    | Generate broadcast channel helpers     | `true`  |
| `generate.broadcast.events`      | Generate broadcast event types         | `true`  |
| `generate.environment_variables` | Generate Vite env variable types       | `true`  |
| `generate.enums`                 | Generate PHP enum types                | `true`  |
| `format.enabled`                 | Format generated files with Biome      | `false` |
| `cache.enabled`                  | Enable caching for faster regeneration | `true`  |

## Verification

1. Run `php artisan wayfinder:generate` to regenerate TypeScript
2. Check imports resolve correctly from `@/wayfinder/`
3. Verify route URLs match expected paths
4. Confirm types in `types.d.ts` match your models and form requests

## Migration from Previous Beta

If upgrading from Wayfinder 0.1.x:
- All files are now generated under `resources/js/wayfinder` (no more separate `actions` and `routes` at root)
- `types.ts` is now `types.d.ts`
- Controller imports follow PHP namespace directly under `@/wayfinder/` (no more `@/actions/` path)
- Named routes remain under `@/wayfinder/routes/`
- CLI flags `--skip-actions`, `--skip-routes`, `--with-form` are replaced by config values
- If using the Vite plugin, remove the `routes`, `actions`, and `withForm` arguments

## Common Pitfalls

- Using `@/actions/` or `@/routes/` paths instead of `@/wayfinder/` (old beta paths)
- Using default imports for controllers instead of named imports (breaks tree-shaking)
- Forgetting to regenerate after route, model, or enum changes
- Not using type-safe parameter objects for route model binding
- Importing enum types when you need runtime constants (use the `.ts` file, not `types.d.ts`)
