<?php

namespace App\Http\Controllers;

use App\Http\Resources\JobResource;
use App\Services\IJobFilterService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request, IJobFilterService $filterService)
    {
        $filters = $request->all();

        $query = $filterService->applyFilters($filters);

        // Add with relationships for better performance
        $query->with(['languages', 'locations', 'categories', 'attributeValuesRelation.attribute']);

        // Apply pagination
        $perPage = $request->input('per_page', 15);
        $jobs = $query->paginate($perPage);

        return JobResource::collection($jobs);
    }
}
