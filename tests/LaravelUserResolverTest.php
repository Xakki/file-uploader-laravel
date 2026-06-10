<?php

declare(strict_types=1);

namespace Xakki\LaravelFileUploader\Tests;

use Illuminate\Auth\GenericUser;
use Xakki\LaravelFileUploader\Auth\LaravelUserResolver;

class LaravelUserResolverTest extends TestCase
{
    public function test_id_is_null_for_a_guest(): void
    {
        self::assertNull((new LaravelUserResolver)->id());
    }

    public function test_id_returns_authenticated_user_id_as_string(): void
    {
        $this->actingAs(new GenericUser(['id' => 42]));

        self::assertSame('42', (new LaravelUserResolver)->id());
    }

    public function test_has_any_role_is_false_for_guest_or_empty_roles(): void
    {
        $resolver = new LaravelUserResolver;

        self::assertFalse($resolver->hasAnyRole([]));
        self::assertFalse($resolver->hasAnyRole(['admin']));
    }

    public function test_has_any_role_delegates_to_the_user_model(): void
    {
        $user = new class(['id' => 7]) extends GenericUser
        {
            /**
             * @param  string[]  $roles
             */
            public function hasAnyRole(array $roles): bool
            {
                return in_array('admin', $roles, true);
            }
        };
        $this->actingAs($user);

        $resolver = new LaravelUserResolver;

        self::assertTrue($resolver->hasAnyRole(['admin', 'editor']));
        self::assertFalse($resolver->hasAnyRole(['editor']));
    }
}
