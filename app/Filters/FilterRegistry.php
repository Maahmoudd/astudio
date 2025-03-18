<?php

namespace App\Filters;

class FilterRegistry
{
    /**
     * Array of registered filter conditions
     *
     * @var array<FilterCondition>
     */
    private array $filters = [];

    /**
     * Register a new filter condition
     *
     * @param FilterCondition $filter The filter to register
     * @return void
     */
    public function register(FilterCondition $filter): void
    {
        $this->filters[] = $filter;
    }

    /**
     * Get the appropriate filter for an expression
     *
     * @param string $expression The filter expression
     * @return FilterCondition|null The filter that can handle the expression, or null if none can
     */
    public function getFilterFor(string $expression): ?FilterCondition
    {
        foreach ($this->filters as $filter) {
            if ($filter->canHandle($expression)) {
                return $filter;
            }
        }

        return null;
    }

    /**
     * Get all registered filters
     *
     * @return array<FilterCondition>
     */
    public function getAll(): array
    {
        return $this->filters;
    }
}
