<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Str;

class WorkModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedProjects();
        $this->seedTasks();
        $this->seedTaskTemplates();
    }

    /**
     * Seed sample projects
     */
    private function seedProjects(): void
    {
        $organization = Organization::where('code', '1OFFICE')->first();
        $itDept = Department::where('code', 'IT')->first();
        $projectManager = User::where('username', 'itmanager')->first();

        // Create sample projects
        $projects = [
            [
                'uuid' => Str::uuid(),
                'code' => 'PROJ001',
                'name' => '1Office Platform Development',
                'description' => 'Development of the main 1Office platform with all modules',
                'status' => 'active',
                'priority' => 'high',
                'start_date' => now()->subMonths(2),
                'end_date' => now()->addMonths(4),
                'budget' => 500000.00,
                'progress' => 35.50,
                'progress_calculation' => 'auto_by_tasks',
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'project_manager_id' => $projectManager->id,
                'created_by' => $projectManager->id,
                'settings' => [
                    'auto_assign_tasks' => true,
                    'require_time_tracking' => true,
                    'notification_frequency' => 'daily',
                ],
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'PROJ002',
                'name' => 'Mobile App Development',
                'description' => 'Development of mobile applications for iOS and Android',
                'status' => 'planning',
                'priority' => 'normal',
                'start_date' => now()->addMonth(),
                'end_date' => now()->addMonths(6),
                'budget' => 200000.00,
                'progress' => 0,
                'progress_calculation' => 'auto_by_tasks',
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'project_manager_id' => $projectManager->id,
                'created_by' => $projectManager->id,
            ],
        ];

        foreach ($projects as $projectData) {
            \DB::table('projects')->insert(array_merge($projectData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Seed sample tasks
     */
    private function seedTasks(): void
    {
        $organization = Organization::where('code', '1OFFICE')->first();
        $itDept = Department::where('code', 'IT')->first();
        $project = \DB::table('projects')->where('code', 'PROJ001')->first();
        $developer1 = User::where('username', 'developer1')->first();
        $developer2 = User::where('username', 'developer2')->first();
        $itManager = User::where('username', 'itmanager')->first();

        $tasks = [
            [
                'uuid' => Str::uuid(),
                'code' => 'TASK001',
                'title' => 'Setup Authentication System',
                'description' => 'Implement Laravel Sanctum authentication with role-based access control',
                'status' => 'completed',
                'priority' => 'high',
                'progress_type' => 'manual',
                'progress' => 100,
                'start_time' => now()->subWeeks(3),
                'end_time' => now()->subWeeks(2),
                'actual_start_time' => now()->subWeeks(3),
                'actual_end_time' => now()->subWeeks(2),
                'estimated_hours' => 40,
                'actual_hours' => 38,
                'is_milestone' => true,
                'project_id' => $project->id,
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'assigned_to' => $developer1->id,
                'created_by' => $itManager->id,
                'tags' => json_encode(['authentication', 'security', 'backend']),
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'TASK002',
                'title' => 'Design Database Schema for Work Module',
                'description' => 'Create comprehensive database schema for task and project management',
                'status' => 'in_progress',
                'priority' => 'high',
                'progress_type' => 'manual',
                'progress' => 75,
                'start_time' => now()->subWeeks(2),
                'end_time' => now()->addDays(3),
                'actual_start_time' => now()->subWeeks(2),
                'estimated_hours' => 32,
                'actual_hours' => 24,
                'is_milestone' => false,
                'project_id' => $project->id,
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'assigned_to' => $developer2->id,
                'created_by' => $itManager->id,
                'tags' => json_encode(['database', 'schema', 'work-module']),
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'TASK003',
                'title' => 'Implement Task Management API',
                'description' => 'Create RESTful API endpoints for task CRUD operations',
                'status' => 'todo',
                'priority' => 'normal',
                'progress_type' => 'auto_by_assignee',
                'progress' => 0,
                'start_time' => now()->addDays(5),
                'end_time' => now()->addWeeks(2),
                'estimated_hours' => 48,
                'actual_hours' => 0,
                'is_milestone' => false,
                'project_id' => $project->id,
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'assigned_to' => $developer1->id,
                'created_by' => $itManager->id,
                'tags' => json_encode(['api', 'backend', 'tasks']),
                'require_description_on_complete' => true,
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'TASK004',
                'title' => 'Create Project Dashboard UI',
                'description' => 'Design and implement project dashboard with charts and statistics',
                'status' => 'todo',
                'priority' => 'normal',
                'progress_type' => 'manual',
                'progress' => 0,
                'start_time' => now()->addWeeks(1),
                'end_time' => now()->addWeeks(3),
                'estimated_hours' => 56,
                'actual_hours' => 0,
                'is_milestone' => false,
                'project_id' => $project->id,
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'assigned_to' => $developer2->id,
                'created_by' => $itManager->id,
                'tags' => json_encode(['frontend', 'dashboard', 'ui']),
            ],
        ];

        foreach ($tasks as $taskData) {
            \DB::table('tasks')->insert(array_merge($taskData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Seed task templates
     */
    private function seedTaskTemplates(): void
    {
        $organization = Organization::where('code', '1OFFICE')->first();
        $itDept = Department::where('code', 'IT')->first();
        $itManager = User::where('username', 'itmanager')->first();

        $templates = [
            [
                'uuid' => Str::uuid(),
                'name' => 'Bug Fix Template',
                'description' => 'Standard template for bug fixing tasks',
                'category' => 'Development',
                'template_data' => json_encode([
                    'title_template' => 'Fix: [Bug Description]',
                    'description_template' => "## Bug Description\n\n## Steps to Reproduce\n\n## Expected Behavior\n\n## Actual Behavior\n\n## Solution",
                    'priority' => 'high',
                    'estimated_hours' => 8,
                    'require_description_on_complete' => true,
                    'require_attachment_on_complete' => false,
                    'default_tags' => ['bug', 'fix'],
                    'checklist_items' => [
                        'Reproduce the bug',
                        'Identify root cause',
                        'Implement fix',
                        'Test the fix',
                        'Update documentation'
                    ]
                ]),
                'template_type' => 'task',
                'scope' => 'department',
                'usage_count' => 15,
                'average_rating' => 4.5,
                'rating_count' => 8,
                'is_active' => true,
                'is_featured' => true,
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'created_by' => $itManager->id,
                'tags' => json_encode(['bug', 'development', 'template']),
                'approval_status' => 'approved',
                'approved_by' => $itManager->id,
                'approved_at' => now()->subDays(30),
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Feature Development Template',
                'description' => 'Template for new feature development tasks',
                'category' => 'Development',
                'template_data' => json_encode([
                    'title_template' => 'Feature: [Feature Name]',
                    'description_template' => "## Feature Requirements\n\n## Acceptance Criteria\n\n## Technical Specifications\n\n## Testing Requirements",
                    'priority' => 'normal',
                    'estimated_hours' => 24,
                    'require_description_on_complete' => true,
                    'require_attachment_on_complete' => true,
                    'default_tags' => ['feature', 'development'],
                    'checklist_items' => [
                        'Analyze requirements',
                        'Design solution',
                        'Implement feature',
                        'Write tests',
                        'Code review',
                        'Deploy to staging',
                        'User acceptance testing'
                    ]
                ]),
                'template_type' => 'task',
                'scope' => 'organization',
                'usage_count' => 25,
                'average_rating' => 4.8,
                'rating_count' => 12,
                'is_active' => true,
                'is_featured' => true,
                'organization_id' => $organization->id,
                'department_id' => $itDept->id,
                'created_by' => $itManager->id,
                'tags' => json_encode(['feature', 'development', 'template']),
                'approval_status' => 'approved',
                'approved_by' => $itManager->id,
                'approved_at' => now()->subDays(45),
            ],
        ];

        foreach ($templates as $templateData) {
            \DB::table('task_templates')->insert(array_merge($templateData, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
