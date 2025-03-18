<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

interface FilterCondition
{
    /**
     * Check if this filter can handle the given expression
     *
     * @param string $expression The filter expression to check
     * @return bool
     */
    public function canHandle(string $expression): bool;

    /**
     * Apply the filter condition to the query
     *
     * @param Builder $query The query to apply the filter to
     * @param string $expression The filter expression
     * @return Builder The modified query
     */
    public function apply(Builder $query, string $expression): Builder;
}
