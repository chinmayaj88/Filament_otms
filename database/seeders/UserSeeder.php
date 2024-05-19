<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = DB::table('departments')->pluck('id')->toArray();


        $faker = \Faker\Factory::create();

        $employees = [];
        $employeeCount = 20;

        for ($i = 0; $i < $employeeCount; $i++) {
            $employees[] = [
                'name' => $faker->firstName,
                'email' => $faker->unique()->safeEmail,
                'department_id' => $faker->randomElement($departments),
                'is_email_verified' => true,
                'password' => bcrypt('password'),
                'role' => 'employee'

            ];
        }


        DB::table('users')->insert($employees);
    }
}
