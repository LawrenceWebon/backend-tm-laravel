<?php

use App\Models\Task;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

describe('Task API Endpoints', function () {

    test('can list all tasks for authenticated user', function () {
        // Create some tasks for the user
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        // Create tasks for another user (should not appear)
        $otherUser = User::factory()->create();
        Task::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                        'date',
                        'priority',
                        'order',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        // Should only return 3 tasks (user's own tasks)
        expect($response->json('data'))->toHaveCount(3);
    });

    test('can filter tasks by date', function () {
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        Task::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today,
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'date' => $tomorrow,
        ]);

        $response = $this->getJson("/api/tasks?date={$today}");

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.date'))->toContain($today);
    });

    test('can filter tasks by status', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->getJson('/api/tasks?status=pending');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.status'))->toBe('pending');
    });

    test('can search tasks by title', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important meeting',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Buy groceries',
        ]);

        $response = $this->getJson('/api/tasks?search=meeting');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toContain('meeting');
    });

    test('can filter tasks by priority', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'medium',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
        ]);

        $response = $this->getJson('/api/tasks?priority=high');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.priority'))->toBe('high');
    });

    test('can sort tasks by priority', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
            'title' => 'Low Priority Task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
            'title' => 'High Priority Task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'medium',
            'title' => 'Medium Priority Task',
        ]);

        $response = $this->getJson('/api/tasks?sort=priority');

        $response->assertStatus(200);
        $tasks = $response->json('data');

        // Should be sorted by priority: high, medium, low
        expect($tasks[0]['priority'])->toBe('high');
        expect($tasks[1]['priority'])->toBe('medium');
        expect($tasks[2]['priority'])->toBe('low');
    });

    test('can sort tasks by title alphabetically', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Zebra Task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Apple Task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Banana Task',
        ]);

        $response = $this->getJson('/api/tasks?sort=title');

        $response->assertStatus(200);
        $tasks = $response->json('data');

        // Should be sorted alphabetically
        expect($tasks[0]['title'])->toBe('Apple Task');
        expect($tasks[1]['title'])->toBe('Banana Task');
        expect($tasks[2]['title'])->toBe('Zebra Task');
    });

    test('can sort tasks by custom order', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'order' => 3,
            'title' => 'Third Task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'order' => 1,
            'title' => 'First Task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'order' => 2,
            'title' => 'Second Task',
        ]);

        $response = $this->getJson('/api/tasks?sort=order');

        $response->assertStatus(200);
        $tasks = $response->json('data');

        // Should be sorted by order
        expect($tasks[0]['title'])->toBe('First Task');
        expect($tasks[1]['title'])->toBe('Second Task');
        expect($tasks[2]['title'])->toBe('Third Task');
    });

    test('can create a new task', function () {
        $taskData = [
            'title' => 'Test Task',
            'date' => now()->addDay()->format('Y-m-d'),
            'priority' => 'high',
            'order' => 1,
        ];

        $response = $this->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status',
                    'date',
                    'priority',
                    'order',
                    'created_at',
                    'updated_at',
                ],
            ]);

        expect($response->json('data.title'))->toBe('Test Task');
        expect($response->json('data.status'))->toBe('pending');
        expect($response->json('data.priority'))->toBe('high');

        // Verify task was created in database
        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Test Task',
            'status' => 'pending',
        ]);
    });

    test('can show a specific task', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'status',
                    'date',
                    'priority',
                    'order',
                    'created_at',
                    'updated_at',
                ],
            ]);

        expect($response->json('data.id'))->toBe($task->id);
    });

    test('cannot show task belonging to another user', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    });

    test('can update a task', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'status' => 'pending',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'completed',
            'date' => $task->date->format('Y-m-d'),
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200);
        expect($response->json('data.title'))->toBe('Updated Title');
        expect($response->json('data.status'))->toBe('completed');

        // Verify task was updated in database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'completed',
        ]);
    });

    test('can update task priority', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'medium',
        ]);

        $updateData = [
            'priority' => 'high',
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200);
        expect($response->json('data.priority'))->toBe('high');

        // Verify priority was updated in database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => 'high',
        ]);
    });

    test('can update task priority from high to low', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
        ]);

        $updateData = [
            'priority' => 'low',
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200);
        expect($response->json('data.priority'))->toBe('low');

        // Verify priority was updated in database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'priority' => 'low',
        ]);
    });

    test('cannot update task belonging to another user', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $updateData = [
            'title' => 'Hacked Title',
            'date' => now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(403);
    });

    test('can delete a task', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);

        // Verify task was deleted from database
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    });

    test('cannot delete task belonging to another user', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    });

    test('can reorder tasks', function () {
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'order' => 1,
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'order' => 2,
        ]);

        $reorderData = [
            'tasks' => [
                ['id' => $task1->id, 'order' => 2],
                ['id' => $task2->id, 'order' => 1],
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Tasks reordered successfully']);

        // Verify tasks were reordered in database
        $this->assertDatabaseHas('tasks', [
            'id' => $task1->id,
            'order' => 2,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'order' => 1,
        ]);
    });

    test('reorder validation fails with invalid data', function () {
        $reorderData = [
            'tasks' => [
                ['id' => 999, 'order' => 1], // Non-existent task ID
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.id']);
    });

    test('cannot reorder tasks belonging to another user', function () {
        $otherUser = User::factory()->create();
        $otherUserTask = Task::factory()->create(['user_id' => $otherUser->id, 'order' => 1]);

        $myTask = Task::factory()->create(['user_id' => $this->user->id, 'order' => 2]);

        $reorderData = [
            'tasks' => [
                ['id' => $myTask->id, 'order' => 1],
                ['id' => $otherUserTask->id, 'order' => 2], // This should fail
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(403);
    });

    test('reorder validation requires tasks array', function () {
        $response = $this->putJson('/api/tasks/reorder', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks']);
    });

    test('reorder validation requires order field', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $reorderData = [
            'tasks' => [
                ['id' => $task->id], // Missing order field
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.order']);
    });

    test('reorder validation requires id field', function () {
        $reorderData = [
            'tasks' => [
                ['order' => 1], // Missing id field
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.id']);
    });

    test('reorder validation requires integer order values', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $reorderData = [
            'tasks' => [
                ['id' => $task->id, 'order' => 'not-a-number'],
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.order']);
    });

    test('can reorder multiple tasks in complex order', function () {
        $task1 = Task::factory()->create(['user_id' => $this->user->id, 'order' => 1, 'title' => 'Task 1']);
        $task2 = Task::factory()->create(['user_id' => $this->user->id, 'order' => 2, 'title' => 'Task 2']);
        $task3 = Task::factory()->create(['user_id' => $this->user->id, 'order' => 3, 'title' => 'Task 3']);
        $task4 = Task::factory()->create(['user_id' => $this->user->id, 'order' => 4, 'title' => 'Task 4']);

        // Reorder: 3, 1, 4, 2
        $reorderData = [
            'tasks' => [
                ['id' => $task3->id, 'order' => 0],
                ['id' => $task1->id, 'order' => 1],
                ['id' => $task4->id, 'order' => 2],
                ['id' => $task2->id, 'order' => 3],
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Tasks reordered successfully']);

        // Verify the new order in database
        $this->assertDatabaseHas('tasks', ['id' => $task3->id, 'order' => 0]);
        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'order' => 1]);
        $this->assertDatabaseHas('tasks', ['id' => $task4->id, 'order' => 2]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'order' => 3]);
    });

    test('reorder handles duplicate order values correctly', function () {
        $task1 = Task::factory()->create(['user_id' => $this->user->id, 'order' => 1]);
        $task2 = Task::factory()->create(['user_id' => $this->user->id, 'order' => 2]);

        // Both tasks with same order value
        $reorderData = [
            'tasks' => [
                ['id' => $task1->id, 'order' => 5],
                ['id' => $task2->id, 'order' => 5],
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(200);

        // Both should be updated to order 5
        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'order' => 5]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'order' => 5]);
    });

    test('reorder with empty tasks array returns validation error', function () {
        $reorderData = [
            'tasks' => [],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks']);
    });

    test('reorder preserves task data integrity', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
            'status' => 'pending',
            'priority' => 'high',
            'date' => now()->format('Y-m-d'),
            'order' => 1,
        ]);

        $reorderData = [
            'tasks' => [
                ['id' => $task->id, 'order' => 5],
            ],
        ];

        $response = $this->putJson('/api/tasks/reorder', $reorderData);

        $response->assertStatus(200);

        // Verify only order changed, other data preserved
        $updatedTask = Task::find($task->id);
        expect($updatedTask->order)->toBe(5);
        expect($updatedTask->title)->toBe('Original Title');
        expect($updatedTask->status)->toBe('pending');
        expect($updatedTask->priority)->toBe('high');
    });
});

describe('Task API Validation', function () {

    test('create task requires title', function () {
        $response = $this->postJson('/api/tasks', [
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('create task requires date', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('create task validates priority values', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'date' => now()->addDay()->format('Y-m-d'),
            'priority' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    });

    test('update task validates status values', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    });

    test('create task with valid priority values', function () {
        $priorities = ['low', 'medium', 'high'];

        foreach ($priorities as $priority) {
            $response = $this->postJson('/api/tasks', [
                'title' => "Test Task {$priority}",
                'date' => now()->addDay()->format('Y-m-d'),
                'priority' => $priority,
            ]);

            $response->assertStatus(201);
        }
    });

    test('update task validates priority values', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'priority' => 'invalid_priority',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    });

    test('priority validation accepts only enum values', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        // Test invalid priority values
        $invalidPriorities = ['urgent', 'normal', '1', '2', '3', '', null];

        foreach ($invalidPriorities as $invalidPriority) {
            $response = $this->putJson("/api/tasks/{$task->id}", [
                'priority' => $invalidPriority,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['priority']);
        }
    });

    test('priority validation accepts valid enum values', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);

        $validPriorities = ['low', 'medium', 'high'];

        foreach ($validPriorities as $priority) {
            $response = $this->putJson("/api/tasks/{$task->id}", [
                'priority' => $priority,
            ]);

            $response->assertStatus(200);
            expect($response->json('data.priority'))->toBe($priority);
        }
    });
});

describe('Task API Edge Cases', function () {

    test('can handle empty task list', function () {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJson(['data' => []]);
    });

    test('can handle non-existent task show', function () {
        $response = $this->getJson('/api/tasks/999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found']);
    });

    test('can handle non-existent task update', function () {
        $response = $this->putJson('/api/tasks/999', [
            'title' => 'Updated Task',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found']);
    });

    test('can handle non-existent task delete', function () {
        $response = $this->deleteJson('/api/tasks/999');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Task not found']);
    });

    test('can create task with minimal required data', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Minimal Task',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
        expect($response->json('data.priority'))->toBe('medium');
        expect($response->json('data.order'))->toBe(0);
    });

    test('can create task with all optional fields', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Complete Task',
            'date' => now()->addDay()->format('Y-m-d'),
            'priority' => 'high',
            'order' => 5,
        ]);

        $response->assertStatus(201);
        expect($response->json('data.priority'))->toBe('high');
        expect($response->json('data.order'))->toBe(5);
    });

    test('can filter tasks by multiple criteria including priority', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
            'status' => 'pending',
            'date' => now()->format('Y-m-d'),
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
            'status' => 'completed',
            'date' => now()->format('Y-m-d'),
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
            'status' => 'pending',
            'date' => now()->format('Y-m-d'),
        ]);

        $response = $this->getJson('/api/tasks?priority=high&status=pending');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.priority'))->toBe('high');
        expect($response->json('data.0.status'))->toBe('pending');
    });

    test('can combine search and priority filtering', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important meeting',
            'priority' => 'high',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important call',
            'priority' => 'low',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Regular task',
            'priority' => 'high',
        ]);

        $response = $this->getJson('/api/tasks?search=important&priority=high');

        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toContain('Important');
        expect($response->json('data.0.priority'))->toBe('high');
    });

    test('can sort tasks by priority with mixed statuses', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
            'status' => 'completed',
            'title' => 'Low Completed',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
            'status' => 'pending',
            'title' => 'High Pending',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 'medium',
            'status' => 'completed',
            'title' => 'Medium Completed',
        ]);

        $response = $this->getJson('/api/tasks?sort=priority');

        $response->assertStatus(200);
        $tasks = $response->json('data');

        // Should be sorted by priority regardless of status
        expect($tasks[0]['priority'])->toBe('high');
        expect($tasks[1]['priority'])->toBe('medium');
        expect($tasks[2]['priority'])->toBe('low');
    });
});
