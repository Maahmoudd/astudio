<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Format attributes as key-value pairs
        $attributes = [];
        foreach ($this->attributeValuesRelation as $attributeValue) {
            $attributes[$attributeValue->attribute->name] = $attributeValue->typed_value;
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'company_name' => $this->company_name,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'is_remote' => $this->is_remote,
            'job_type' => $this->job_type,
            'status' => $this->status,
            'published_at' => $this->published_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Include relationships
            'languages' => LanguageResource::collection($this->whenLoaded('languages')),
            'locations' => LocationResource::collection($this->whenLoaded('locations')),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),

            // Include the dynamic attributes
            'attributes' => $attributes,

        ];
    }
}
