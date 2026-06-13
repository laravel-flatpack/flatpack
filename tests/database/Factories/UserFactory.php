<?php

declare(strict_types=1);

namespace Flatpack\Tests\Database\Factories;

use Flatpack\Tests\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
final class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => 'Test User',
            'email' => 'user-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
        ];
    }
}
