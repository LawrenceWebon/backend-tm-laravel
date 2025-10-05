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
        // Get the existing user created by UserSeeder
        $user = User::where('email', 'matt@goteam.com')->first();
        
        if (!$user) {
            $this->command->error('User matt@goteam.com not found. Please run UserSeeder first.');
            return;
        }

        // Create sample tasks for October 2nd to test pagination
        $tasks = [
            [
                'title' => 'Complete project documentation',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 1,
            ],
            [
                'title' => 'Review code changes',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 2,
            ],
            [
                'title' => 'Update dependencies',
                'status' => 'completed',
                'date' => '2025-10-02',
                'priority' => 'low',
                'order' => 3,
            ],
            [
                'title' => 'Write unit tests',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 4,
            ],
            [
                'title' => 'Deploy to staging',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 5,
            ],
            [
                'title' => 'Fix authentication bug',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 6,
            ],
            [
                'title' => 'Optimize database queries',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 7,
            ],
            [
                'title' => 'Update user interface',
                'status' => 'completed',
                'date' => '2025-10-02',
                'priority' => 'low',
                'order' => 8,
            ],
            [
                'title' => 'Implement new feature',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 9,
            ],
            [
                'title' => 'Code review for PR #123',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 10,
            ],
            [
                'title' => 'Update API documentation',
                'status' => 'completed',
                'date' => '2025-10-02',
                'priority' => 'low',
                'order' => 11,
            ],
            [
                'title' => 'Fix responsive design issues',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 12,
            ],
            [
                'title' => 'Add error handling',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 13,
            ],
            [
                'title' => 'Update README file',
                'status' => 'completed',
                'date' => '2025-10-02',
                'priority' => 'low',
                'order' => 14,
            ],
            [
                'title' => 'Refactor legacy code',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 15,
            ],
            [
                'title' => 'Set up monitoring',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 16,
            ],
            [
                'title' => 'Update test coverage',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 17,
            ],
            [
                'title' => 'Clean up unused code',
                'status' => 'completed',
                'date' => '2025-10-02',
                'priority' => 'low',
                'order' => 18,
            ],
            [
                'title' => 'Implement caching strategy',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'high',
                'order' => 19,
            ],
            [
                'title' => 'Update security policies',
                'status' => 'pending',
                'date' => '2025-10-02',
                'priority' => 'medium',
                'order' => 20,
            ],
        ];

        foreach ($tasks as $taskData) {
            $user->tasks()->create($taskData);
        }
    }
}
