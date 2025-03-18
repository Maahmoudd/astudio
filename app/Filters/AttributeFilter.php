<?php

namespace App\Filters;

use App\Enums\AttributeTypeEnum;
use App\Models\Attribute;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AttributeFilter extends AbstractFilterCondition
{
    /**
     * Check if this filter can handle the given expression
     *
     * @param string $expression
     * @return bool
     */
    public function canHandle(string $expression): bool
    {
        return str_starts_with($expression, 'attribute:');
    }

    /**
     * Apply the attribute filter to the query
     *
     * @param Builder $query
     * @param string $expression
     * @return Builder
     */
    public function apply(Builder $query, string $expression): Builder
    {
        try {
            // Extract attribute name and the rest of the condition
            if (!preg_match('/attribute:([a-zA-Z0-9_]+)(.*)/', $expression, $matches)) {
                Log::warning("Invalid attribute filter format: {$expression}");
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
                Log::warning("Invalid operator or missing value in attribute filter: {$expression}");
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
            Log::error("Error applying attribute filter: {$expression}", [
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
}
