<?php

namespace App\Models;

use App\Enums\AttributeTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'attribute_id',
        'value',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function getTypedValueAttribute()
    {
        if (!$this->attribute) {
            return $this->value;
        }

        switch ($this->attribute->type) {
            case AttributeTypeEnum::NUMBER:
                return is_numeric($this->value) ? (float) $this->value : null;

            case AttributeTypeEnum::BOOLEAN:
                return filter_var($this->value, FILTER_VALIDATE_BOOLEAN);

            case AttributeTypeEnum::DATE:
                return $this->value ? date('Y-m-d', strtotime($this->value)) : null;

            case AttributeTypeEnum::SELECT:
            case AttributeTypeEnum::TEXT:
            default:
                return $this->value;
        }
    }
}
