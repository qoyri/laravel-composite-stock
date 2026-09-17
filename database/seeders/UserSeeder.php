<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo back-office accounts (password: "password"), documented in the README.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Archie Admin',
            'email' => 'admin@archiecool.test',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Sam Préparateur',
            'email' => 'staff@archiecool.test',
            'role' => UserRole::Staff,
        ]);
    }
}
