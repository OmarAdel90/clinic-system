<?php

namespace Modules\Visit\Controllers;

use App\Http\Controllers\Controller;
use Modules\Visit\Models\Visit;
use Modules\Visit\Requests\VisitReportRequest;
use Modules\Visit\Services\VisitFlowService;
use Illuminate\Http\JsonResponse;

class VisitFlowController extends Controller
{
    public function __construct(protected VisitFlowService $service) {}

    public function confirm(Visit $visit): JsonResponse
    {
        try {
            return response()->json($this->service->confirmAppointment($visit->id));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function complete(Visit $visit, VisitReportRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->completeVisit($visit->id, $request->validated()), 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Visit $visit): JsonResponse
    {
        try {
            return response()->json($this->service->cancelVisit($visit->id));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function miss(Visit $visit): JsonResponse
    {
        try {
            return response()->json($this->service->markMissed($visit->id));
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
