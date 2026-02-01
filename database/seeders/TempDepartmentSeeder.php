<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobVacancy;

class TempDepartmentSeeder extends Seeder
{
    public function run()
    {
        JobVacancy::create([
            'title' => 'Test Department Seeder',
            'slug' => 'test-dept-seeder',
            'department' => 'RnD',
            'created_by' => 1,
            'description' => 'Test',
            'required_count' => 1,
            'is_published' => false,
            'status' => 'pending'
        ]);
    }
}
