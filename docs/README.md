# Recipe Community Web App — Task Files

Use these tasks sequentially. Each task is intentionally scoped so it can be assigned to an AI coding agent or developer without requiring the entire project prompt to be repeated.

## Recommended Order

1. `00-project-overview.md`
2. `01-project-setup.md`
3. `02-authentication-roles-permissions.md`
4. `03-user-profile-module.md`
5. `04-recipe-categories.md`
6. `05-recipe-module.md`
7. `06-recipe-ingredients-instructions.md`
8. `07-comments-reactions-guests.md`
9. `08-forum-module.md`
10. `09-reports-moderation-notifications.md`
11. `10-admin-moderator-dashboards.md`
12. `11-search-seo-accessibility.md`
13. `12-security-performance-testing.md`
14. `13-final-ui-polish-and-documentation.md`

## How to Use

Complete each task before starting the next task.

For every task:

1. Read the task file.
2. Inspect the existing codebase before making changes.
3. Implement only the requested scope unless a dependency requires a related change.
4. Reuse existing components and conventions.
5. Run relevant tests.
6. Fix errors before moving to the next task.
7. Do not weaken security or authorization to make a feature work.
8. Keep the code beginner-readable.

## Important Rule

Frontend visibility is not authorization.

Every protected operation must be enforced server-side with Laravel authentication, Spatie permissions, and/or Laravel Policies.

## Final Verification

After all tasks are complete, run:

```bash
php artisan test
npm run build
php artisan route:list
php artisan migrate:fresh --seed
```

Then perform the security review in Task 12 and the final UI/documentation review in Task 13.
