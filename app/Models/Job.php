<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'job_attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class);
    }

    /**
     * Get an attribute value by attribute name
     */
    public function getAttributeValue($key)
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
    public function setAttributeValue(string $attributeName, $value): bool
    {
        $attribute = Attribute::where('name', $attributeName)->first();

        if (!$attribute) {
            return false;
        }

        $attributeValue = $this->attributeValues()
            ->where('attribute_id', $attribute->id)
            ->first();

        if ($attributeValue) {
            $attributeValue->update(['value' => $value]);
        } else {
            $this->attributeValues()->create([
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
    public function setAttributeValues(array $attributes): bool
    {
        $success = true;

        foreach ($attributes as $name => $value) {
            $result = $this->setAttributeValue($name, $value);
            if (!$result) {
                $success = false;
            }
        }

        return $success;
    }
}
