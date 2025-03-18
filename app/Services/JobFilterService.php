<?php

namespace App\Services;

use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;

class JobFilterService implements IJobFilterService
{

    public function applyFilters(array $filters): Builder
    {
        $query = Job::query();

        // If there's a filter parameter, parse and apply it
        if (!empty($filters['filter'])) {
            $filterString = $filters['filter'];
            $query = $this->parseFilterExpression($query, $filterString);
        }

        // Apply sorting
        if (!empty($filters['sort_by'])) {
            $sortField = $filters['sort_by'];
            $sortDirection = $filters['sort_direction'] ?? 'asc';

            // Check if sorting by an attribute
            if (str_starts_with($sortField, 'attribute:')) {
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
        if (str_starts_with($expression, '(') && str_ends_with($expression, ')')) {
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
        return str_contains(strtolower($haystack), strtolower($needle));
    }

    /**
     * Apply a basic condition to the query
     */
    protected function applyBasicCondition(Builder $query, string $condition): Builder
    {
        // Check for relationship filters
        if (str_contains($condition, ' HAS_ANY ')) {
            [$relation, $values] = explode(' HAS_ANY ', $condition);
            return $this->applyHasAnyFilter($query, $relation, $values);
        }

        if (str_contains($condition, ' IS_ANY ')) {
            [$relation, $values] = explode(' IS_ANY ', $condition);
            return $this->applyIsAnyFilter($query, $relation, $values);
        }

        if (str_contains($condition, ' EXISTS')) {
            $relation = trim(str_replace(' EXISTS', '', $condition));
            return $this->applyExistsFilter($query, $relation);
        }

        // Check for attribute filters
        if (str_starts_with($condition, 'attribute:')) {
            return $this->applyAttributeFilter($query, $condition);
        }

        // Handle basic field comparisons
        $operators = ['>=', '<=', '!=', '=', '>', '<', ' LIKE '];

        foreach ($operators as $operator) {
            if (str_contains($condition, $operator)) {
                [$field, $value] = explode($operator, $condition, 2);
                $field = trim($field);
                $value = trim($value);

                // Handle IN operator (multiple values)
                if (str_starts_with($value, '(') && strpos($value, ')') === strlen($value) - 1) {
                    return $this->applyInFilter($query, $field, $value, $operator);
                }

                // Remove quotes if present
                if ((str_starts_with($value, "'") && strrpos($value, "'") === strlen($value) - 1) ||
                    (str_starts_with($value, '"') && strrpos($value, '"') === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
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

        // If we reach here, the condition couldn't be parsed
        return $query;
    }

    /**
     * Apply HAS_ANY filter for relationships
     */
    protected function applyHasAnyFilter(Builder $query, string $relation, string $values): Builder
    {
        $relation = trim($relation);
        $values = $this->parseValueList($values);

        return $query->whereHas($relation, function ($q) use ($values) {
            $q->whereIn('name', $values);
        });
    }

    /**
     * Apply IS_ANY filter for relationships
     */
    protected function applyIsAnyFilter(Builder $query, string $relation, string $values): Builder
    {
        $relation = trim($relation);
        $values = $this->parseValueList($values);

        // Special case for locations to handle "Remote"
        if ($relation === 'locations' && in_array('Remote', $values)) {
            $key = array_search('Remote', $values);
            if ($key !== false) {
                unset($values[$key]);

                return $query->where(function ($q) use ($relation, $values) {
                    $q->where('is_remote', true);

                    if (!empty($values)) {
                        $q->orWhereHas($relation, function ($locationQuery) use ($values) {
                            $locationQuery->whereIn('city', $values);
                        });
                    }
                });
            }
        }

        return $query->whereHas($relation, function ($q) use ($values) {
            $q->whereIn('name', $values);
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
        if (str_starts_with($valueList, '(') && strrpos($valueList, ')') === strlen($valueList) - 1) {
            $valueList = substr($valueList, 1, -1);
        }

        // Split by comma and trim each value
        $values = array_map('trim', explode(',', $valueList));

        // Remove quotes if present
        foreach ($values as &$value) {
            if ((str_starts_with($value, "'") && strrpos($value, "'") === strlen($value) - 1) ||
                (str_starts_with($value, '"') && strrpos($value, '"') === strlen($value) - 1)) {
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
            if (str_starts_with($restOfCondition, $op)) {
                $operator = $op;
                $value = trim(substr($restOfCondition, strlen($op)));
                break;
            }
        }

        if (!$operator || $value === null) {
            return $query;
        }

        // Remove quotes if present
        if ((str_starts_with($value, "'") && strrpos($value, "'") === strlen($value) - 1) ||
            (str_starts_with($value, '"') && strrpos($value, '"') === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }

        // Handle IN operator for select type attributes
        if ($attribute->type === AttributeTypeEnum::SELECT &&
            str_starts_with($value, '(') &&
            strpos($value, ')') === strlen($value) - 1) {

            $values = $this->parseValueList($value);

            return $query->whereHas('attributeValuesRelation', function ($q) use ($attribute, $values, $operator) {
                $q->where('attribute_id', $attribute->id);

                if ($operator === '=') {
                    $q->whereIn('value', $values);
                } elseif ($operator === '!=') {
                    $q->whereNotIn('value', $values);
                }
            });
        }

        // Apply the filter based on attribute type and operator
        return $query->whereHas('attributeValuesRelation', function ($q) use ($attribute, $operator, $value) {
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
     * Sort by attribute
     */
    protected function sortByAttribute(Builder $query, string $attributeName, string $direction = 'asc'): Builder
    {
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            return $query;
        }

        return $query->leftJoin('job_attribute_values as sort_values', function ($join) use ($attribute) {
            $join->on('jobs.id', '=', 'sort_values.job_id')
                ->where('sort_values.attribute_id', '=', $attribute->id);
        })
            ->orderBy('sort_values.value', $direction)
            ->select('jobs.*');
    }
}
