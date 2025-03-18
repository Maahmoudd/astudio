<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobsIndexRequest;
use App\Http\Resources\JobResource;
use App\Services\IJobFilterService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(JobsIndexRequest $request, IJobFilterService $filterService)
    {
        $validatedRequest = $request->validated();

        $query = $filterService->applyFilters($validatedRequest);

        $query->with(['languages', 'locations', 'categories', 'attributeValuesRelation.attribute']);

        $perPage = $request['per_page'] ?? 15;
        $jobs = $query->paginate($perPage);

        return JobResource::collection($jobs);
    }
}
