<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for Laravel
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@chapchap.com',
            'password' => bcrypt('password'),
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Call all seeders in order
        $this->call([
            MemberSeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            TransactionSeeder::class,
            FeedbackSeeder::class,
        ]);

        $this->command->info('Database seeding completed successfully!');
    }
}
