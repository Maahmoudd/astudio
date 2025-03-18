<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'city',
        'state',
        'country',
    ];

    /**
     * Get the full location name (city, state, country)
     */
    public function getFullNameAttribute(): string
    {
        $parts = [$this->city];

        if ($this->state) {
            $parts[] = $this->state;
        }

        $parts[] = $this->country;

        return implode(', ', $parts);
    }

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class);
    }
}
