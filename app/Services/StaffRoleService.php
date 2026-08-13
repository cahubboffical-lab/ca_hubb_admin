<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;

final class StaffRoleService
{
    public function syncCustomRole(User $user, int $roleId): void
    {
        $role = Role::query()
            ->where('custom_role', 1)
            ->findOrFail($roleId);

        $user->syncRoles([$role]);
    }
}
