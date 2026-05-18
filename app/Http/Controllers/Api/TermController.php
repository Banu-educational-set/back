<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Term\StoreTermRequest;
use App\Http\Requests\Term\UpdateTermRequest;
use App\Http\Resources\TermResource;
use App\Models\Term;
use App\Services\TermService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TermController extends Controller
{
    public function __construct(private readonly TermService $termService) {}

    public function index(Request $request): JsonResponse
    {
        $terms = $this->termService->paginate(
            activeOnly: $request->boolean('active_only') ?: null,
            perPage: (int) $request->integer('per_page', 20),
        );

        return ApiResponse::success(TermResource::collection($terms));
    }

    public function show(Term $term): JsonResponse
    {
        return ApiResponse::success(new TermResource($term));
    }

    public function store(StoreTermRequest $request): JsonResponse
    {
        $term = $this->termService->create($request->validated(), $request->user());

        return ApiResponse::success(new TermResource($term), 'Term created.', 201);
    }

    public function update(UpdateTermRequest $request, Term $term): JsonResponse
    {
        $updated = $this->termService->update($term, $request->validated(), $request->user());

        return ApiResponse::success(new TermResource($updated), 'Term updated.');
    }

    public function destroy(Term $term): JsonResponse
    {
        $this->authorize('delete', $term);
        $this->termService->delete($term);

        return ApiResponse::success(null, 'Term deleted.');
    }
}
