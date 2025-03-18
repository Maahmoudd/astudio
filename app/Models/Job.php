<?php

namespace App\Models;

use App\Services\IJobFilterService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'company_name',
        'salary_min',
        'salary_max',
        'is_remote',
        'job_type',
        'status',
        'published_at',
    ];

    protected $casts = [
        'is_remote' => 'boolean',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    /**
     * Scope to filter jobs based on a complete filter string
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        return app(IJobFilterService::class)->applyFilters($filters);
    }


    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function attributesRelation(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'job_attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function attributeValuesRelation(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class);
    }

    /**
     * Get an attribute value by attribute name
     */
    public function getAttributeValueRelation($key)
    {
        $attributeValue = $this->attributeValues()
            ->whereHas('attribute', function ($query) use ($key) {
                $query->where('name', $key);
            })
            ->with('attribute')
            ->first();

        return $attributeValue ? $attributeValue->typed_value : null;
    }

    /**
     * Set an attribute value
     */
    public function setAttributeValueRelation(string $attributeName, $value): bool
    {
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            return false;
        }

        $attributeValue = $this->attributeValuesRelation()
            ->where('attribute_id', $attribute->id)
            ->first();

        if ($attributeValue) {
            $attributeValue->update(['value' => $value]);
        } else {
            $this->attributeValuesRelation()->create([
                'attribute_id' => $attribute->id,
                'value' => $value,
            ]);
        }

        return true;
    }

    /**
     * Set multiple attribute values at once
     *
     * @param array $attributes Array of attribute name => value pairs
     * @return bool
     */
    public function setAttributeValuesRelation(array $attributes): bool
    {
        $success = true;

        foreach ($attributes as $name => $value) {
            $result = $this->setAttributeValueRelation($name, $value);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }
}
