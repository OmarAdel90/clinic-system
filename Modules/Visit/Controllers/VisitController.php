<?php

namespace Modules\Visit\Controllers;

use App\Http\Controllers\Controller;
use Modules\Visit\Models\Visit;
use Modules\Visit\Requests\IndexVisitRequest;
use Modules\Visit\Requests\ShowVisitRequest;
use Modules\Visit\Requests\StoreVisitRequest;
use Modules\Visit\Requests\UpdateVisitRequest;
use Modules\Visit\Requests\DestroyVisitRequest;
use Modules\Visit\Services\VisitService;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function __construct(protected VisitService $service) {}

    public function index(IndexVisitRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user()));
    }

    public function show(ShowVisitRequest $request, Visit $visit): JsonResponse
    {
        return response()->json($this->service->get($visit->id, $request->user()));
    }

    public function store(StoreVisitRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->create($request->validated()), 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateVisitRequest $request, Visit $visit): JsonResponse
    {
        try {
            return response()->json($this->service->update($visit, $request->validated()));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(DestroyVisitRequest $request, Visit $visit): JsonResponse
    {
        $this->service->delete($visit);
        return response()->json(null, 204);
    }
}
