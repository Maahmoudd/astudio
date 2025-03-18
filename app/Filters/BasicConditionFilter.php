<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

class BasicConditionFilter extends AbstractFilterCondition
{
    /**
     * The operators supported by this filter
     *
     * @var array<string>
     */
    protected array $operators = ['>=', '<=', '!=', '=', '>', '<', ' LIKE '];

    /**
     * Check if this filter can handle the given expression
     *
     * @param string $expression
     * @return bool
     */
    public function canHandle(string $expression): bool
    {
        // This is a catch-all filter for basic field conditions
        // It should be registered last as it will match many expressions
        foreach ($this->operators as $operator) {
            if (str_contains($expression, $operator)) {
                $parts = explode($operator, $expression, 2);
                if (count($parts) === 2) {
                    $field = trim($parts[0]);
                    return $this->isValidField($field);
                }
            }
        }

        return false;
    }

    /**
     * Apply the basic condition filter to the query
     *
     * @param Builder $query
     * @param string $expression
     * @return Builder
     */
    public function apply(Builder $query, string $expression): Builder
    {
        try {
            foreach ($this->operators as $operator) {
                if (str_contains($expression, $operator)) {
                    $parts = explode($operator, $expression, 2);
                    if (count($parts) === 2) {
                        $field = trim($parts[0]);
                        $value = trim($parts[1]);

                        // Validate field name
                        if (!$this->isValidField($field)) {
                            Log::warning("Invalid field in filter: {$field}");
                            return $query;
                        }

                        // Handle IN operator (multiple values)
                        if (str_starts_with($value, '(') && strpos($value, ')') === strlen($value) - 1) {
                            return $this->applyInFilter($query, $field, $value, $operator);
                        }

                        // Remove quotes if present
                        if ((str_starts_with($value, "'") && strrpos($value, "'") === strlen($value) - 1) ||
                            (str_starts_with($value, '"') && strrpos($value, '"') === strlen($value) - 1)) {
                            $value = substr($value, 1, -1);
                        }

                        // Convert boolean string values to actual booleans for boolean fields
                        if ($field == 'is_remote' && in_array(strtolower($value), ['true', 'false'])) {
                            $value = strtolower($value) === 'true';
                        }

                        // Apply the appropriate where clause based on the operator
                        switch ($operator) {
                            case '=':
                                return $query->where($field, $value);
                            case '!=':
                                return $query->where($field, '!=', $value);
                            case '>':
                                return $query->where($field, '>', $value);
                            case '<':
                                return $query->where($field, '<', $value);
                            case '>=':
                                return $query->where($field, '>=', $value);
                            case '<=':
                                return $query->where($field, '<=', $value);
                            case ' LIKE ':
                                return $query->where($field, 'like', "%{$value}%");
                        }
                    }
                }
            }

            return $query;
        } catch (Exception $e) {
            Log::error("Error applying basic condition: {$expression}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $query;
        }
    }

    /**
     * Apply IN filter for field values
     *
     * @param Builder $query The query to modify
     * @param string $field The field to filter on
     * @param string $valueList Comma-separated list of values in parentheses
     * @param string $operator The operator (= or !=)
     * @return Builder
     */
    protected function applyInFilter(Builder $query, string $field, string $valueList, string $operator): Builder
    {
        // Validate field name
        if (!$this->isValidField($field)) {
            Log::warning("Invalid field in IN filter: {$field}");
            return $query;
        }

        $values = $this->parseValueList($valueList);

        if ($operator === '=') {
            return $query->whereIn($field, $values);
        } elseif ($operator === '!=') {
            return $query->whereNotIn($field, $values);
        }

        return $query;
    }
}
