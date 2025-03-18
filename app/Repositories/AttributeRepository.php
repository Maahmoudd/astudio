<?php

namespace App\Repositories;

use App\Models\Attribute;

class AttributeRepository extends Repository
{
    /**
     * Find an attribute by name
     *
     * @param string $name
     * @return Attribute|null
     */
    public function findByName(string $name): ?Attribute
    {
        return Attribute::where('name', $name)->first();
    }
}
