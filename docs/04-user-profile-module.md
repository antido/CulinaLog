# Task 03 — User Profiles and Account Management

## Goal
Create user profile and account management functionality.

## Features
Users can manage:
- name
- username
- profile image
- bio
- email
- password

Display public profile information:
- name
- username
- bio
- profile image
- joined date
- recipe count
- forum activity
- comments count

## Security
Never expose:
- password
- password hash
- remember token
- private session data
- sensitive internal fields

Validate profile image uploads securely.

## Acceptance Criteria
- Users can view/edit their own profile.
- Public profiles expose only intended information.
- Users cannot modify another user's account.
