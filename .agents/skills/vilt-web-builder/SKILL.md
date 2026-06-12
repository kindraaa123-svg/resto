---
name: vilt-web-builder
description: build production-ready web applications using the vilt stack: vue, inertia, laravel, and tailwind css. use this skill when the user asks to create, scaffold, refactor, or extend a web app with reusable design components, separate ui/component files, laravel controllers/routes/models, inertia pages, and consistent frontend architecture.
---

# VILT Web Builder

Use this skill to build or modify web applications using the VILT stack:

- Laravel for backend, routing, validation, controllers, models, migrations, policies, and services.
- Inertia.js for connecting Laravel routes to Vue pages.
- Vue 3 for frontend pages and reusable components.
- Tailwind CSS for styling.
- Component-first design, where UI elements are created once and reused by calling/importing them.

## Core Behavior

When building a feature or page:

1. Understand the requested feature, page, or app.
2. Break the UI into reusable components.
3. Create shared design components first.
4. Create page-level components that import and compose shared components.
5. Create Laravel routes, controllers, requests, models, migrations, and services when needed.
6. Keep business logic out of Vue components when it belongs in Laravel.
7. Keep repeated UI patterns out of pages by moving them into reusable components.
8. Return clean, organized code with file paths.

## Default Stack Assumptions

Use these defaults unless the user says otherwise:

- Laravel 11 or newer.
- Vue 3 with Composition API.
- Inertia.js.
- Tailwind CSS.
- Vite.
- TypeScript only if the existing project uses it or the user requests it.
- Pinia only if global frontend state is truly needed.
- Laravel Form Request classes for validation.
- Laravel Resource classes for API-like response shaping when useful.
- Eloquent relationships for database modeling.

## Project Structure

Prefer this frontend structure:

```txt
resources/js/
├── Components/
│   ├── ui/
│   │   ├── Button.vue
│   │   ├── Input.vue
│   │   ├── Card.vue
│   │   ├── Modal.vue
│   │   ├── Badge.vue
│   │   └── Dropdown.vue
│   ├── forms/
│   ├── tables/
│   ├── layout/
│   └── feature-specific/
├── Layouts/
│   ├── AppLayout.vue
│   ├── GuestLayout.vue
│   └── DashboardLayout.vue
├── Pages/
│   └── ...
├── Composables/
│   └── ...
├── lib/
│   └── utils.js
└── app.js
```

Prefer this backend structure:

```txt
app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
├── Models/
├── Services/
└── Policies/

database/
├── migrations/
├── seeders/
└── factories/

routes/
├── web.php
└── auth.php
```

## Component Design Rules

Always create reusable design components for repeated UI.

Examples:

* Buttons must use `Components/ui/Button.vue`.
* Inputs must use `Components/ui/Input.vue`.
* Cards must use `Components/ui/Card.vue`.
* Modals must use `Components/ui/Modal.vue`.
* Tables should use reusable table components if more than one table exists.
* Form fields should be reusable when multiple forms share the same style.

Do not duplicate Tailwind class blocks across pages. Move repeated styling into components.

## Component API Rules

Every reusable component should have a clear API using props and slots.

Example pattern:

```vue
<script setup>
defineProps({
  variant: {
    type: String,
    default: 'primary',
  },
  size: {
    type: String,
    default: 'md',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <button>
    <slot />
  </button>
</template>
```

Use slots for flexible content.

Use props for variants, sizes, states, labels, icons, and behavior.

## Styling Rules

Use Tailwind CSS.

Prefer consistent design tokens:

* Primary color.
* Secondary color.
* Background color.
* Border color.
* Text color.
* Radius.
* Shadow.
* Spacing scale.

Use clean, modern UI:

* Rounded corners.
* Soft shadows.
* Clear spacing.
* Responsive layout.
* Accessible contrast.
* Hover, focus, loading, and disabled states.

Avoid excessive custom CSS unless Tailwind cannot express the design cleanly.

## Page Building Workflow

When creating a page:

1. Identify the page purpose.
2. List required sections.
3. Split sections into components.
4. Build reusable UI components first.
5. Build feature components.
6. Build the Inertia page.
7. Connect the page to Laravel route and controller.
8. Pass props from Laravel to Inertia.
9. Add validation and backend logic if forms are involved.

Example output order:

```txt
1. routes/web.php
2. app/Http/Controllers/...
3. app/Http/Requests/...
4. app/Models/...
5. database/migrations/...
6. resources/js/Components/ui/...
7. resources/js/Components/feature/...
8. resources/js/Pages/...
```

## Inertia Rules

Use Laravel controllers to return Inertia pages.

Example:

```php
return Inertia::render('Dashboard/Index', [
    'stats' => $stats,
]);
```

In Vue pages, receive props using:

```js
defineProps({
  stats: Object,
})
```

Use Inertia forms for form handling:

```js
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  name: '',
  email: '',
})
```

Submit with:

```js
form.post(route('users.store'))
```

Show validation errors near the relevant input.

## Laravel Rules

Use named routes.

Use RESTful controller methods when suitable:

* index
* create
* store
* show
* edit
* update
* destroy

Use Form Request validation for create/update actions.

Use services for complex business logic.

Use policies for authorization when the feature involves user permissions.

Use migrations for database changes.

Use seeders/factories when sample data is useful.

## Output Format

When generating code, always include file paths before code blocks.

Example:

```txt
resources/js/Components/ui/Button.vue
```

```vue
<template>
  ...
</template>
```

Do not dump all files without explanation. Briefly explain the structure first, then provide the files.

## Refactoring Rules

When the user gives existing code:

1. Identify repeated UI.
2. Extract repeated UI into reusable components.
3. Keep page components focused on layout and data flow.
4. Keep form state local unless shared state is needed.
5. Preserve existing behavior unless the user asks to change it.
6. Mention which files were changed and why.

## Quality Checklist

Before finalizing code, check:

* Components are reusable.
* Pages are not overloaded with repeated Tailwind markup.
* Props and slots are clean.
* Laravel routes are named.
* Controllers are thin.
* Validation is handled properly.
* Errors and loading states are handled.
* UI is responsive.
* Code follows existing project conventions.
* No unused imports.
* No hardcoded mock data unless clearly marked as sample data.

## Preferred Answer Style

Respond in Indonesian unless the user asks otherwise.

Use concise explanations.

When producing implementation code, organize the answer like this:

```txt
Struktur yang dibuat:
- ...
- ...

File:
1. ...
2. ...
3. ...
```

Then provide each file with its path and code.

## Example User Requests This Skill Should Handle

* "Buatkan dashboard admin pakai VILT."
* "Buat landing page dengan komponen sendiri-sendiri."
* "Bikin CRUD products pakai Laravel Inertia Vue Tailwind."
* "Refactor halaman ini supaya komponennya reusable."
* "Buat design system sederhana untuk project VILT."
* "Buat form user dengan validation Laravel dan useForm Inertia."
* "Pisahkan card, button, input, modal jadi component sendiri."

## Strict Implementation Instruction

Do not place reusable UI directly inside page files.

If a page contains repeated visual patterns, create or reuse components from `resources/js/Components`.

Before writing a page, first decide which components are needed.

Always prefer:

- `Components/ui/*` for base design components.
- `Components/forms/*` for reusable form components.
- `Components/tables/*` for reusable table components.
- `Components/feature-name/*` for feature-specific components.
- `Pages/*` only for page composition and data binding.

Never mix backend business rules into Vue components.
Never duplicate large Tailwind class groups across multiple files.
