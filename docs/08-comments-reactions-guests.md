# Task 07 — Comments, Replies, Reactions and Guest Interactions

## Goal
Implement recipe comments, threaded replies, reactions, and anonymous guest interaction.

## Comments
Fields:
- recipe_id
- user_id nullable
- parent_id nullable
- guest_name nullable
- body
- status
- timestamps

Authenticated users display their account identity.

Guests display:
- supplied name, or
- Anonymous

## Replies
Use parent_id for threaded comments.
Limit visible nesting to approximately 2–3 levels.

## Reactions
Support:
- like
- love
- wow

Authenticated reactions associate with user_id.

Guest reactions use a secure anonymous guest token.

## Guest Security
Implement:
- CSRF
- rate limiting
- validation
- spam controls
- duplicate prevention
- reasonable content limits

Do not store raw IP addresses unless absolutely necessary. If used for abuse prevention, hash them.

## XSS
Render user-generated content as escaped text.
Do not render arbitrary submitted HTML.

## Tests
Include:
- comment creation
- reply creation
- reaction creation
- guest comments
- guest reactions
- duplicate prevention
- rate limiting
- XSS protection
- ownership checks

## Acceptance Criteria
Guests can participate without an account while being displayed anonymously.
