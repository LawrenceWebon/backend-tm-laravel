<?php

namespace App\Repository\Eloquent;

use App\Models\Task;
use App\Repository\TaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get all tasks for a user with optional filters
     */
    public function getAllByUser(int $userId, ?string $date = null, ?string $status = null, ?string $search = null, ?string $priority = null, ?string $sort = null): Collection
    {
        $query = Task::where('user_id', $userId);

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%']);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        // Apply sorting
        if ($sort) {
            switch ($sort) {
                case 'priority':
                    $query->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END");
                    break;
                case 'title':
                    $query->orderBy('title');
                    break;
                case 'order':
                default:
                    $query->orderBy('order');
                    break;
            }
        } else {
            $query->orderBy('order');
        }

        return $query->get();
    }

    /**
     * Get paginated tasks for a user with optional filters
     */
    public function getPaginatedByUser(int $userId, ?string $date = null, ?string $status = null, ?string $search = null, ?string $priority = null, ?string $sort = null, int $perPage = 10, int $page = 1): LengthAwarePaginator
    {
        $query = Task::where('user_id', $userId);

        if ($date) {
            $query->whereDate('date', $date);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($search).'%']);
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        // Apply sorting
        if ($sort) {
            switch ($sort) {
                case 'priority':
                    $query->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END");
                    break;
                case 'title':
                    $query->orderBy('title');
                    break;
                case 'order':
                default:
                    $query->orderBy('order');
                    break;
            }
        } else {
            $query->orderBy('order');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
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
        return Task::withTrashed()->find($id);
    }

    /**
     * Update a task
     */
    public function update(int $id, array $data): bool
    {
        $task = Task::find($id);

        if (! $task) {
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

        if (! $task) {
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
