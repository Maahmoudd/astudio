<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Web Development',
            'Mobile Development',
            'Data Science',
            'Machine Learning',
            'DevOps',
            'UI/UX Design',
            'Project Management',
            'Quality Assurance',
            'Network Administration',
            'Security',
            'Database Administration',
            'Systems Administration',
            'Cloud Computing',
            'Blockchain',
            'Artificial Intelligence',
            'Technical Support',
            'Technical Writing',
            'Sales Engineering',
            'Digital Marketing',
            'Product Management',
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['name' => $category]);
        }
    }
}
