<?php

/*
 * Settings for CulinaLog's community features.
 */
return [

    /*
     * The largest image a person may upload, in kilobytes (5120 KB = 5 MB).
     * Uploaded images are also resized and re-saved by App\Services\ImageService.
     */
    'max_image_kilobytes' => 5120,

    /*
     * Whether people without an account may comment on and react to recipes.
     * Guests are shown as "Anonymous" unless they type a display name.
     */
    'guest_comments' => true,
    'guest_reactions' => true,

    /*
     * Whether guests may reply in the forum. Guest replies always wait for a
     * moderator's approval and are shown as "Anonymous". Starting a topic
     * always needs an account.
     */
    'guest_forum_replies' => true,

    /*
     * Whether guests may report content to the moderators.
     */
    'guest_reports' => true,

    /*
     * How deep replies are shown. 3 means: a comment, a reply to it, and a
     * reply to that reply. Deeper replies are attached at the last level.
     */
    'comment_depth' => 3,

    /*
     * A person cannot post the same text on the same recipe again within
     * this many minutes.
     */
    'duplicate_window_minutes' => 10,

];
