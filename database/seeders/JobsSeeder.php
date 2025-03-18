<?php

namespace Database\Seeders;

use App\Enums\JobStatusEnum;
use App\Enums\JobTypeEnum;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Job;
use App\Models\Language;
use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createFullTimeSoftwareJobs();
        $this->createPartTimeDesignJobs();
        $this->createContractDataJobs();
        $this->createFreelanceJobs();
    }

    /**
     * Create full-time software jobs
     */
    private function createFullTimeSoftwareJobs(): void
    {
        // Get related data
        $languages = Language::whereIn('name', ['PHP', 'JavaScript', 'Python', 'Java', 'TypeScript'])->get();
        $locations = Location::whereIn('city', ['New York', 'San Francisco', 'London', 'Berlin', 'Toronto'])->get();
        $categories = Category::whereIn('name', ['Web Development', 'Mobile Development', 'Cloud Computing'])->get();

        $jobs = [
            [
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
            ],
            [
                'title' => 'Full Stack JavaScript Developer',
                'description' => 'Join our agile team to develop responsive and interactive web applications using modern JavaScript frameworks. You\'ll be working on both frontend and backend development.',
                'company_name' => 'WebStack Inc.',
                'salary_min' => 75000,
                'salary_max' => 110000,
                'is_remote' => true,
                'job_type' => JobTypeEnum::FULL_TIME->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(2),
                'attributes' => [
                    'years_experience' => 3,
                    'education_level' => 'Bachelor\'s Degree',
                    'seniority_level' => 'Mid-Level',
                    'has_health_insurance' => true,
                    'application_deadline' => Carbon::now()->addMonths(1)->format('Y-m-d'),
                    'required_skills' => 'React, Node.js, Express, MongoDB, AWS',
                    'benefits' => 'Health insurance, 4-day work week, Remote work, Stock options',
                    'work_schedule' => 'Weekdays',
                ],
                'languages' => ['JavaScript', 'TypeScript'],
                'locations' => ['Berlin', 'London'],
                'categories' => ['Web Development', 'Cloud Computing'],
            ],
        ];

        $this->createJobs($jobs, $languages, $locations, $categories);

        // Add more jobs in a separate batch to reduce memory usage
        $jobs = [
            [
                'title' => 'Backend Python Developer',
                'description' => 'Looking for a talented Python developer to join our engineering team. You will be working on backend services and APIs for our SaaS platform.',
                'company_name' => 'CloudSoft Solutions',
                'salary_min' => 90000,
                'salary_max' => 135000,
                'is_remote' => false,
                'job_type' => JobTypeEnum::FULL_TIME->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(7),
                'attributes' => [
                    'years_experience' => 4,
                    'education_level' => 'Master\'s Degree',
                    'seniority_level' => 'Senior',
                    'has_health_insurance' => true,
                    'application_deadline' => Carbon::now()->addMonths(1)->format('Y-m-d'),
                    'required_skills' => 'Python, Django, FastAPI, PostgreSQL, Docker',
                    'benefits' => 'Health and dental insurance, Gym membership, Flexible hours, Annual bonus',
                    'work_schedule' => 'Weekdays',
                ],
                'languages' => ['Python'],
                'locations' => ['San Francisco'],
                'categories' => ['Web Development', 'Cloud Computing'],
            ],
            [
                'title' => 'Java Enterprise Developer',
                'description' => 'Join our team to develop robust enterprise applications. You will be responsible for designing, implementing, and maintaining Java-based applications.',
                'company_name' => 'Enterprise Solutions',
                'salary_min' => 95000,
                'salary_max' => 140000,
                'is_remote' => false,
                'job_type' => JobTypeEnum::FULL_TIME->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(10),
                'attributes' => [
                    'years_experience' => 6,
                    'education_level' => 'Bachelor\'s Degree',
                    'seniority_level' => 'Senior',
                    'has_health_insurance' => true,
                    'application_deadline' => Carbon::now()->addMonths(2)->format('Y-m-d'),
                    'required_skills' => 'Java, Spring Boot, Hibernate, MySQL, Microservices',
                    'benefits' => 'Competitive salary, Health benefits, 401(k), Professional development',
                    'work_schedule' => 'Weekdays',
                ],
                'languages' => ['Java'],
                'locations' => ['New York', 'Toronto'],
                'categories' => ['Web Development'],
            ],
        ];

        $this->createJobs($jobs, $languages, $locations, $categories);
    }

    /**
     * Create part-time design jobs
     */
    private function createPartTimeDesignJobs(): void
    {
        // Get related data
        $languages = Language::whereIn('name', ['HTML/CSS', 'JavaScript'])->get();
        $locations = Location::whereIn('city', ['Los Angeles', 'New York', 'London'])->get();
        $categories = Category::whereIn('name', ['UI/UX Design', 'Web Development'])->get();

        $jobs = [
            [
                'title' => 'UI/UX Designer (Part-time)',
                'description' => 'We\'re looking for a talented UI/UX designer to join our team on a part-time basis. You will create engaging and intuitive user interfaces for our web and mobile applications.',
                'company_name' => 'DesignHub',
                'salary_min' => 35000,
                'salary_max' => 55000,
                'is_remote' => true,
                'job_type' => JobTypeEnum::PART_TIME->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(3),
                'attributes' => [
                    'years_experience' => 2,
                    'education_level' => 'Bachelor\'s Degree',
                    'seniority_level' => 'Mid-Level',
                    'has_health_insurance' => false,
                    'application_deadline' => Carbon::now()->addWeeks(3)->format('Y-m-d'),
                    'required_skills' => 'Figma, Adobe XD, User Research, Wireframing, Prototyping',
                    'benefits' => 'Flexible schedule, Remote work, Portfolio development',
                    'work_schedule' => 'Flexible Hours',
                ],
                'languages' => ['HTML/CSS'],
                'locations' => ['Los Angeles', 'New York'],
                'categories' => ['UI/UX Design'],
            ],
            [
                'title' => 'Web Designer (Part-time)',
                'description' => 'Join our creative team to design visually appealing websites. You will collaborate with clients and developers to create responsive designs that meet project requirements.',
                'company_name' => 'Creative Solutions',
                'salary_min' => 30000,
                'salary_max' => 45000,
                'is_remote' => true,
                'job_type' => JobTypeEnum::PART_TIME->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(5),
                'attributes' => [
                    'years_experience' => 1,
                    'education_level' => 'Associate Degree',
                    'seniority_level' => 'Junior',
                    'has_health_insurance' => false,
                    'application_deadline' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
                    'required_skills' => 'HTML, CSS, Responsive Design, Adobe Creative Suite',
                    'benefits' => 'Flexible hours, Work from anywhere, Professional development',
                    'work_schedule' => 'Flexible Hours',
                ],
                'languages' => ['HTML/CSS', 'JavaScript'],
                'locations' => ['London'],
                'categories' => ['UI/UX Design', 'Web Development'],
            ],
        ];

        $this->createJobs($jobs, $languages, $locations, $categories);
    }

    /**
     * Create contract data science jobs
     */
    private function createContractDataJobs(): void
    {
        // Get related data
        $languages = Language::whereIn('name', ['Python', 'R', 'SQL'])->get();
        $locations = Location::whereIn('city', ['New York', 'San Francisco', 'London', 'Berlin', 'Toronto'])->get();
        $categories = Category::whereIn('name', ['Data Science', 'Machine Learning', 'Artificial Intelligence'])->get();

        $jobs = [
            [
                'title' => 'Data Scientist (6-month Contract)',
                'description' => 'We are seeking a Data Scientist to join our team on a 6-month contract. You will analyze complex data sets, build predictive models, and deliver actionable insights to improve business outcomes.',
                'company_name' => 'DataInsights',
                'salary_min' => 50000,
                'salary_max' => 75000, // For 6 months
                'is_remote' => true,
                'job_type' => JobTypeEnum::CONTRACT->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(2),
                'attributes' => [
                    'years_experience' => 3,
                    'education_level' => 'Master\'s Degree',
                    'seniority_level' => 'Mid-Level',
                    'has_health_insurance' => false,
                    'application_deadline' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
                    'required_skills' => 'Python, SQL, Machine Learning, Statistical Analysis, Data Visualization',
                    'benefits' => 'Flexible schedule, Remote work',
                    'work_schedule' => 'Weekdays',
                    'contract_duration' => '6 months',
                ],
                'languages' => ['Python', 'SQL'],
                'locations' => ['New York', 'San Francisco'],
                'categories' => ['Data Science', 'Machine Learning'],
            ],
            [
                'title' => 'Machine Learning Engineer (3-month Contract)',
                'description' => 'Join our AI team for a 3-month contract to develop and implement machine learning algorithms. You will work on cutting-edge projects in natural language processing and computer vision.',
                'company_name' => 'AI Innovations',
                'salary_min' => 30000,
                'salary_max' => 45000, // For 3 months
                'is_remote' => false,
                'job_type' => JobTypeEnum::CONTRACT->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(4),
                'attributes' => [
                    'years_experience' => 2,
                    'education_level' => 'Master\'s Degree',
                    'seniority_level' => 'Mid-Level',
                    'has_health_insurance' => false,
                    'application_deadline' => Carbon::now()->addWeeks(1)->format('Y-m-d'),
                    'required_skills' => 'Python, TensorFlow, PyTorch, Deep Learning, NLP',
                    'benefits' => 'Access to cutting-edge technology, Networking opportunities',
                    'work_schedule' => 'Weekdays',
                    'contract_duration' => '3 months',
                ],
                'languages' => ['Python'],
                'locations' => ['London', 'Berlin'],
                'categories' => ['Machine Learning', 'Artificial Intelligence'],
            ],
        ];

        $this->createJobs($jobs, $languages, $locations, $categories);
    }

    /**
     * Create freelance jobs
     */
    private function createFreelanceJobs(): void
    {
        // Get related data
        $languages = Language::whereIn('name', ['JavaScript', 'PHP', 'Python', 'HTML/CSS'])->get();
        $locations = Location::all()->take(5); // Limit locations to avoid memory issues
        $categories = Category::whereIn('name', ['Web Development', 'UI/UX Design', 'Technical Writing', 'Digital Marketing'])->get();

        $jobs = [
            [
                'title' => 'WordPress Developer (Freelance)',
                'description' => 'Looking for a WordPress developer to create a custom theme and implement specific functionality for our blog. This is a one-time project with potential for ongoing maintenance.',
                'company_name' => 'ContentPlus',
                'salary_min' => 2000,
                'salary_max' => 5000,
                'is_remote' => true,
                'job_type' => JobTypeEnum::FREELANCE->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(1),
                'attributes' => [
                    'years_experience' => 2,
                    'education_level' => 'High School',
                    'seniority_level' => 'Mid-Level',
                    'has_health_insurance' => false,
                    'application_deadline' => Carbon::now()->addWeeks(1)->format('Y-m-d'),
                    'required_skills' => 'WordPress, PHP, Custom Theme Development, Plugin Development',
                    'benefits' => 'Portfolio enhancement, Potential for ongoing work',
                    'work_schedule' => 'Flexible Hours',
                ],
                'languages' => ['PHP', 'HTML/CSS', 'JavaScript'],
                'locations' => [],
                'categories' => ['Web Development'],
            ],
            [
                'title' => 'Technical Writer (Freelance)',
                'description' => 'We need a skilled technical writer to create user documentation for our software products. You will work with our development team to understand features and document them clearly.',
                'company_name' => 'SoftDocs',
                'salary_min' => 1500,
                'salary_max' => 3000,
                'is_remote' => true,
                'job_type' => JobTypeEnum::FREELANCE->value,
                'status' => JobStatusEnum::PUBLISHED->value,
                'published_at' => Carbon::now()->subDays(3),
                'attributes' => [
                    'years_experience' => 1,
                    'education_level' => 'Bachelor\'s Degree',
                    'seniority_level' => 'Junior',
                    'has_health_insurance' => false,
                    'application_deadline' => Carbon::now()->addWeeks(2)->format('Y-m-d'),
                    'required_skills' => 'Technical Writing, Markdown, Documentation, Clear Communication',
                    'benefits' => 'Flexible schedule, Work from anywhere',
                    'work_schedule' => 'Flexible Hours',
                ],
                'languages' => [],
                'locations' => [],
                'categories' => ['Technical Writing'],
            ],
        ];

        $this->createJobs($jobs, $languages, $locations, $categories);
    }

    /**
     * Helper method to create jobs with relationships and attributes
     */
    private function createJobs(array $jobsData, $languages, $locations, $categories): void
    {
        foreach ($jobsData as $jobData) {
            // Extract metadata first
            $attributes = $jobData['attributes'] ?? [];
            $jobLanguages = $jobData['languages'] ?? [];
            $jobLocations = $jobData['locations'] ?? [];
            $jobCategories = $jobData['categories'] ?? [];


            // Remove metadata from job data
            unset($jobData['attributes'], $jobData['languages'], $jobData['locations'], $jobData['categories']);

                // Create the job
            $job = Job::create($jobData);

                // Add relationships - do this inside a transaction for each job
            DB::beginTransaction();
            // Attach languages
            if (!empty($jobLanguages)) {
                $languageIds = $languages->whereIn('name', $jobLanguages)->pluck('id')->toArray();
                if (!empty($languageIds)) {
                    $job->languages()->attach($languageIds);
                }
            }

            // Attach locations
            if (!empty($jobLocations)) {
                $locationIds = $locations->whereIn('city', $jobLocations)->pluck('id')->toArray();
                if (!empty($locationIds)) {
                    $job->locations()->attach($locationIds);
                }
            }

            // Attach categories
            if (!empty($jobCategories)) {
                $categoryIds = $categories->whereIn('name', $jobCategories)->pluck('id')->toArray();
                if (!empty($categoryIds)) {
                    $job->categories()->attach($categoryIds);
                }
            }

            // Add attribute values
            if (!empty($attributes)) {
                $job->setAttributeValuesRelation($attributes);
            }
            DB::commit();
        }
    }
}
