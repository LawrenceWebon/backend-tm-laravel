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
                        'updated_at'
                    ]
                ]
            ]);
        
        // Should only return 3 tasks (user's own tasks)
        expect($response->json('data'))->toHaveCount(3);
    });

    test('can filter tasks by date', function () {
        $today = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');
        
        Task::factory()->create([
            'user_id' => $this->user->id,
            'date' => $today
        ]);
        
        Task::factory()->create([
            'user_id' => $this->user->id,
            'date' => $tomorrow
        ]);
        
        $response = $this->getJson("/api/tasks?date={$today}");
        
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.date'))->toContain($today);
    });

    test('can filter tasks by status', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);
        
        Task::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);
        
        $response = $this->getJson('/api/tasks?status=pending');
        
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.status'))->toBe('pending');
    });

    test('can search tasks by title', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Important meeting'
        ]);
        
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Buy groceries'
        ]);
        
        $response = $this->getJson('/api/tasks?search=meeting');
        
        $response->assertStatus(200);
        expect($response->json('data'))->toHaveCount(1);
        expect($response->json('data.0.title'))->toContain('meeting');
    });

    test('can create a new task', function () {
        $taskData = [
            'title' => 'Test Task',
            'date' => now()->addDay()->format('Y-m-d'),
            'priority' => 'high',
            'order' => 1
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
                    'updated_at'
                ]
            ]);
        
        expect($response->json('data.title'))->toBe('Test Task');
        expect($response->json('data.status'))->toBe('pending');
        expect($response->json('data.priority'))->toBe('high');
        
        // Verify task was created in database
        $this->assertDatabaseHas('tasks', [
            'user_id' => $this->user->id,
            'title' => 'Test Task',
            'status' => 'pending'
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
                    'updated_at'
                ]
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
            'status' => 'pending'
        ]);
        
        $updateData = [
            'title' => 'Updated Title',
            'status' => 'completed',
            'date' => $task->date->format('Y-m-d')
        ];
        
        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);
        
        $response->assertStatus(200);
        expect($response->json('data.title'))->toBe('Updated Title');
        expect($response->json('data.status'))->toBe('completed');
        
        // Verify task was updated in database
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'completed'
        ]);
    });

    test('cannot update task belonging to another user', function () {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create(['user_id' => $otherUser->id]);
        
        $updateData = [
            'title' => 'Hacked Title',
            'date' => now()->addDay()->format('Y-m-d')
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
            'id' => $task->id
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
            'order' => 1
        ]);
        
        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'order' => 2
        ]);
        
        $reorderData = [
            'tasks' => [
                ['id' => $task1->id, 'order' => 2],
                ['id' => $task2->id, 'order' => 1]
            ]
        ];
        
        $response = $this->putJson('/api/tasks/reorder', $reorderData);
        
        $response->assertStatus(200)
            ->assertJson(['message' => 'Tasks reordered successfully']);
        
        // Verify tasks were reordered in database
        $this->assertDatabaseHas('tasks', [
            'id' => $task1->id,
            'order' => 2
        ]);
        
        $this->assertDatabaseHas('tasks', [
            'id' => $task2->id,
            'order' => 1
        ]);
    });

    test('reorder validation fails with invalid data', function () {
        $reorderData = [
            'tasks' => [
                ['id' => 999, 'order' => 1] // Non-existent task ID
            ]
        ];
        
        $response = $this->putJson('/api/tasks/reorder', $reorderData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasks.0.id']);
    });
});

describe('Task API Validation', function () {
    
    test('create task requires title', function () {
        $response = $this->postJson('/api/tasks', [
            'date' => now()->addDay()->format('Y-m-d')
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    test('create task requires date', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });

    test('create task validates priority values', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'date' => now()->addDay()->format('Y-m-d'),
            'priority' => 'invalid'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    });

    test('update task validates status values', function () {
        $task = Task::factory()->create(['user_id' => $this->user->id]);
        
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'invalid'
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
                'priority' => $priority
            ]);
            
            $response->assertStatus(201);
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
            'date' => now()->addDay()->format('Y-m-d')
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
            'date' => now()->addDay()->format('Y-m-d')
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
            'order' => 5
        ]);
        
        $response->assertStatus(201);
        expect($response->json('data.priority'))->toBe('high');
        expect($response->json('data.order'))->toBe(5);
    });
});
