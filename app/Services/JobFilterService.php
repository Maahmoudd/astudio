<?php

namespace App\Services;

use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use App\Models\Job;
use App\Repositories\Repository;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobFilterService implements IJobFilterService
{
    protected $jobRepository;
    protected $validRelations = ['languages', 'locations', 'categories', 'attributeValues', 'attributeValuesRelation'];
    protected $validFields = ['id', 'title', 'description', 'company_name', 'salary_min', 'salary_max', 'is_remote', 'job_type', 'status', 'published_at', 'created_at', 'updated_at'];

    public function __construct()
    {
        $this->jobRepository = Repository::getRepository('Job');
    }

    /**
     * Apply filters to a job query
     *
     * @param array $filters The filters to apply
     * @return Builder
     */
    public function applyFilters(array $filters): Builder
    {
        $query = $this->jobRepository->getModel()->query();

        try {
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
                    // Validate the sort field
                    if ($this->isValidField($sortField)) {
                        $query->orderBy($sortField, $sortDirection);
                    } else {
                        Log::warning("Invalid sort field: {$sortField}");
                        // Use a default sort if field is invalid
                        $query->orderBy('created_at', 'desc');
                    }
                }
            }
        } catch (Exception $e) {
            // Log the error and return a basic query
            Log::error('Filter error: ' . $e->getMessage(), [
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            // Return a valid query even if filtering fails
            return $this->jobRepository->getModel()->query();
        }

        return $query;
    }

    /**
     * Check if the provided field name is valid for the Job model
     */
    protected function isValidField(string $field): bool
    {
        return in_array($field, $this->validFields);
    }

    /**
     * Check if the provided relation name is valid
     */
    protected function isValidRelation(string $relation): bool
    {
        return in_array($relation, $this->validRelations);
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

        if (str_starts_with($expression, '(') && substr($expression, -1) === ')') {
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
        try {
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
                    $parts = explode($operator, $condition, 2);
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
        } catch (Exception $e) {
            Log::error("Error applying basic condition: {$condition}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $query;
    }

    /**
     * Apply HAS_ANY filter for relationships
     */
    protected function applyHasAnyFilter(Builder $query, string $relation, string $values): Builder
    {
        $relation = trim($relation);

        // Validate relation name
        if (!$this->relationExists($relation)) {
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
    }

    /**
     * Check if a relation exists on the Job model
     */
    protected function relationExists(string $relation): bool
    {
        try {
            $jobModel = $this->jobRepository->getModel();
            if (method_exists($jobModel, $relation)) {
                return true;
            }

            return in_array($relation, $this->validRelations);
        } catch (Exception $e) {
            Log::error("Error checking relation: {$relation}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Apply IS_ANY filter for relationships
     */
    protected function applyIsAnyFilter(Builder $query, string $relation, string $values): Builder
    {
        $relation = trim($relation);

        // Validate relation name
        if (!$this->relationExists($relation)) {
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
    }

    /**
     * Apply EXISTS filter for relationships
     */
    protected function applyExistsFilter(Builder $query, string $relation): Builder
    {
        $relation = trim($relation);

        // Validate relation name
        if (!$this->relationExists($relation)) {
            Log::warning("Invalid relation in EXISTS filter: {$relation}");
            return $query;
        }

        return $query->has($relation);
    }

    /**
     * Apply IN filter
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

    /**
     * Parse a comma-separated list of values
     */
    protected function parseValueList(string $valueList): array
    {
        try {
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
        } catch (Exception $e) {
            Log::error("Error parsing value list: {$valueList}", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Apply attribute filter
     */
    protected function applyAttributeFilter(Builder $query, string $condition): Builder
    {
        try {
            // Extract attribute name and the rest of the condition
            if (!preg_match('/attribute:([a-zA-Z0-9_]+)(.*)/', $condition, $matches)) {
                Log::warning("Invalid attribute filter format: {$condition}");
                return $query;
            }

            if (count($matches) < 3) {
                return $query;
            }

            $attributeName = $matches[1];
            $restOfCondition = $matches[2];

            // Find the attribute
            $attribute = Attribute::where('name', $attributeName)->first();
            if (!$attribute) {
                Log::warning("Attribute not found: {$attributeName}");
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
                Log::warning("Invalid operator or missing value in attribute filter: {$condition}");
                return $query;
            }

            // Remove quotes if present
            if ((str_starts_with($value, "'") && strrpos($value, "'") === strlen($value) - 1) ||
                (str_starts_with($value, '"') && strrpos($value, '"') === strlen($value) - 1)) {
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
                str_starts_with($value, '(') &&
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
        } catch (Exception $e) {
            Log::error("Error applying attribute filter: {$condition}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $query;
        }
    }

    /**
     * Direct approach for attribute filtering using table name
     */
    protected function applyAttributeFilterWithTableName(Builder $query, $attribute, $operator, $value): Builder
    {
        try {
            return $query->whereExists(function ($subQuery) use ($attribute, $operator, $value) {
                $tableName = 'job_attribute_values';
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
        } catch (Exception $e) {
            Log::error("Error in attribute filter with table name", [
                'error' => $e->getMessage(),
                'attribute' => $attribute->name ?? 'unknown',
                'operator' => $operator,
                'value' => $value
            ]);
            return $query;
        }
    }

    /**
     * Sort by attribute
     */
    protected function sortByAttribute(Builder $query, string $attributeName, string $direction = 'asc'): Builder
    {
        try {
            $attribute = Attribute::where('name', $attributeName)->first();

            if (!$attribute) {
                Log::warning("Attribute not found for sorting: {$attributeName}");
                return $query->orderBy('created_at', 'desc'); // Default sort
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
        } catch (Exception $e) {
            Log::error("Error sorting by attribute: {$attributeName}", [
                'error' => $e->getMessage()
            ]);
            return $query->orderBy('created_at', 'desc'); // Default sort on error
        }
    }
}
