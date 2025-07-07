<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            // System roles
            [
                'name' => 'Super Administrator',
                'slug' => 'super-admin',
                'display_name' => 'Super Administrator',
                'description' => 'Has complete access to all system features and modules',
                'module' => 'system',
                'level' => 100,
                'is_system_role' => true,
                'permissions' => ['*'], // All permissions
            ],
            [
                'name' => 'System Administrator',
                'slug' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Has administrative access to system management',
                'module' => 'system',
                'level' => 90,
                'is_system_role' => true,
                'permissions' => [
                    'system.users.create',
                    'system.users.read',
                    'system.users.update',
                    'system.users.delete',
                    'system.roles.manage',
                    'system.departments.manage',
                    'system.audit_logs.read',
                ],
            ],
            [
                'name' => 'Organization Manager',
                'slug' => 'org-manager',
                'display_name' => 'Organization Manager',
                'description' => 'Manages organization-wide operations',
                'module' => 'system',
                'level' => 80,
                'is_system_role' => true,
                'permissions' => [
                    'system.users.read',
                    'system.users.update',
                    'system.departments.manage',
                    'work.tasks.read',
                    'work.projects.read',
                    'hrm.employees.read',
                    'crm.customers.read',
                    'warehouse.products.read',
                ],
            ],

            // Work module roles
            [
                'name' => 'Project Manager',
                'slug' => 'project-manager',
                'display_name' => 'Project Manager',
                'description' => 'Manages projects and tasks',
                'module' => 'work',
                'level' => 70,
                'is_system_role' => false,
                'permissions' => [
                    'work.tasks.create',
                    'work.tasks.read',
                    'work.tasks.update',
                    'work.tasks.delete',
                    'work.projects.create',
                    'work.projects.read',
                    'work.projects.update',
                    'work.projects.delete',
                    'system.users.read',
                ],
            ],
            [
                'name' => 'Team Lead',
                'slug' => 'team-lead',
                'display_name' => 'Team Lead',
                'description' => 'Leads a team and manages team tasks',
                'module' => 'work',
                'level' => 60,
                'is_system_role' => false,
                'permissions' => [
                    'work.tasks.create',
                    'work.tasks.read',
                    'work.tasks.update',
                    'work.projects.read',
                    'system.users.read',
                ],
            ],
            [
                'name' => 'Employee',
                'slug' => 'employee',
                'display_name' => 'Employee',
                'description' => 'Regular employee with basic access',
                'module' => 'work',
                'level' => 30,
                'is_system_role' => false,
                'permissions' => [
                    'work.tasks.read',
                    'work.tasks.update',
                    'work.projects.read',
                    'system.users.read',
                ],
            ],

            // HRM module roles
            [
                'name' => 'HR Manager',
                'slug' => 'hr-manager',
                'display_name' => 'HR Manager',
                'description' => 'Manages human resources operations',
                'module' => 'hrm',
                'level' => 70,
                'is_system_role' => false,
                'permissions' => [
                    'hrm.employees.create',
                    'hrm.employees.read',
                    'hrm.employees.update',
                    'hrm.attendance.manage',
                    'hrm.payroll.manage',
                    'system.users.create',
                    'system.users.read',
                    'system.users.update',
                ],
            ],
            [
                'name' => 'HR Specialist',
                'slug' => 'hr-specialist',
                'display_name' => 'HR Specialist',
                'description' => 'Handles specific HR functions',
                'module' => 'hrm',
                'level' => 50,
                'is_system_role' => false,
                'permissions' => [
                    'hrm.employees.read',
                    'hrm.employees.update',
                    'hrm.attendance.manage',
                    'system.users.read',
                ],
            ],

            // CRM module roles
            [
                'name' => 'Sales Manager',
                'slug' => 'sales-manager',
                'display_name' => 'Sales Manager',
                'description' => 'Manages sales operations and customer relationships',
                'module' => 'crm',
                'level' => 70,
                'is_system_role' => false,
                'permissions' => [
                    'crm.customers.create',
                    'crm.customers.read',
                    'crm.leads.manage',
                    'crm.deals.manage',
                    'system.users.read',
                ],
            ],
            [
                'name' => 'Sales Representative',
                'slug' => 'sales-rep',
                'display_name' => 'Sales Representative',
                'description' => 'Handles customer relationships and sales',
                'module' => 'crm',
                'level' => 40,
                'is_system_role' => false,
                'permissions' => [
                    'crm.customers.create',
                    'crm.customers.read',
                    'crm.leads.manage',
                    'crm.deals.manage',
                ],
            ],

            // Warehouse module roles
            [
                'name' => 'Warehouse Manager',
                'slug' => 'warehouse-manager',
                'display_name' => 'Warehouse Manager',
                'description' => 'Manages warehouse operations and inventory',
                'module' => 'warehouse',
                'level' => 70,
                'is_system_role' => false,
                'permissions' => [
                    'warehouse.products.create',
                    'warehouse.products.read',
                    'warehouse.inventory.manage',
                    'warehouse.orders.manage',
                    'system.users.read',
                ],
            ],
            [
                'name' => 'Inventory Clerk',
                'slug' => 'inventory-clerk',
                'display_name' => 'Inventory Clerk',
                'description' => 'Manages product inventory',
                'module' => 'warehouse',
                'level' => 40,
                'is_system_role' => false,
                'permissions' => [
                    'warehouse.products.read',
                    'warehouse.inventory.manage',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            $permissions = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = Role::create($roleData);

            // Assign permissions to role
            if ($permissions === ['*']) {
                // Super admin gets all permissions
                $allPermissions = Permission::all();
                $permissionIds = $allPermissions->mapWithKeys(function ($permission) {
                    return [$permission->id => ['is_granted' => true]];
                })->toArray();
                $role->permissions()->sync($permissionIds);
            } else {
                $permissionIds = [];
                foreach ($permissions as $permissionSlug) {
                    $permission = Permission::where('slug', $permissionSlug)->first();
                    if ($permission) {
                        $permissionIds[$permission->id] = ['is_granted' => true];
                    }
                }
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
