@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel Wayfinder (next)

Wayfinder generates fully-typed TypeScript from your Laravel code: routes, models, form requests, enums, Inertia page props, broadcast channels/events, and Vite env variables. All generated files live under `@/wayfinder/`.

- IMPORTANT: Activate `wayfinder-development` skill whenever referencing backend constructs in frontend components.
- Controller Imports: `import { PostController } from '@/wayfinder/App/Http/Controllers/PostController'; PostController.show({ post: 1 })`.
- Named Routes: `import posts from '@/wayfinder/routes/posts'; posts.show({ post: 1 })`.
- URL Generation: `PostController.show.url({ post: 42 })` returns the URL string only.
- Query Params: `PostController.index({ query: { page: 2 } })` or `{ mergeQuery: { page: 3 } }` to merge with current URL.
- HTTP Method Variants: `PostController.update.patch({ post: 1 })`, `.get()`, `.head()`, `.delete()`.
- Form-Safe Routes: `PostController.update.form({ post: 1 })` returns `{ action, method: 'post' }` with `_method` spoofing.
- Types: Import from `@/wayfinder/types` for models (`App.Models.User`), form requests (`App.Http.Controllers.PostController.Store.Request`), enums (`App.Enums.PostStatus`), and Inertia page props (`Inertia.Pages.Dashboard`).
- Enum Constants: `import PostStatus from '@/wayfinder/App/Enums/PostStatus'; PostStatus.Published`.
- Broadcast Channels: `import { BroadcastChannels } from '@/wayfinder/broadcast-channels'` for type-safe channel subscriptions.
- Environment Variables: Wayfinder generates `vite-env.d.ts` for typed `import.meta.env`.
@if($assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_LARAVEL) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_REACT) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_VUE) || $assist->roster->uses(\Laravel\Roster\Enums\Packages::INERTIA_SVELTE))
- Inertia: Use `.form()` with `<form>` element or spread into Inertia's `<Form>` component. Page props types available as `Inertia.Pages.*`. Shared data types auto-generated from `HandleInertiaRequests` middleware.
@endif
