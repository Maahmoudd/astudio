<?php

namespace App\Services;

use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class JobFilterService implements IJobFilterService
{
    /**
     * Apply filters to a job query
     *
     * @param array $filters The filters to apply
     * @return Builder
     */
    public function applyFilters(array $filters): Builder
    {
        $query = Job::query();

        // If there's a filter parameter, parse and apply it
        if (isset($filters['filter']) && !empty($filters['filter'])) {
            $filterString = $filters['filter'];
            $query = $this->parseFilterExpression($query, $filterString);
        }

        // Apply sorting
        if (isset($filters['sort_by']) && !empty($filters['sort_by'])) {
            $sortField = $filters['sort_by'];
            $sortDirection = $filters['sort_direction'] ?? 'asc';

            // Check if sorting by an attribute
            if (strpos($sortField, 'attribute:') === 0) {
                $attributeName = substr($sortField, 10);
                $query = $this->sortByAttribute($query, $attributeName, $sortDirection);
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
        }

        return $query;
    }

    /**
     * Parse a filter expression and apply it to the query
     */
    protected function parseFilterExpression(Builder $query, string $expression): Builder
    {
        // First, check if the expression is empty
        if (empty(trim($expression))) {
            return $query;
        }

        // Handle parentheses and nested expressions
        if ($this->hasOuterParentheses($expression)) {
            return $this->parseFilterExpression($query, substr($expression, 1, -1));
        }

        // Check for OR at the top level
        $orParts = $this->splitByLogicalOperator($expression, ' OR ');
        if (count($orParts) > 1) {
            return $query->where(function ($subQuery) use ($orParts) {
                foreach ($orParts as $index => $part) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $subQuery->{$method}(function ($q) use ($part) {
                        return $this->parseFilterExpression($q, $part);
                    });
                }
            });
        }

        // Check for AND at the top level
        $andParts = $this->splitByLogicalOperator($expression, ' AND ');
        if (count($andParts) > 1) {
            return $query->where(function ($subQuery) use ($andParts) {
                foreach ($andParts as $part) {
                    $subQuery->where(function ($q) use ($part) {
                        return $this->parseFilterExpression($q, $part);
                    });
                }
            });
        }

        // If we get here, we're dealing with a basic condition
        return $this->applyBasicCondition($query, $expression);
    }

    /**
     * Check if the expression has outer parentheses
     */
    protected function hasOuterParentheses(string $expression): bool
    {
        $expression = trim($expression);

        if (substr($expression, 0, 1) === '(' && substr($expression, -1) === ')') {
            // Count parentheses to make sure the outer ones match
            $depth = 0;
            for ($i = 0; $i < strlen($expression) - 1; $i++) {
                if ($expression[$i] === '(') {
                    $depth++;
                } elseif ($expression[$i] === ')') {
                    $depth--;
                }

                // If depth becomes 0 before the end, these aren't outer parentheses
                if ($depth === 0 && $i < strlen($expression) - 2) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Split an expression by a logical operator, respecting parentheses
     */
    protected function splitByLogicalOperator(string $expression, string $operator): array
    {
        $parts = [];
        $currentPart = '';
        $depth = 0;

        $tokens = preg_split('/([\(\)])/', $expression, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($tokens as $token) {
            if ($token === '(') {
                $depth++;
                $currentPart .= $token;
            } elseif ($token === ')') {
                $depth--;
                $currentPart .= $token;
            } elseif ($depth === 0 && $this->stringContains($token, $operator)) {
                // Split the token by the operator
                $subTokens = explode($operator, $token);

                // Add the first part to the current part
                $currentPart .= $subTokens[0];
                $parts[] = trim($currentPart);

                // Start a new current part with the rest
                $currentPart = '';
                for ($i = 1; $i < count($subTokens) - 1; $i++) {
                    $parts[] = trim($subTokens[$i]);
                }

                // The last part becomes the start of the new current part
                $currentPart = $subTokens[count($subTokens) - 1];
            } else {
                $currentPart .= $token;
            }
        }

        // Add the last part if it's not empty
        if (!empty($currentPart)) {
            $parts[] = trim($currentPart);
        }

        return $parts;
    }

    /**
     * Case-insensitive string contains
     */
    protected function stringContains(string $haystack, string $needle): bool
    {
        return strpos(strtolower($haystack), strtolower($needle)) !== false;
    }

    /**
     * Apply a basic condition to the query
     */
    protected function applyBasicCondition(Builder $query, string $condition): Builder
    {
        // Check for relationship filters
        if (strpos($condition, ' HAS_ANY ') !== false) {
            [$relation, $values] = explode(' HAS_ANY ', $condition);
            return $this->applyHasAnyFilter($query, $relation, $values);
        }

        if (strpos($condition, ' IS_ANY ') !== false) {
            [$relation, $values] = explode(' IS_ANY ', $condition);
            return $this->applyIsAnyFilter($query, $relation, $values);
        }

        if (strpos($condition, ' EXISTS') !== false) {
            $relation = trim(str_replace(' EXISTS', '', $condition));
            return $this->applyExistsFilter($query, $relation);
        }

        // Check for attribute filters
        if (strpos($condition, 'attribute:') === 0) {
            return $this->applyAttributeFilter($query, $condition);
        }

        // Handle basic field comparisons
        $operators = ['>=', '<=', '!=', '=', '>', '<', ' LIKE '];

        foreach ($operators as $operator) {
            if (strpos($condition, $operator) !== false) {
                $parts = explode($operator, $condition, 2);
                if (count($parts) === 2) {
                    $field = trim($parts[0]);
                    $value = trim($parts[1]);

                    // Handle IN operator (multiple values)
                    if (strpos($value, '(') === 0 && strpos($value, ')') === strlen($value) - 1) {
                        return $this->applyInFilter($query, $field, $value, $operator);
                    }

                    // Remove quotes if present
                    if ((strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) ||
                        (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
                        $value = substr($value, 1, -1);
                    }

                    // Convert boolean string values to actual booleans for boolean fields
                    if (in_array($field, ['is_remote']) && in_array(strtolower($value), ['true', 'false'])) {
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
    }

    /**
     * Apply HAS_ANY filter for relationships
     */
    protected function applyHasAnyFilter(Builder $query, string $relation, string $values): Builder
    {
        $relation = trim($relation);
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
    }

    /**
     * Apply IS_ANY filter for relationships
     */
    protected function applyIsAnyFilter(Builder $query, string $relation, string $values): Builder
    {
        $relation = trim($relation);
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
    }

    /**
     * Apply EXISTS filter for relationships
     */
    protected function applyExistsFilter(Builder $query, string $relation): Builder
    {
        $relation = trim($relation);
        return $query->has($relation);
    }

    /**
     * Apply IN filter
     */
    protected function applyInFilter(Builder $query, string $field, string $valueList, string $operator): Builder
    {
        $values = $this->parseValueList($valueList);

        if ($operator === '=') {
            return $query->whereIn($field, $values);
        } elseif ($operator === '!=') {
            return $query->whereNotIn($field, $values);
        }

        return $query;
    }

    /**
     * Parse a comma-separated list of values
     */
    protected function parseValueList(string $valueList): array
    {
        // Remove parentheses if present
        if (strpos($valueList, '(') === 0 && strrpos($valueList, ')') === strlen($valueList) - 1) {
            $valueList = substr($valueList, 1, -1);
        }

        // Split by comma and trim each value
        $values = array_map('trim', explode(',', $valueList));

        // Remove quotes if present
        foreach ($values as &$value) {
            if ((strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) ||
                (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
                $value = substr($value, 1, -1);
            }
        }

        return $values;
    }

    /**
     * Apply attribute filter
     */
    protected function applyAttributeFilter(Builder $query, string $condition): Builder
    {
        // Extract attribute name and the rest of the condition
        preg_match('/attribute:([a-zA-Z0-9_]+)(.*)/', $condition, $matches);

        if (count($matches) < 3) {
            return $query;
        }

        $attributeName = $matches[1];
        $restOfCondition = $matches[2];

        // Find the attribute
        $attribute = Attribute::where('name', $attributeName)->first();
        if (!$attribute) {
            return $query;
        }

        // Determine operator and value
        $operators = ['>=', '<=', '!=', '=', '>', '<', ' LIKE '];
        $operator = null;
        $value = null;

        foreach ($operators as $op) {
            if (strpos($restOfCondition, $op) === 0) {
                $operator = $op;
                $value = trim(substr($restOfCondition, strlen($op)));
                break;
            }
        }

        if (!$operator || $value === null) {
            return $query;
        }

        // Remove quotes if present
        if ((strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1) ||
            (strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }

        // Determine the relationship method to use - check both possible names
        $relationMethod = method_exists(Job::class, 'attributeValuesRelation')
            ? 'attributeValuesRelation'
            : 'attributeValues';

        // If neither method exists, try a direct approach with table name
        if (!method_exists(Job::class, $relationMethod)) {
            return $this->applyAttributeFilterWithTableName($query, $attribute, $operator, $value);
        }

        // Handle boolean values
        if (($attribute->type === AttributeTypeEnum::BOOLEAN ||
                (property_exists($attribute, 'type') && $attribute->type === 'boolean')) &&
            in_array(strtolower($value), ['true', 'false', '1', '0'])) {
            $value = in_array(strtolower($value), ['true', '1']) ? 'true' : 'false';
        }

        // Handle IN operator for select type attributes
        if (($attribute->type === AttributeTypeEnum::SELECT ||
                (property_exists($attribute, 'type') && $attribute->type === 'select')) &&
            strpos($value, '(') === 0 &&
            strpos($value, ')') === strlen($value) - 1) {

            $values = $this->parseValueList($value);

            return $query->whereHas($relationMethod, function ($q) use ($attribute, $values, $operator) {
                $q->where('attribute_id', $attribute->id);

                if ($operator === '=') {
                    $q->whereIn('value', $values);
                } elseif ($operator === '!=') {
                    $q->whereNotIn('value', $values);
                }
            });
        }

        // Apply the filter based on attribute type and operator
        return $query->whereHas($relationMethod, function ($q) use ($attribute, $operator, $value) {
            $q->where('attribute_id', $attribute->id);

            switch ($operator) {
                case '=':
                    $q->where('value', $value);
                    break;
                case '!=':
                    $q->where('value', '!=', $value);
                    break;
                case '>':
                    $q->where('value', '>', $value);
                    break;
                case '<':
                    $q->where('value', '<', $value);
                    break;
                case '>=':
                    $q->where('value', '>=', $value);
                    break;
                case '<=':
                    $q->where('value', '<=', $value);
                    break;
                case ' LIKE ':
                    $q->where('value', 'like', "%{$value}%");
                    break;
            }
        });
    }

    /**
     * Direct approach for attribute filtering using table name
     */
    protected function applyAttributeFilterWithTableName(Builder $query, $attribute, $operator, $value): Builder
    {
        // Default table name is job_attribute_values
        $tableName = 'job_attribute_values';

        return $query->whereExists(function ($subQuery) use ($tableName, $attribute, $operator, $value) {
            $subQuery->select(DB::raw(1))
                ->from($tableName)
                ->whereRaw($tableName . '.job_id = jobs.id')
                ->where($tableName . '.attribute_id', $attribute->id);

            switch ($operator) {
                case '=':
                    $subQuery->where($tableName . '.value', $value);
                    break;
                case '!=':
                    $subQuery->where($tableName . '.value', '!=', $value);
                    break;
                case '>':
                    $subQuery->where($tableName . '.value', '>', $value);
                    break;
                case '<':
                    $subQuery->where($tableName . '.value', '<', $value);
                    break;
                case '>=':
                    $subQuery->where($tableName . '.value', '>=', $value);
                    break;
                case '<=':
                    $subQuery->where($tableName . '.value', '<=', $value);
                    break;
                case ' LIKE ':
                    $subQuery->where($tableName . '.value', 'like', "%{$value}%");
                    break;
            }
        });
    }

    /**
     * Sort by attribute
     */
    protected function sortByAttribute(Builder $query, string $attributeName, string $direction = 'asc'): Builder
    {
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            return $query;
        }

        // Determine the name of the job_attribute_values table
        $relationMethod = method_exists(Job::class, 'attributeValuesRelation')
            ? 'attributeValuesRelation'
            : 'attributeValues';

        // Default table name is job_attribute_values
        $tableName = 'job_attribute_values';

        return $query->leftJoin("{$tableName} as sort_values", function ($join) use ($attribute) {
            $join->on('jobs.id', '=', 'sort_values.job_id')
                ->where('sort_values.attribute_id', '=', $attribute->id);
        })
            ->orderBy('sort_values.value', $direction)
            ->select('jobs.*');
    }
}
