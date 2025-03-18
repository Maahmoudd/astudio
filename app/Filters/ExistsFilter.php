<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

class ExistsFilter extends AbstractFilterCondition
{
    /**
     * Check if this filter can handle the given expression
     *
     * @param string $expression
     * @return bool
     */
    public function canHandle(string $expression): bool
    {
        return str_contains($expression, ' EXISTS');
    }

    /**
     * Apply the EXISTS filter to the query
     *
     * @param Builder $query
     * @param string $expression
     * @return Builder
     */
    public function apply(Builder $query, string $expression): Builder
    {
        try {
            $relation = trim(str_replace(' EXISTS', '', $expression));

            // Validate relation name
            if (!$this->isValidRelation($relation)) {
                Log::warning("Invalid relation in EXISTS filter: {$relation}");
                return $query;
            }

            return $query->has($relation);
        } catch (Exception $e) {
            Log::error("Error applying EXISTS filter: {$expression}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $query;
        }
    }
}
