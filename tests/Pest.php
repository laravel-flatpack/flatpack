<?php

use Flatpack\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Set the currently logged in user for the application.
 *
 * @return TestCase
 */
function actingAs(Authenticatable $user, string $driver = null)
{
    return test()->actingAs($user, $driver);
}

uses(TestCase::class)->in(__DIR__);
