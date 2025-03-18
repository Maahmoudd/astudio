<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

interface IJobFilterService
{
    /**
     * Apply filters to a job query
     *
     * @param array $filters The filters to apply
     * @return Builder
     */
    public function applyFilters(array $filters): Builder;
}
