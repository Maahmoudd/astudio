<?php

namespace App\Models;

use App\Enums\AttributeTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'options',
    ];

    protected $casts = [
        'options' => 'json',
    ];

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_attribute_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(JobAttributeValue::class);
    }

    public function isSelect(): bool
    {
        return $this->type === AttributeTypeEnum::SELECT->value;
    }

    public function getSelectOptions(): array
    {
        if (!$this->isSelect()) {
            return [];
        }

        return $this->options ?? [];
    }
}
