<?php

namespace Database\Seeders;

use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Common job attributes
        $attributes = [
            [
                'name' => 'years_experience',
                'type' => AttributeTypeEnum::NUMBER,
                'options' => null,
            ],
            [
                'name' => 'education_level',
                'type' => AttributeTypeEnum::SELECT,
                'options' => [
                    'High School',
                    'Associate Degree',
                    'Bachelor\'s Degree',
                    'Master\'s Degree',
                    'PhD',
                ],
            ],
            [
                'name' => 'allows_remote',
                'type' => AttributeTypeEnum::BOOLEAN,
                'options' => null,
            ],
            [
                'name' => 'has_health_insurance',
                'type' => AttributeTypeEnum::BOOLEAN,
                'options' => null,
            ],
            [
                'name' => 'application_deadline',
                'type' => AttributeTypeEnum::DATE,
                'options' => null,
            ],
            [
                'name' => 'seniority_level',
                'type' => AttributeTypeEnum::SELECT,
                'options' => [
                    'Entry Level',
                    'Junior',
                    'Mid-Level',
                    'Senior',
                    'Lead',
                    'Manager',
                    'Director',
                    'Executive',
                ],
            ],
            [
                'name' => 'required_skills',
                'type' => AttributeTypeEnum::TEXT,
                'options' => null,
            ],
            [
                'name' => 'benefits',
                'type' => AttributeTypeEnum::TEXT,
                'options' => null,
            ],
            [
                'name' => 'work_schedule',
                'type' => AttributeTypeEnum::SELECT,
                'options' => [
                    'Weekdays',
                    'Weekends',
                    'Night Shift',
                    'Flexible Hours',
                    'Shifts',
                ],
            ],
            [
                'name' => 'contract_duration',
                'type' => AttributeTypeEnum::SELECT,
                'options' => [
                    '3 months',
                    '6 months',
                    '1 year',
                    '2+ years',
                    'Indefinite',
                ],
            ],
        ];

        foreach ($attributes as $attribute) {
            Attribute::updateOrCreate(
                ['name' => $attribute['name']],
                $attribute
            );
        }
    }
}
