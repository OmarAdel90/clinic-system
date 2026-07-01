<?php

namespace Modules\Patient\Controllers;

use App\Http\Controllers\Controller;
use Modules\Patient\Models\PatientFeedback;
use Modules\Patient\Requests\IndexPatientFeedbackRequest;
use Modules\Patient\Requests\ShowPatientFeedbackRequest;
use Modules\Patient\Requests\StorePatientFeedbackRequest;
use Modules\Patient\Requests\UpdatePatientFeedbackRequest;
use Modules\Patient\Requests\DestroyPatientFeedbackRequest;
use Modules\Patient\Services\PatientFeedbackService;
use Illuminate\Http\JsonResponse;

class PatientFeedbackController extends Controller
{
    public function __construct(protected PatientFeedbackService $service) {}

    public function index(IndexPatientFeedbackRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user()));
    }

    public function show(ShowPatientFeedbackRequest $request, PatientFeedback $patientFeedback): JsonResponse
    {
        return response()->json($this->service->get($patientFeedback->id, $request->user()));
    }

    public function store(StorePatientFeedbackRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function update(UpdatePatientFeedbackRequest $request, PatientFeedback $patientFeedback): JsonResponse
    {
        return response()->json($this->service->update($patientFeedback, $request->validated()));
    }

    public function destroy(DestroyPatientFeedbackRequest $request, PatientFeedback $patientFeedback): JsonResponse
    {
        $this->service->delete($patientFeedback);
        return response()->json(null, 204);
    }
}
