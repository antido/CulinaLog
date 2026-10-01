# Task 08 — Forum Module

## Goal
Create a complete community forum.

## Forum Categories
Fields:
- name
- slug
- description
- is_active
- sort_order

## Forum Topics
Fields:
- user_id
- category_id
- title
- slug
- body
- status
- is_pinned
- is_locked
- views
- timestamps

## Forum Replies
Fields:
- topic_id
- user_id nullable
- parent_id nullable
- body
- status
- timestamps

## Features
- forum home
- category pages
- topic list
- topic details
- create topic
- edit own topic
- replies
- search
- pagination
- pinned topics
- locked topics

## Guest Participation
Allow guest posting only where configured.

Guest content displays as Anonymous.

Apply stronger rate limits and moderation to guest content.

## Moderator Features
- pin/unpin
- lock/unlock
- hide/restore
- delete where authorized

## Acceptance Criteria
Forum discussions work independently from recipes but use the same authentication, authorization, moderation, and security conventions.
