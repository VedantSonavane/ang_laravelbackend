<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin()
            || $project->tasks()->where('assigned_to', $user->id)->exists();
    }
}
