<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'Admin@123',
            'role' => 'admin',
        ]);

        $users = [];
        foreach ([
            ['Aarav Shah', 'aarav@example.com', 'Aarav@123'],
            ['Meera Iyer', 'meera@example.com', 'Meera@123'],
            ['Rohan Patil', 'rohan@example.com', 'Rohan@123'],
            ['Test User', 'user@example.com', 'User@123'],
        ] as [$name, $email, $password]) {
            $users[$email] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'user',
            ]);
        }

        $projects = [];
        foreach ([
            'web' => ['Website Redesign', 'Refresh the company website'],
            'mobile' => ['Mobile App', 'First release of the mobile app'],
            'marketing' => ['Marketing Campaign', 'Q4 launch campaign'],
            'tools' => ['Internal Tools', 'Admin dashboard and developer tooling'],
            'support' => ['Customer Support Portal', 'Self-service help for customers'],
            'archive' => ['Archive Cleanup', 'Empty project, no tasks yet'],
        ] as $key => [$name, $description]) {
            $projects[$key] = Project::create([
                'name' => $name,
                'description' => $description,
                'created_by' => $admin->id,
            ]);
        }

        // [project, assignee email or null, title, status, priority, due date offset in days from today]
        $tasks = [
            // Website Redesign
            ['web', 'aarav@example.com', 'Design homepage', 'TODO', 'HIGH', 5],
            ['web', 'meera@example.com', 'Write page copy', 'IN_PROGRESS', 'MEDIUM', 2],
            ['web', 'user@example.com', 'Set up repository', 'TODO', 'HIGH', 3],
            ['web', 'user@example.com', 'Write API docs', 'IN_PROGRESS', 'MEDIUM', -2],   // overdue
            ['web', 'rohan@example.com', 'SEO audit', 'DONE', 'LOW', -6],
            ['web', 'aarav@example.com', 'Fix footer links', 'TODO', 'LOW', -1],          // overdue
            ['web', null, 'Choose hosting plan', 'TODO', 'MEDIUM', 7],                    // unassigned

            // Mobile App
            ['mobile', 'rohan@example.com', 'Login screen', 'DONE', 'LOW', -1],
            ['mobile', 'aarav@example.com', 'Push notifications', 'IN_PROGRESS', 'HIGH', -4], // overdue
            ['mobile', 'meera@example.com', 'App store listing', 'TODO', 'LOW', 10],
            ['mobile', 'user@example.com', 'Crash reporting setup', 'IN_PROGRESS', 'HIGH', 1],
            ['mobile', 'rohan@example.com', 'Offline mode', 'TODO', 'MEDIUM', 14],
            ['mobile', 'aarav@example.com', 'Onboarding screens', 'DONE', 'MEDIUM', -3],

            // Marketing Campaign
            ['marketing', 'meera@example.com', 'Launch email draft', 'IN_PROGRESS', 'HIGH', 0],   // due today, NOT overdue
            ['marketing', 'meera@example.com', 'Social media calendar', 'TODO', 'MEDIUM', 4],
            ['marketing', 'aarav@example.com', 'Landing page banner', 'TODO', 'HIGH', -5],        // overdue
            ['marketing', 'user@example.com', 'Competitor research', 'DONE', 'LOW', -8],
            ['marketing', 'rohan@example.com', 'Budget review', 'TODO', 'MEDIUM', 6],

            // Internal Tools (only Rohan and Meera have tasks here)
            ['tools', 'rohan@example.com', 'Build admin dashboard', 'IN_PROGRESS', 'HIGH', 9],
            ['tools', 'rohan@example.com', 'Set up CI pipeline', 'OVERDUE', 'MEDIUM', -2],       // already stored as OVERDUE
            ['tools', 'meera@example.com', 'Write onboarding docs', 'TODO', 'LOW', 12],
            ['tools', 'rohan@example.com', 'Rotate API keys', 'DONE', 'HIGH', -10],

            // Customer Support Portal
            ['support', 'user@example.com', 'Ticket form', 'TODO', 'MEDIUM', 8],
            ['support', 'user@example.com', 'FAQ page', 'DONE', 'LOW', -4],
            ['support', 'aarav@example.com', 'Live chat widget', 'IN_PROGRESS', 'MEDIUM', 3],
            ['support', 'meera@example.com', 'Support email routing', 'TODO', 'HIGH', -1],       // overdue
        ];

        foreach ($tasks as [$project, $email, $title, $status, $priority, $offset]) {
            Task::create([
                'project_id' => $projects[$project]->id,
                'assigned_to' => $email ? $users[$email]->id : null,
                'title' => $title,
                'description' => null,
                'status' => $status,
                'priority' => $priority,
                'due_date' => now()->addDays($offset),
            ]);
        }
    }
}
