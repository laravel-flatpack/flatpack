<?php

declare(strict_types=1);

namespace Flatpack\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Uses the posts table but has no registered policy in the test suite.
 */
final class UnregisteredPolicyPost extends Model
{
    protected $table = 'posts';
}
