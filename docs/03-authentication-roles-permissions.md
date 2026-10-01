# Task 02 — Authentication, Roles and Permissions

## Goal
Implement secure authentication and authorization foundations.

## Requirements
Use:
- Laravel Breeze
- Spatie Laravel Permission
- Laravel Policies

Create roles:
- admin
- moderator
- user

Create granular permissions for:
- users
- recipes
- categories
- comments
- reactions
- forum
- reports
- roles
- permissions
- dashboard/settings

## Seeders
Create:
- roles
- permissions
- development admin
- development moderator
- development user

Never allow normal registration to select a role.

## Security
Protect against:
- privilege escalation
- mass assignment
- unauthorized admin access
- unauthorized moderator access

Use middleware and policies server-side.

## Tests
Test:
- authenticated access
- guest access
- role access
- permission access
- user cannot assign themselves admin
- user cannot access admin routes

## Acceptance Criteria
Spatie permissions are installed, seeded, assigned, and actually enforced.
