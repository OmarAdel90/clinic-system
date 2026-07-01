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
        $this->verifyAccess($leadId);

        return response()->json($this->service->getByLead($leadId, $request->user()));
    }

    public function show(ShowMedicalRecordRequest $request, int $leadId, MedicalRecord $medicalRecord): JsonResponse
    {
        abort_if($medicalRecord->lead_id !== $leadId, 404);
        $this->verifyAccess($leadId);

        return response()->json($medicalRecord);
    }

    public function store(int $leadId, StoreMedicalRecordRequest $request): JsonResponse
    {
        Lead::findOrFail($leadId);

        $record = $this->service->upload($leadId, $request->validated());

        return response()->json($record, 201);
    }

    public function update(int $leadId, MedicalRecord $medicalRecord, UpdateMedicalRecordRequest $request): JsonResponse
    {
        abort_if($medicalRecord->lead_id !== $leadId, 404);

        return response()->json($this->service->update($medicalRecord, $request->validated()));
    }

    public function destroy(DestroyMedicalRecordRequest $request, int $leadId, MedicalRecord $medicalRecord): JsonResponse
    {
        abort_if($medicalRecord->lead_id !== $leadId, 404);

        $this->service->delete($medicalRecord);

        return response()->json(null, 204);
    }

    public function download(int $leadId, MedicalRecord $medicalRecord): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_if($medicalRecord->lead_id !== $leadId, 404);
        $this->verifyAccess($leadId);

        return response()->download(
            $this->service->filePath($medicalRecord),
            $medicalRecord->original_name,
            ['Content-Type' => $medicalRecord->mime_type]
        );
    }

    protected function verifyAccess(int $leadId): void
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return;
        }

        abort(403);
    }
}
