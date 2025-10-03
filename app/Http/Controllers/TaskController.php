<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
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
        $tasks = $this->taskRepository->getAllByUser(
            $request->user()->id,
            $request->date,
            $request->status,
            $request->search
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
        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaskRequest $request, string $id)
    {
        $task = $this->taskRepository->find($id);
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
        
        if (!$task) {
            return response()->json(['message' => 'Task not found'], 404);
        }
        
        $this->authorize('delete', $task);

        $this->taskRepository->delete($id);

        return response()->json(null, 204);
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

        $this->taskRepository->reorder($request->tasks);

        return response()->json(['message' => 'Tasks reordered successfully']);
    }
}
