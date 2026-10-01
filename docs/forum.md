# Forum, reports, moderation and notifications

## Forum pages

| Address | Who | What |
| --- | --- | --- |
| `/forum` | Everyone | Categories and active topics; a search box |
| `/forum/c/{slug}` | Everyone | Topics in one category |
| `/forum/topics/{slug}` | Everyone (if visible) | A topic and its replies |
| `/forum/topics/create` | Verified members | New topic form |
| `/forum/topics/{slug}/edit` | The author, or an admin | Edit form |
| `/admin/forum-categories` | Admins | Manage forum categories |

The forum is separate from recipes but follows the same rules for authentication, permissions, moderation and guest handling.

## Topics

- Starting a topic needs a verified account. Guests cannot start topics.
- A topic has a title (5 to 150 characters), a category and a message (10 to 10,000 characters).
- Lists show **pinned** topics first, then the most recently active. A topic becomes "active" when it is created and each time a visible reply is added.
- The view counter goes up at most once per visitor session.
- The author can edit their topic while it is open and visible, and can delete it. Admins can edit and delete any topic.

## Replies

- Members' replies appear at once.
- **Guest replies always wait for a moderator's approval** and are shown as "Anonymous". Switch guest replies off with `guest_forum_replies` in `config/community.php`.
- Replies nest up to three levels, like recipe comments.
- A member can delete their own reply. Moderators hide replies; admins can delete any.

## Locked and hidden topics

| State | Effect |
| --- | --- |
| **Locked** | Nobody can reply or edit, except moderators |
| **Hidden** | Only the author and moderators can see it; everyone else gets "not found" |
| **Pinned** | Stays at the top of its lists |

Moderators change these with the **Pin**, **Lock** and **Hide** buttons on the topic page.

## Reports

Anyone, including guests, can report a recipe, a comment, a forum topic or a forum reply with the **Report** button.

Reasons: Spam, Harassment, Inappropriate Content, Copyright Concern, Misleading Information, Other. "Other" needs a description.

- Only content the public can see can be reported.
- Reporting the same thing again while the first report is pending does not create a second report.
- The author is not told who reported them.

## Moderation area

Open to people with the matching permissions: moderators and admins by default.

| Page | Permission | What it does |
| --- | --- | --- |
| `/moderation` | `dashboard.moderator` | Counts of pending work, recent reports, activity log |
| `/moderation/reports` | `reports.review` | Review reports: **Hide content and resolve**, **Resolve** or **Dismiss** |
| `/moderation/pending` | `dashboard.moderator` | Approve or hide guest comments and guest forum replies |

Hiding the reported content while resolving also needs the permission to moderate that kind of content.

### What moderators can do

| Action | Recipes | Comments | Forum topics | Forum replies |
| --- | :-: | :-: | :-: | :-: |
| Hide and restore | ✓ | ✓ | ✓ | ✓ |
| Approve pending | | ✓ | | ✓ |
| Pin and lock | | | ✓ | |
| Delete | | ✓ | | |

Admins can do all of this and can also edit or delete any recipe, topic or reply.

A normal user who types a moderation address into the browser gets "403 forbidden". The buttons are hidden for them as well, but the server check is what protects the action.

### Moderation log

Every hide, restore, pin, lock and report review is recorded by `App\Services\ModerationService` in the `moderation_logs` table, with who did it and a short copy of the content's title or text. The ten latest entries are on the moderator dashboard.

## Admin dashboard

`/admin` shows total users, total, published and draft recipes, comments, pending comments, forum topics and pending reports, plus the five most recent recipes, comments, users and forum topics. From there admins reach users, roles, permissions, recipes, categories and forum categories.

## Notifications

Members receive in-app notifications, listed at `/notifications`. A dot on the account menu shows that there are unread ones.

| Event | Who is notified |
| --- | --- |
| A comment on a recipe | The recipe's author |
| A reply to a comment | The comment's author |
| A first reaction to a recipe | The recipe's author |
| A reply in a forum topic | The topic's author, and the author of the reply being answered |
| A report was reviewed | The member who reported |

Rules that keep them useful (`App\Services\ActivityNotifier`):

- Nobody is notified about their own action.
- One action sends at most one notification to each person.
- Switching from one reaction to another does not notify again.
- Posts waiting for approval notify nobody until a moderator approves them.

Opening a notification marks it as read and goes to the related page. Members can only see and open their own notifications.

Notifications are stored in the database only. To also send email, add `'mail'` to the `via()` method of a class in `app/Notifications` and write its `toMail()` method.
