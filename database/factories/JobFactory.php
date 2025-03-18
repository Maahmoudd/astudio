<?php

namespace Database\Factories;

use App\Enums\JobStatusEnum;
use App\Enums\JobTypeEnum;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Job>
 */
class JobFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Job::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraph(4),
            'company_name' => $this->faker->company(),
            'salary_min' => $this->faker->numberBetween(50000, 80000),
            'salary_max' => $this->faker->numberBetween(80001, 150000),
            'is_remote' => $this->faker->boolean(),
            'job_type' => $this->faker->randomElement([
                JobTypeEnum::FULL_TIME->value,
                JobTypeEnum::PART_TIME->value,
                JobTypeEnum::CONTRACT->value,
                JobTypeEnum::FREELANCE->value,
            ]),
            'status' => JobStatusEnum::PUBLISHED->value,
            'published_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 30)),
        ];
    }

    /**
     * Set the job to a full-time position
     */
    public function fullTime()
    {
        return $this->state(function (array $attributes) {
            return [
                'job_type' => JobTypeEnum::FULL_TIME->value,
            ];
        });
    }

    /**
     * Set the job to a part-time position
     */
    public function partTime()
    {
        return $this->state(function (array $attributes) {
            return [
                'job_type' => JobTypeEnum::PART_TIME->value,
            ];
        });
    }

    /**
     * Set the job to a contract position
     */
    public function contract()
    {
        return $this->state(function (array $attributes) {
            return [
                'job_type' => JobTypeEnum::CONTRACT->value,
            ];
        });
    }

    /**
     * Set the job to a freelance position
     */
    public function freelance()
    {
        return $this->state(function (array $attributes) {
            return [
                'job_type' => JobTypeEnum::FREELANCE->value,
            ];
        });
    }

    /**
     * Set the job as remote
     */
    public function remote()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_remote' => true,
            ];
        });
    }

    /**
     * Set the job as on-site (not remote)
     */
    public function onSite()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_remote' => false,
            ];
        });
    }

    /**
     * Set the job with a high salary (100k+)
     */
    public function highSalary()
    {
        return $this->state(function (array $attributes) {
            return [
                'salary_min' => $this->faker->numberBetween(100000, 120000),
                'salary_max' => $this->faker->numberBetween(120001, 200000),
            ];
        });
    }

    /**
     * Set the job with a specific salary min
     */
    public function withSalaryMin(int $amount)
    {
        return $this->state(function (array $attributes) use ($amount) {
            $salaryMax = max($amount + 20000, $attributes['salary_max'] ?? 0);

            return [
                'salary_min' => $amount,
                'salary_max' => $salaryMax,
            ];
        });
    }

    /**
     * Set the job as recently published
     */
    public function recentlyPublished()
    {
        return $this->state(function (array $attributes) {
            return [
                'published_at' => Carbon::now()->subDays($this->faker->numberBetween(1, 7)),
            ];
        });
    }
}
