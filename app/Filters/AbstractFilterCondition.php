<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class AbstractFilterCondition implements FilterCondition
{
    /**
     * Valid model fields
     *
     * @var array<string>
     */
    protected array $validFields = [
        'id', 'title', 'description', 'company_name', 'salary_min',
        'salary_max', 'is_remote', 'job_type', 'status', 'published_at',
        'created_at', 'updated_at'
    ];

    /**
     * Valid model relations
     *
     * @var array<string>
     */
    protected array $validRelations = [
        'languages', 'locations', 'categories',
        'attributeValuesRelation', 'attributeValues'
    ];

    /**
     * Check if a field is valid for the Job model
     *
     * @param string $field Field name to check
     * @return bool
     */
    protected function isValidField(string $field): bool
    {
        return in_array($field, $this->validFields);
    }

    /**
     * Check if a relation exists for the Job model
     *
     * @param string $relation Relation name to check
     * @return bool
     */
    protected function isValidRelation(string $relation): bool
    {
        return in_array($relation, $this->validRelations);
    }

    /**
     * Parse a comma-separated list of values into an array
     *
     * @param string $valueList The comma-separated list of values
     * @return array<string>
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
}
