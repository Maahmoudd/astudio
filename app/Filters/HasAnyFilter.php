<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

class HasAnyFilter extends AbstractFilterCondition
{
    /**
     * Check if this filter can handle the given expression
     *
     * @param string $expression
     * @return bool
     */
    public function canHandle(string $expression): bool
    {
        return str_contains($expression, ' HAS_ANY ');
    }

    /**
     * Apply the HAS_ANY filter to the query
     *
     * @param Builder $query
     * @param string $expression
     * @return Builder
     */
    public function apply(Builder $query, string $expression): Builder
    {
        try {
            [$relation, $values] = explode(' HAS_ANY ', $expression);
            $relation = trim($relation);

            // Validate relation name
            if (!$this->isValidRelation($relation)) {
                Log::warning("Invalid relation in HAS_ANY filter: {$relation}");
                return $query;
            }

            $valuesList = $this->parseValueList($values);

            if ($relation === 'languages') {
                return $query->whereHas($relation, function ($q) use ($valuesList) {
                    $q->whereIn('name', $valuesList);
                });
            } else if ($relation === 'locations') {
                return $query->whereHas($relation, function ($q) use ($valuesList) {
                    $q->whereIn('city', $valuesList);
                });
            } else if ($relation === 'categories') {
                return $query->whereHas($relation, function ($q) use ($valuesList) {
                    $q->whereIn('name', $valuesList);
                });
            }

            // Default fallback to name column
            return $query->whereHas($relation, function ($q) use ($valuesList) {
                $q->whereIn('name', $valuesList);
            });
        } catch (Exception $e) {
            Log::error("Error applying HAS_ANY filter: {$expression}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $query;
        }
    }
}
