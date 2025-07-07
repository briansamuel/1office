<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Organization;
use App\Models\Department;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main organization
        $mainOrg = Organization::create([
            'name' => '1Office Corporation',
            'slug' => '1office-corp',
            'code' => '1OFFICE',
            'description' => 'Main organization for 1Office system',
            'email' => 'info@1office.com',
            'phone' => '+1-555-0123',
            'website' => 'https://1office.com',
            'address' => [
                'street' => '123 Business Avenue',
                'city' => 'Tech City',
                'state' => 'California',
                'postal_code' => '90210',
                'country' => 'United States',
            ],
            'timezone' => 'America/Los_Angeles',
            'locale' => 'en',
            'currency' => 'USD',
            'settings' => [
                'working_hours' => [
                    'start' => '09:00',
                    'end' => '17:00',
                ],
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'features' => [
                    'work' => true,
                    'hrm' => true,
                    'crm' => true,
                    'warehouse' => true,
                ],
            ],
            'is_active' => true,
        ]);

        // Create subsidiary organizations
        $subsidiaries = [
            [
                'name' => '1Office Tech Solutions',
                'slug' => '1office-tech',
                'code' => '1TECH',
                'description' => 'Technology solutions division',
                'parent_id' => $mainOrg->id,
            ],
            [
                'name' => '1Office Consulting',
                'slug' => '1office-consulting',
                'code' => '1CONSULT',
                'description' => 'Business consulting division',
                'parent_id' => $mainOrg->id,
            ],
        ];

        foreach ($subsidiaries as $subData) {
            Organization::create(array_merge([
                'email' => strtolower($subData['code']) . '@1office.com',
                'timezone' => 'America/Los_Angeles',
                'locale' => 'en',
                'currency' => 'USD',
                'is_active' => true,
            ], $subData));
        }

        // Create departments for main organization
        $departments = [
            [
                'name' => 'Executive',
                'slug' => 'executive',
                'code' => 'EXEC',
                'description' => 'Executive leadership team',
                'organization_id' => $mainOrg->id,
            ],
            [
                'name' => 'Information Technology',
                'slug' => 'information-technology',
                'code' => 'IT',
                'description' => 'IT department responsible for technology infrastructure',
                'organization_id' => $mainOrg->id,
            ],
            [
                'name' => 'Human Resources',
                'slug' => 'human-resources',
                'code' => 'HR',
                'description' => 'Human resources and people operations',
                'organization_id' => $mainOrg->id,
            ],
            [
                'name' => 'Sales & Marketing',
                'slug' => 'sales-marketing',
                'code' => 'SALES',
                'description' => 'Sales and marketing operations',
                'organization_id' => $mainOrg->id,
            ],
            [
                'name' => 'Finance & Accounting',
                'slug' => 'finance-accounting',
                'code' => 'FINANCE',
                'description' => 'Financial operations and accounting',
                'organization_id' => $mainOrg->id,
            ],
            [
                'name' => 'Operations',
                'slug' => 'operations',
                'code' => 'OPS',
                'description' => 'Business operations and logistics',
                'organization_id' => $mainOrg->id,
            ],
        ];

        $createdDepartments = [];
        foreach ($departments as $deptData) {
            $dept = Department::create(array_merge([
                'settings' => [
                    'budget' => 100000,
                    'max_employees' => 50,
                ],
                'is_active' => true,
            ], $deptData));
            
            $createdDepartments[$dept->code] = $dept;
        }

        // Create sub-departments
        $subDepartments = [
            [
                'name' => 'Software Development',
                'slug' => 'software-development',
                'code' => 'DEV',
                'description' => 'Software development team',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['IT']->id,
            ],
            [
                'name' => 'Quality Assurance',
                'slug' => 'quality-assurance',
                'code' => 'QA',
                'description' => 'Quality assurance and testing',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['IT']->id,
            ],
            [
                'name' => 'DevOps',
                'slug' => 'devops',
                'code' => 'DEVOPS',
                'description' => 'Development operations and infrastructure',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['IT']->id,
            ],
            [
                'name' => 'Recruitment',
                'slug' => 'recruitment',
                'code' => 'RECRUIT',
                'description' => 'Talent acquisition and recruitment',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['HR']->id,
            ],
            [
                'name' => 'Employee Relations',
                'slug' => 'employee-relations',
                'code' => 'EMPREL',
                'description' => 'Employee relations and engagement',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['HR']->id,
            ],
            [
                'name' => 'Digital Marketing',
                'slug' => 'digital-marketing',
                'code' => 'DIGMKT',
                'description' => 'Digital marketing and online presence',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['SALES']->id,
            ],
            [
                'name' => 'Sales Operations',
                'slug' => 'sales-operations',
                'code' => 'SALESOPS',
                'description' => 'Sales operations and customer success',
                'organization_id' => $mainOrg->id,
                'parent_id' => $createdDepartments['SALES']->id,
            ],
        ];

        foreach ($subDepartments as $subDeptData) {
            Department::create(array_merge([
                'settings' => [
                    'budget' => 50000,
                    'max_employees' => 20,
                ],
                'is_active' => true,
            ], $subDeptData));
        }
    }
}
