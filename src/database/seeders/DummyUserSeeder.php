<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DummyUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(30)
            ->create();
    }
}
