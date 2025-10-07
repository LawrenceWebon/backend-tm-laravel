<?php

namespace App\Repository;

use App\Models\Task;
use Illuminate\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * Get all tasks for a user with optional filters
     */
    public function getAllByUser(int $userId, ?string $date = null, ?string $status = null, ?string $search = null, ?string $priority = null, ?string $sort = null);

    /**
     * Get paginated tasks for a user with optional filters
     */
    public function getPaginatedByUser(int $userId, ?string $date = null, ?string $status = null, ?string $search = null, ?string $priority = null, ?string $sort = null, int $perPage = 10, int $page = 1): LengthAwarePaginator;

    /**
     * Create a new task
     */
    public function create(array $data): Task;

    /**
     * Find a task by ID
     */
    public function find(int $id): ?Task;

    /**
     * Update a task
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a task
     */
    public function delete(int $id): bool;

    /**
     * Reorder tasks
     */
    public function reorder(array $tasks): bool;
}
