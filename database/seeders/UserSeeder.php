<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Organization;
use App\Models\Department;
use App\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainOrg = Organization::where('code', '1OFFICE')->first();
        $itDept = Department::where('code', 'IT')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $salesDept = Department::where('code', 'SALES')->first();
        $execDept = Department::where('code', 'EXEC')->first();
        $devDept = Department::where('code', 'DEV')->first();

        // Create super admin
        $superAdmin = User::create([
            'username' => 'superadmin',
            'email' => 'superadmin@1office.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Super',
            'last_name' => 'Administrator',
            'phone' => '+1-555-0001',
            'is_active' => true,
            'is_verified' => true,
            'organization_id' => $mainOrg->id,
            'department_id' => $execDept->id,
            'employee_id' => 'EMP001',
            'position' => 'Super Administrator',
            'hire_date' => now()->subYears(2),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'timezone' => 'America/Los_Angeles',
            'locale' => 'en',
        ]);

        // Assign super admin role
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $superAdmin->assignRole($superAdminRole);

        // Create system admin
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@1office.com',
            'password' => Hash::make('password123'),
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'phone' => '+1-555-0002',
            'is_active' => true,
            'is_verified' => true,
            'organization_id' => $mainOrg->id,
            'department_id' => $itDept->id,
            'employee_id' => 'EMP002',
            'position' => 'System Administrator',
            'hire_date' => now()->subYears(1),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'timezone' => 'America/Los_Angeles',
            'locale' => 'en',
        ]);

        $adminRole = Role::where('slug', 'admin')->first();
        $admin->assignRole($adminRole);

        // Create organization manager
        $orgManager = User::create([
            'username' => 'orgmanager',
            'email' => 'manager@1office.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Organization',
            'last_name' => 'Manager',
            'phone' => '+1-555-0003',
            'is_active' => true,
            'is_verified' => true,
            'organization_id' => $mainOrg->id,
            'department_id' => $execDept->id,
            'employee_id' => 'EMP003',
            'position' => 'Organization Manager',
            'hire_date' => now()->subMonths(18),
            'employment_status' => 'active',
            'employment_type' => 'full_time',
            'timezone' => 'America/Los_Angeles',
            'locale' => 'en',
        ]);

        $orgManagerRole = Role::where('slug', 'org-manager')->first();
        $orgManager->assignRole($orgManagerRole);

        // Create department managers
        $users = [
            [
                'username' => 'itmanager',
                'email' => 'it.manager@1office.com',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'department_id' => $itDept->id,
                'position' => 'IT Manager',
                'role' => 'project-manager',
            ],
            [
                'username' => 'hrmanager',
                'email' => 'hr.manager@1office.com',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'department_id' => $hrDept->id,
                'position' => 'HR Manager',
                'role' => 'hr-manager',
            ],
            [
                'username' => 'salesmanager',
                'email' => 'sales.manager@1office.com',
                'first_name' => 'Michael',
                'last_name' => 'Brown',
                'department_id' => $salesDept->id,
                'position' => 'Sales Manager',
                'role' => 'sales-manager',
            ],
            [
                'username' => 'developer1',
                'email' => 'dev1@1office.com',
                'first_name' => 'Alice',
                'last_name' => 'Wilson',
                'department_id' => $devDept->id,
                'position' => 'Senior Developer',
                'role' => 'employee',
            ],
            [
                'username' => 'developer2',
                'email' => 'dev2@1office.com',
                'first_name' => 'Bob',
                'last_name' => 'Davis',
                'department_id' => $devDept->id,
                'position' => 'Frontend Developer',
                'role' => 'employee',
            ],
            [
                'username' => 'hrspecialist',
                'email' => 'hr.specialist@1office.com',
                'first_name' => 'Emma',
                'last_name' => 'Taylor',
                'department_id' => $hrDept->id,
                'position' => 'HR Specialist',
                'role' => 'hr-specialist',
            ],
            [
                'username' => 'salesrep1',
                'email' => 'sales1@1office.com',
                'first_name' => 'David',
                'last_name' => 'Miller',
                'department_id' => $salesDept->id,
                'position' => 'Sales Representative',
                'role' => 'sales-rep',
            ],
            [
                'username' => 'salesrep2',
                'email' => 'sales2@1office.com',
                'first_name' => 'Lisa',
                'last_name' => 'Anderson',
                'department_id' => $salesDept->id,
                'position' => 'Account Manager',
                'role' => 'sales-rep',
            ],
        ];

        $employeeCounter = 4;
        foreach ($users as $userData) {
            $roleSlug = $userData['role'];
            unset($userData['role']);

            $user = User::create(array_merge([
                'password' => Hash::make('password123'),
                'phone' => '+1-555-' . str_pad($employeeCounter + 10, 4, '0', STR_PAD_LEFT),
                'is_active' => true,
                'is_verified' => true,
                'organization_id' => $mainOrg->id,
                'employee_id' => 'EMP' . str_pad($employeeCounter, 3, '0', STR_PAD_LEFT),
                'hire_date' => now()->subMonths(rand(3, 12)),
                'employment_status' => 'active',
                'employment_type' => 'full_time',
                'timezone' => 'America/Los_Angeles',
                'locale' => 'en',
            ], $userData));

            // Assign role
            $role = Role::where('slug', $roleSlug)->first();
            if ($role) {
                $user->assignRole($role);
            }

            $employeeCounter++;
        }

        // Set department managers
        $itDept->update(['manager_id' => User::where('username', 'itmanager')->first()->id]);
        $hrDept->update(['manager_id' => User::where('username', 'hrmanager')->first()->id]);
        $salesDept->update(['manager_id' => User::where('username', 'salesmanager')->first()->id]);

        // Set manager relationships
        $itManager = User::where('username', 'itmanager')->first();
        $hrManager = User::where('username', 'hrmanager')->first();
        $salesManager = User::where('username', 'salesmanager')->first();

        // IT team reports to IT manager
        User::whereIn('username', ['developer1', 'developer2'])
            ->update(['manager_id' => $itManager->id]);

        // HR team reports to HR manager
        User::where('username', 'hrspecialist')
            ->update(['manager_id' => $hrManager->id]);

        // Sales team reports to Sales manager
        User::whereIn('username', ['salesrep1', 'salesrep2'])
            ->update(['manager_id' => $salesManager->id]);

        // Managers report to org manager
        User::whereIn('username', ['itmanager', 'hrmanager', 'salesmanager'])
            ->update(['manager_id' => $orgManager->id]);
    }
}
