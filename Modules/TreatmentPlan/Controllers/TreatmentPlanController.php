<?php

namespace Modules\TreatmentPlan\Controllers;

use App\Http\Controllers\Controller;
use Modules\TreatmentPlan\Models\TreatmentPlan;
use Modules\TreatmentPlan\Requests\IndexTreatmentPlanRequest;
use Modules\TreatmentPlan\Requests\ShowTreatmentPlanRequest;
use Modules\TreatmentPlan\Requests\TreatmentPlanRequest;
use Modules\TreatmentPlan\Requests\UpdateTreatmentPlanRequest;
use Modules\TreatmentPlan\Requests\DestroyTreatmentPlanRequest;
use Modules\TreatmentPlan\Services\TreatmentPlanService;
use Illuminate\Http\JsonResponse;

class TreatmentPlanController extends Controller
{
    public function __construct(protected TreatmentPlanService $service) {}

    public function index(IndexTreatmentPlanRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user()));
    }

    public function show(ShowTreatmentPlanRequest $request, TreatmentPlan $treatmentPlan): JsonResponse
    {
        return response()->json($this->service->get($treatmentPlan->id, $request->user()));
    }

    public function store(TreatmentPlanRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->create($request->validated()), 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateTreatmentPlanRequest $request, TreatmentPlan $treatmentPlan): JsonResponse
    {
        $treatmentPlan->update($request->validated());
        return response()->json($treatmentPlan->fresh());
    }

    public function destroy(DestroyTreatmentPlanRequest $request, TreatmentPlan $treatmentPlan): JsonResponse
    {
        $treatmentPlan->delete();
        return response()->json(null, 204);
    }
}
