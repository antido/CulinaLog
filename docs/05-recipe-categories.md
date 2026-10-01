# Task 04 — Recipe Categories

## Goal
Implement recipe categories.

## Fields
- id
- name
- slug
- description
- image
- is_active
- created_at
- updated_at

## Features
Public:
- category list
- category details
- recipes by category

Admin:
- create category
- edit category
- delete/disable category

## Requirements
- Unique slugs.
- Validation through Form Requests.
- Policies/permissions for management.
- Pagination where needed.
- Secure image upload handling.

## Acceptance Criteria
Categories work independently and integrate cleanly with recipes.
