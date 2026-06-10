<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Auth;

use Illuminate\Support\Facades\Auth;
use Xakki\FileUploader\Contracts\UserResolver;

/**
 * Core UserResolver over Laravel's Auth. Role checks support spatie-style
 * (hasAnyRole / hasRole) and getRoleNames() user models.
 */
final class LaravelUserResolver implements UserResolver
{
    public function id(): ?string
    {
        $id = Auth::id();

        return $id === null ? null : (string) $id;
    }

    public function hasAnyRole(array $roles): bool
    {
        if (! $roles) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return (bool) $user->hasAnyRole($roles);
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        if (method_exists($user, 'getRoleNames')) {
            $roleNames = $user->getRoleNames();
            if (is_iterable($roleNames)) {
                foreach ($roleNames as $roleName) {
                    if (in_array((string) $roleName, $roles, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
