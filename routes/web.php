<?php

use App\Enums\JobStatusEnum;
use App\Enums\JobTypeEnum;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $jobData = [
        'title' => 'Senior PHP Developer',
        'description' => 'We are looking for an experienced PHP developer to join our team and help us build scalable web applications. You will be responsible for developing new features, optimizing performance, and ensuring code quality.',
        'company_name' => 'TechCorp',
        'salary_min' => 85000,
        'salary_max' => 120000,
        'is_remote' => true,
        'job_type' => JobTypeEnum::FULL_TIME->value,
        'status' => JobStatusEnum::PUBLISHED->value,
        'published_at' => Carbon::now()->subDays(5),
        'attributes' => [
            'years_experience' => 5,
            'education_level' => 'Bachelor\'s Degree',
            'seniority_level' => 'Senior',
            'has_health_insurance' => true,
            'application_deadline' => Carbon::now()->addMonths(2)->format('Y-m-d'),
            'required_skills' => 'Laravel, Vue.js, MySQL, RESTful APIs, Git',
            'benefits' => '401(k) matching, Health insurance, Unlimited PTO, Education stipend',
            'work_schedule' => 'Flexible Hours',
        ],
        'languages' => ['PHP', 'JavaScript'],
        'locations' => ['New York', 'San Francisco'],
        'categories' => ['Web Development'],
    ];
    Job::create($jobData);
    $job = Job::query()->with('languages')->first();
    dd($job);
    return ;
});
