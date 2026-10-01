# Task 09 — Reporting, Moderation and Notifications

## Goal
Create content reporting and moderation workflows.

## Reportable Content
- recipes
- comments
- forum topics
- forum replies

## Report Fields
- user_id nullable
- reportable_type
- reportable_id
- reason
- description
- status
- reviewed_by
- reviewed_at
- timestamps

## Reasons
- Spam
- Harassment
- Inappropriate Content
- Copyright Concern
- Misleading Information
- Other

## Moderation
Moderators can:
- review reports
- hide content
- restore content
- lock topics
- unlock topics
- pin topics
- resolve reports

Admins can manage all moderation functionality.

## Notifications
Use Laravel Notifications for:
- recipe comments
- comment replies
- recipe reactions
- forum replies
- report review

Do not send unnecessary notifications.

## Security
Check authorization for every moderation action.

## Acceptance Criteria
A normal user cannot access moderation actions simply by manually visiting a URL.
