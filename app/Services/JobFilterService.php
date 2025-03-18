<?php

namespace App\Services;

use App\Filters\FilterRegistry;
use App\Models\Attribute;
use App\Repositories\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class JobFilterService implements IJobFilterService
{
    /**
     * @var Repository Job repository
     */
    protected $jobRepository;

    /**
     * @var FilterRegistry Registry of filter conditions
     */
    protected $filterRegistry;

    /**
     * @var array<string> Valid fields for sorting
     */
    protected array $validSortFields = [
        'id', 'title', 'description', 'company_name', 'salary_min',
        'salary_max', 'is_remote', 'job_type', 'status', 'published_at',
        'created_at', 'updated_at'
    ];

    /**
     * Constructor
     *
     * @param FilterRegistry $filterRegistry Registry of filter conditions
     */
    public function __construct(FilterRegistry $filterRegistry)
    {
        $this->jobRepository = Repository::getRepository('Job');
        $this->filterRegistry = $filterRegistry;
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
                    if (in_array($sortField, $this->validSortFields)) {
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
        // Find the appropriate filter from the registry
        $filter = $this->filterRegistry->getFilterFor($expression);

        if ($filter) {
            return $filter->apply($query, $expression);
        }

        // If no filter can handle this expression, log a warning
        Log::warning("No filter handler found for expression: {$expression}");
        return $query;
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
