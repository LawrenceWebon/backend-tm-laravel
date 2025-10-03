<?php

namespace App\Repository\Eloquent;

use App\Models\Task;
use App\Repository\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks for a user with optional filters
     */
    public function getAllByUser(int $userId, ?string $date = null, ?string $status = null, ?string $search = null): Collection
    {
        $query = Task::where('user_id', $userId);

        if ($date) {
            $query->where('date', $date);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        return $query->orderBy('order')->get();
    }

    /**
     * Create a new task
     */
    public function create(array $data): Task
    {
        return Task::create($data);
    }

    /**
     * Find a task by ID
     */
    public function find(int $id): ?Task
    {
        return Task::find($id);
    }

    /**
     * Update a task
     */
    public function update(int $id, array $data): bool
    {
        $task = Task::find($id);
        
        if (!$task) {
            return false;
        }

        return $task->update($data);
    }

    /**
     * Delete a task
     */
    public function delete(int $id): bool
    {
        $task = Task::find($id);
        
        if (!$task) {
            return false;
        }

        return $task->delete();
    }

    /**
     * Reorder tasks
     */
    public function reorder(array $tasks): bool
    {
        foreach ($tasks as $taskData) {
            Task::where('id', $taskData['id'])
                ->update(['order' => $taskData['order']]);
        }

        return true;
    }
}
