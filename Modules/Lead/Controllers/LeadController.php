<?php

namespace Modules\Lead\Controllers;

use App\Http\Controllers\Controller;
use Modules\Lead\Models\Lead;
use Modules\Lead\Requests\AssignLeadClinicRequest;
use Modules\Lead\Requests\IndexLeadRequest;
use Modules\Lead\Requests\IndexLeadPickerRequest;
use Modules\Lead\Requests\ShowLeadRequest;
use Modules\Lead\Requests\StoreLeadRequest;
use Modules\Lead\Requests\UpdateLeadRequest;
use Modules\Lead\Requests\DestroyLeadRequest;
use Modules\Lead\Services\LeadService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(protected LeadService $service) {}

    public function index(IndexLeadRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user(), $request->validated()));
    }

    public function picker(IndexLeadPickerRequest $request): JsonResponse
    {
        return response()->json($this->service->getPickerOptions($request->user(), $request->validated()));
    }

    public function show(ShowLeadRequest $request, int $id): JsonResponse
    {
        try {
            return response()->json($this->service->get($id, $request->user()));
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lead not found.'], 404);
        }
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        try {
            $lead = $this->service->create($request->validated());
            return response()->json($lead, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateLeadRequest $request, Lead $lead): JsonResponse
    {
        return response()->json($this->service->update($lead, $request->validated()));
    }

    public function destroy(DestroyLeadRequest $request, Lead $lead): JsonResponse
    {
        $this->service->delete($lead);
        return response()->json(null, 204);
    }

    public function assignClinic(AssignLeadClinicRequest $request, Lead $lead): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'Lead assigned to clinic successfully.',
                'lead' => $this->service->assignClinic($lead, $request->validated()['clinic_id'], $request->user()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
