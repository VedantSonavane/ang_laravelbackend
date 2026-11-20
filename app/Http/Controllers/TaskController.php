<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Resources\TaskResource;
use App\Http\Responses\ApiResponse;
use App\Models\Task;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    public function __construct(private TaskService $tasks) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            TaskResource::collection($this->tasks->listFor($request->user()))
        );
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->tasks->create($request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task created', 201);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('update', $task);

        $task = $this->tasks->update($task, $request->validated());

        return ApiResponse::success(new TaskResource($task), 'Task updated');
    }

    public function destroy(Task $task): JsonResponse
    {
        Gate::authorize('delete', $task);

        $this->tasks->delete($task);

        return ApiResponse::success(null, 'Task deleted');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('updateStatus', $task);

        $task = $this->tasks->changeStatus($task, $request->validated()['status'], $request->user());

        return ApiResponse::success(new TaskResource($task), 'Status updated');
    }
}
