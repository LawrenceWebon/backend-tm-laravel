<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create test user
        $user = User::create([
            'name' => 'Matt',
            'email' => 'matt@goteam.com',
            'password' => Hash::make('password'),
        ]);

        // Create sample tasks
        $tasks = [
            [
                'title' => 'Complete project documentation',
                'status' => 'pending',
                'date' => now()->addDays(1),
                'priority' => 'high',
                'order' => 1,
            ],
            [
                'title' => 'Review code changes',
                'status' => 'pending',
                'date' => now()->addDays(2),
                'priority' => 'medium',
                'order' => 2,
            ],
            [
                'title' => 'Update dependencies',
                'status' => 'completed',
                'date' => now()->subDays(1),
                'priority' => 'low',
                'order' => 3,
            ],
            [
                'title' => 'Write unit tests',
                'status' => 'pending',
                'date' => now()->addDays(3),
                'priority' => 'high',
                'order' => 4,
            ],
            [
                'title' => 'Deploy to staging',
                'status' => 'pending',
                'date' => now()->addDays(5),
                'priority' => 'medium',
                'order' => 5,
            ],
        ];

        foreach ($tasks as $taskData) {
            $user->tasks()->create($taskData);
        }
    }
}
