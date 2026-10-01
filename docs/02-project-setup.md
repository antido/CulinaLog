# Task 01 — Laravel, Breeze, Inertia and React Setup

## Goal
Create the base Laravel application and configure the frontend stack.

## Requirements
- Install/configure Laravel.
- Configure MySQL.
- Configure Laravel Breeze with Inertia React.
- Configure Tailwind CSS.
- Configure Vite.
- Verify Laravel authentication pages.
- Verify Inertia page rendering.
- Verify React frontend builds successfully.

## Environment
Create/update `.env.example` with:
- APP settings
- database settings
- session settings
- mail settings

Do not commit real credentials.

## Verification
Run:
```bash
php artisan about
php artisan route:list
php artisan migrate
npm run build
```

## Acceptance Criteria
- Laravel loads successfully.
- Login/register work.
- Inertia React page renders.
- Tailwind styles work.
- Vite build succeeds.
