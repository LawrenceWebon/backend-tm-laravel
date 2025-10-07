<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Task API Authentication', function () {

    test('unauthenticated user cannot access tasks', function () {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    });

    test('unauthenticated user cannot create tasks', function () {
        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(401);
    });

    test('unauthenticated user cannot update tasks', function () {
        $response = $this->putJson('/api/tasks/1', [
            'title' => 'Updated Task',
            'date' => now()->addDay()->format('Y-m-d'),
        ]);

        $response->assertStatus(401);
    });

    test('unauthenticated user cannot delete tasks', function () {
        $response = $this->deleteJson('/api/tasks/1');

        $response->assertStatus(401);
    });

    test('unauthenticated user cannot reorder tasks', function () {
        $response = $this->putJson('/api/tasks/reorder', [
            'tasks' => [
                ['id' => 1, 'order' => 1],
            ],
        ]);

        $response->assertStatus(401);
    });
});
