# Task 12 — Security, Performance and Automated Testing

## Goal
Perform a dedicated security and quality pass over the application.

## Security Audit
Check:
- XSS
- CSRF
- SQL injection
- mass assignment
- IDOR
- broken access control
- privilege escalation
- insecure file uploads
- authentication issues
- session security
- rate limiting
- spam
- information disclosure
- sensitive data exposure

## Authentication
Verify:
- password hashing
- login throttling
- session regeneration
- password reset
- email verification
- secure logout

## Authorization
Verify:
- policies
- Spatie permissions
- role middleware
- ownership checks
- admin/moderator separation

## Guest Security
Verify:
- guest token security
- rate limiting
- spam prevention
- anonymous display
- no unnecessary personal data storage

## File Uploads
Verify:
- MIME validation
- extension validation
- size limits
- safe filenames
- image validation
- executable upload prevention

## Performance
Check:
- N+1 queries
- eager loading
- indexes
- pagination
- expensive dashboard queries
- image sizes

## Automated Tests
Create tests covering:
- authentication
- authorization
- recipes
- categories
- comments
- reactions
- forum
- reports
- roles
- permissions
- guest interactions
- file uploads
- security failures

Include negative tests.

## Verification
Run:
```bash
php artisan test
npm run build
php artisan route:list
php artisan migrate:fresh --seed
```

## Production Checks
Confirm:
- APP_DEBUG=false
- HTTPS-ready cookies
- secure session settings
- appropriate security headers
- no sensitive logs
- friendly error pages

## Acceptance Criteria
The application must not be considered complete until security-sensitive tests pass and the major authorization boundaries have been verified.
