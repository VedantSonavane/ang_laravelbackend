<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    private const WITH = ['project:id,name', 'assignee'];

    public function __construct(private OverdueClient $overdue) {}

    public function listFor(User $user): Collection
    {
        $this->overdue->sync();

        return Task::with(self::WITH)
            ->when(! $user->isAdmin(), fn ($q) => $q->where('assigned_to', $user->id))
            ->orderBy('due_date')
            ->get();
    }

    public function create(array $data): Task
    {
        $task = Task::create($data + ['status' => 'TODO']);

        return $task->load(self::WITH);
    }

    public function changeStatus(Task $task, string $status, User $actor): Task
    {
        $result = $this->overdue->validateTransition($task->id, $status, $actor->role);

        if (! $result['allowed']) {
            throw new ApiException($result['reason'], 422);
        }

        $task->update(['status' => $status]);

        return $task->load(self::WITH);
    }
}
