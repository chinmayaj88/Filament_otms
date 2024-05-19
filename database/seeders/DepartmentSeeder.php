<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            'Software Development Department',
            'Quality Assurance Department',
            'Project Management Office',
            'Information Security Department',
            'Network Operations Center (NOC)',
            'IT Infrastructure Department',
            'Customer Support Department',
            'Research and Development Division',
            'Data Analytics Department',
            'Cloud Services Department',
        ];

        // Insert data into the departments table
        foreach ($departments as $department) {
            DB::table('departments')->insert([
                'name' => $department,
            ]);
        }
    }
}
