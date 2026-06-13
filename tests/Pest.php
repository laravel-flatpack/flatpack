<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Set the currently logged in user for the application.
 */
function actingAs(Authenticatable $user, ?string $driver = null)
{
    return test()->actingAs($user, $driver);
}
