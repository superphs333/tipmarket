<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class DummyUserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()
            ->count(30)
            ->create();
    }
}
