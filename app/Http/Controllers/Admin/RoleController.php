<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * The roles the application depends on. They cannot be deleted.
     */
    private const BUILT_IN_ROLES = ['admin', 'moderator', 'user'];

    /**
     * List the roles with their permissions.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Roles/Index', [
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'users_count' => $role->users_count,
                    'permissions' => $role->permissions->pluck('name'),
                    'is_built_in' => in_array($role->name, self::BUILT_IN_ROLES, true),
                    // The admin role always has every permission, so that
                    // an admin cannot lock all admins out by mistake.
                    'is_locked' => $role->name === 'admin',
                ]),
            'permissions' => collect(RolesAndPermissionsSeeder::PERMISSIONS)
                ->map(fn (string $description, string $name) => ['name' => $name, 'description' => $description])
                ->values(),
        ]);
    }

    /**
     * Create a new role with the chosen permissions.
     */
    public function store(RoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions'));

        return back()->with('success', "Role \"{$role->name}\" was created.");
    }

    /**
     * Change which permissions a role has.
     */
    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'admin') {
            return back()->with('error', 'The admin role always has every permission and cannot be changed.');
        }

        $role->syncPermissions($request->validated('permissions'));

        return back()->with('success', "Permissions for \"{$role->name}\" were saved.");
    }

    /**
     * Delete a custom role that nobody has.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_unless($request->user()->can('roles.manage'), 403);

        if (in_array($role->name, self::BUILT_IN_ROLES, true)) {
            return back()->with('error', "The built-in role \"{$role->name}\" cannot be deleted.");
        }

        if ($role->users()->exists()) {
            return back()->with('error', "\"{$role->name}\" still has users. Give them another role first.");
        }

        $role->delete();

        return back()->with('success', "Role \"{$role->name}\" was deleted.");
    }
}
