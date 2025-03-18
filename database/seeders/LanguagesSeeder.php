<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            'PHP',
            'JavaScript',
            'Python',
            'Java',
            'C#',
            'C++',
            'Ruby',
            'Go',
            'Swift',
            'Kotlin',
            'TypeScript',
            'Rust',
            'SQL',
            'HTML/CSS',
            'Scala',
            'Perl',
            'R',
            'MATLAB',
            'Dart',
            'Elixir',
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['name' => $language]);
        }
    }
}
