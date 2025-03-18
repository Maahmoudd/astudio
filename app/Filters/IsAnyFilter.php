<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

class IsAnyFilter extends AbstractFilterCondition
{
    /**
     * Check if this filter can handle the given expression
     *
     * @param string $expression
     * @return bool
     */
    public function canHandle(string $expression): bool
    {
        return str_contains($expression, ' IS_ANY ');
    }

    /**
     * Apply the IS_ANY filter to the query
     *
     * @param Builder $query
     * @param string $expression
     * @return Builder
     */
    public function apply(Builder $query, string $expression): Builder
    {
        try {
            [$relation, $values] = explode(' IS_ANY ', $expression);
            $relation = trim($relation);

            // Validate relation name
            if (!$this->isValidRelation($relation)) {
                Log::warning("Invalid relation in IS_ANY filter: {$relation}");
                return $query;
            }

            $valuesList = $this->parseValueList($values);

            // Special case for locations to handle "Remote"
            if ($relation === 'locations') {
                $hasRemote = in_array('Remote', $valuesList, true) || in_array('remote', $valuesList, true);
                $nonRemoteValues = array_filter($valuesList, function($value) {
                    return strtolower($value) !== 'remote';
                });

                if ($hasRemote) {
                    // Return jobs that are either remote or in one of the specified locations
                    return $query->where(function ($q) use ($relation, $nonRemoteValues) {
                        $q->where('is_remote', true);

                        if (!empty($nonRemoteValues)) {
                            $q->orWhereHas($relation, function ($locationQuery) use ($nonRemoteValues) {
                                $locationQuery->whereIn('city', $nonRemoteValues);
                            });
                        }
                    });
                }

                // Standard location filter if "Remote" is not in the list
                return $query->whereHas($relation, function ($q) use ($valuesList) {
                    $q->whereIn('city', $valuesList);
                });
            } else if ($relation === 'languages') {
                return $query->whereHas($relation, function ($q) use ($valuesList) {
                    $q->whereIn('name', $valuesList);
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
            Log::error("Error applying IS_ANY filter: {$expression}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $query;
        }
    }
}
