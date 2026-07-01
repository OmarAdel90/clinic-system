<?php

namespace Modules\Patient\Controllers;

use App\Http\Controllers\Controller;
use Modules\Lead\Models\Lead;
use Modules\Patient\Models\MedicalRecord;
use Modules\Patient\Requests\IndexMedicalRecordRequest;
use Modules\Patient\Requests\ShowMedicalRecordRequest;
use Modules\Patient\Requests\StoreMedicalRecordRequest;
use Modules\Patient\Requests\UpdateMedicalRecordRequest;
use Modules\Patient\Requests\DestroyMedicalRecordRequest;
use Modules\Patient\Services\MedicalRecordService;
use Illuminate\Http\JsonResponse;

class MedicalRecordController extends Controller
{
    public function __construct(protected MedicalRecordService $service) {}

    public function index(IndexMedicalRecordRequest $request, int $leadId): JsonResponse
    {
        return response()->json($this->service->getByLead($leadId, $request->user()));
    }

    public function store(int $leadId, StoreMedicalRecordRequest $request): JsonResponse
    {
        
        Lead::findOrFail($leadId);

        $record = $this->service->upload($leadId, $request->validated());

        return response()->json($record, 201);
    }

    public function show(ShowMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        return response()->json($medicalRecord);
    }

    public function update(UpdateMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        return response()->json($this->service->update($medicalRecord, $request->validated()));
    }

    public function destroy(DestroyMedicalRecordRequest $request, MedicalRecord $medicalRecord): JsonResponse
    {
        $this->service->delete($medicalRecord);

        return response()->json(null, 204);
    }

    public function file(ShowMedicalRecordRequest $request, MedicalRecord $medicalRecord): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->file(
            $this->service->filePath($medicalRecord),
            ['Content-Type' => $medicalRecord->mime_type]
        );
    }

    public function download(ShowMedicalRecordRequest $request, MedicalRecord $medicalRecord): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return response()->download(
            $this->service->filePath($medicalRecord),
            $medicalRecord->original_name,
            ['Content-Type' => $medicalRecord->mime_type]
        );
    }
}
