<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'user')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_date' => ['required', 'date'],
        ];
    }
}
