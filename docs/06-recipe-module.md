# Task 05 — Recipe Management

## Goal
Build the complete recipe CRUD module.

## Recipe Fields
- user_id
- category_id
- title
- slug
- description
- image
- prep_time
- cook_time
- servings
- difficulty
- status
- published_at
- timestamps

Difficulty:
- easy
- medium
- hard

Status:
- draft
- published
- archived

## Features
Authenticated users:
- create
- edit
- publish
- archive
- delete own recipes

Public:
- browse published recipes
- view recipe details

Admin/moderator:
- moderate according to permissions

## Security
- Use policies.
- Prevent IDOR.
- Never accept user_id from trusted browser input.
- Validate every field server-side.
- Secure image uploads.
- Do not expose drafts publicly.

## UI
Create:
- recipe list
- recipe card
- create form
- edit form
- details page
- responsive layout

## Acceptance Criteria
A user cannot edit/delete another user's recipe by changing the URL or request payload.
