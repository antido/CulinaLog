<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Every permission in the application, and what it allows.
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        'users.view' => 'See the list of users in the admin area',
        'users.manage' => 'Change user roles and delete users',
        'roles.manage' => 'Create roles and choose their permissions',
        'permissions.view' => 'See the list of permissions',
        'categories.manage' => 'Create, edit and delete recipe categories',
        'recipes.create' => 'Publish and manage their own recipes',
        'recipes.moderate' => 'Hide and restore any recipe',
        'recipes.manage' => 'Edit and delete any recipe',
        'comments.create' => 'Comment on recipes',
        'comments.moderate' => 'Approve, hide and restore any comment',
        'comments.delete' => 'Delete any comment',
        'reactions.create' => 'React to recipes',
        'forum.create' => 'Start forum topics and reply to them',
        'forum.moderate' => 'Pin, lock, hide and restore forum content',
        'forum.manage' => 'Manage forum categories and delete forum content',
        'reports.create' => 'Report content',
        'reports.review' => 'Review and resolve reports',
        'dashboard.admin' => 'Open the admin dashboard',
        'dashboard.moderator' => 'Open the moderator dashboard',
    ];

    /**
     * What an ordinary member may do.
     *
     * @var list<string>
     */
    public const USER_PERMISSIONS = [
        'recipes.create',
        'comments.create',
        'reactions.create',
        'forum.create',
        'reports.create',
    ];

    /**
     * What a moderator may do in addition to an ordinary member.
     *
     * @var list<string>
     */
    public const MODERATOR_PERMISSIONS = [
        'recipes.moderate',
        'comments.moderate',
        'comments.delete',
        'forum.moderate',
        'reports.review',
        'dashboard.moderator',
    ];

    /**
     * Create the permissions and the three roles. Safe to run more than once.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_keys(self::PERMISSIONS) as $name) {
            Permission::findOrCreate($name);
        }

        Role::findOrCreate('user')
            ->syncPermissions(self::USER_PERMISSIONS);

        Role::findOrCreate('moderator')
            ->syncPermissions([...self::USER_PERMISSIONS, ...self::MODERATOR_PERMISSIONS]);

        // Admins receive every permission explicitly, so permission checks
        // work the same way for all three roles.
        Role::findOrCreate('admin')
            ->syncPermissions(array_keys(self::PERMISSIONS));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
