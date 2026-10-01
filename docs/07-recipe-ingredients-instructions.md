# Task 06 — Recipe Ingredients and Instructions

## Goal
Add structured ingredients and cooking instructions to recipes.

## Ingredients
Fields:
- recipe_id
- name
- quantity
- unit
- sort_order

## Instructions
Fields:
- recipe_id
- step_number
- instruction

## UI
Recipe form must allow users to:
- add ingredient rows
- remove ingredient rows
- add instruction steps
- remove instruction steps
- reorder where practical

## Backend
Use database transactions when saving a recipe and its related records together.

Validate:
- required fields
- reasonable text lengths
- numeric values
- ownership/authorization

## Acceptance Criteria
Recipe details display ingredients and instructions in the correct order.
