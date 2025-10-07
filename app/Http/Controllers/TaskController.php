<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Repository\TaskRepositoryInterface;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if pagination is requested
        $paginate = $request->boolean('paginate', false);
        $perPage = $request->integer('per_page', 10);
        $page = $request->integer('page', 1);

        if ($paginate) {
            $tasks = $this->taskRepository->getPaginatedByUser(
                $request->user()->id,
                $request->date,
                $request->status,
                $request->search,
                $request->priority,
                $request->sort,
                $perPage,
                $page
            );

            return TaskResource::collection($tasks);
        }

        // Default behavior - return all tasks
        $tasks = $this->taskRepository->getAllByUser(
            $request->user()->id,
            $request->date,
            $request->status,
            $request->search,
            $request->priority,
            $request->sort
        );

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaskRequest $request)
    {
        $task = $this->taskRepository->create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'date' => $request->date,
            'status' => 'pending',
            'priority' => $request->priority ?? 'medium',
            'order' => $request->order ?? 0,
        ]);

        return new TaskResource($task);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $task = $this->taskRepository->find($id);

        if (! $task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskRequest $request, string $id)
    {
        $task = $this->taskRepository->find($id);

        if (! $task) {
            return response()->json(['message' => 'Task not found'], 404);
        }

        $this->authorize('update', $task);

        $this->taskRepository->update($id, $request->validated());

        return new TaskResource($task->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $task = $this->taskRepository->find($id);

        if (! $task) {
            return response()->json([
                'message' => 'Task not found',
                'code' => 'TASK_NOT_FOUND',
            ], 404);
        }

        $this->authorize('delete', $task);

        // Check if already soft deleted
        if ($task->trashed()) {
            return response()->json([
                'message' => 'Task already deleted',
                'code' => 'TASK_ALREADY_DELETED',
            ], 200);
        }

        $this->taskRepository->delete($id);

        return response()->json([
            'message' => 'Task deleted successfully',
            'code' => 'TASK_DELETED',
        ], 200);
    }

    /**
     * Reorder tasks
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'tasks' => 'required|array',
            'tasks.*.id' => 'required|exists:tasks,id',
            'tasks.*.order' => 'required|integer',
        ]);

        $tasks = $request->input('tasks');

        // Verify user owns all tasks
        foreach ($tasks as $taskData) {
            $task = $this->taskRepository->find($taskData['id']);
            if (!$task) {
                abort(404, 'Task not found');
            }
            $this->authorize('update', $task);
        }

        // Use repository to reorder tasks
        $this->taskRepository->reorder($tasks);

        return response()->json(['message' => 'Tasks reordered successfully']);
    }
}
