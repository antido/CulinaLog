<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * List every permission, what it allows, and which roles have it.
     *
     * Permissions are defined in code (RolesAndPermissionsSeeder) because
     * the application checks them by name, so this page is read-only.
     * Use the Roles page to change which roles have them.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => Permission::query()
                ->with('roles:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Permission $permission) => [
                    'name' => $permission->name,
                    'description' => RolesAndPermissionsSeeder::PERMISSIONS[$permission->name] ?? '',
                    'roles' => $permission->roles->pluck('name')->sort()->values(),
                ]),
        ]);
    }
}
