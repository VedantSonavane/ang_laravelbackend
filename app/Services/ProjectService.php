<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function __construct(private OverdueClient $overdue) {}

    public function listFor(User $user): Collection
    {
        $this->overdue->sync();

        $query = Project::with('creator')->latest();

        if ($user->isAdmin()) {
            return $query->withCount('tasks')->get();
        }

        return $query
            ->whereHas('tasks', fn ($q) => $q->where('assigned_to', $user->id))
            ->withCount(['tasks' => fn ($q) => $q->where('assigned_to', $user->id)])
            ->get();
    }

    public function create(array $data, User $admin): Project
    {
        $project = Project::create($data + ['created_by' => $admin->id]);

        return $project->load('creator');
    }

    public function showFor(Project $project, User $user): Project
    {
        $this->overdue->sync();

        return $project->load([
            'creator',
            'tasks' => function ($q) use ($user) {
                if (! $user->isAdmin()) {
                    $q->where('assigned_to', $user->id);
                }
                $q->orderBy('due_date');
            },
            'tasks.assignee',
        ]);
    }
}
