<?php

namespace Modules\Clinic\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Clinic\Models\Clinic;
use Modules\Clinic\Requests\DestroyClinicRequest;
use Modules\Clinic\Requests\IndexClinicRequest;
use Modules\Clinic\Requests\ShowClinicRequest;
use Modules\Clinic\Requests\StoreClinicRequest;
use Modules\Clinic\Requests\UpdateClinicRequest;
use Modules\Clinic\Services\ClinicService;

class ClinicController extends Controller
{
    public function __construct(protected ClinicService $service) {}

    public function index(IndexClinicRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user()));
    }

    public function show(ShowClinicRequest $request, Clinic $clinic): JsonResponse
    {
        return response()->json($clinic->load('warehouse'));
    }

    public function store(StoreClinicRequest $request): JsonResponse
    {
        $clinic = $this->service->create($request->validated());

        return response()->json($clinic, 201);
    }

    public function update(UpdateClinicRequest $request, Clinic $clinic): JsonResponse
    {
        $result = $this->service->update($clinic, $request->validated(), $request->boolean('confirm_reassign'));

        if (is_array($result) && ($result['conflict'] ?? false)) {
            $warehouseName = $result['warehouse_name'] ?? "Warehouse {$result['warehouse_id']}";
            $clinicName = $result['current_clinic_name'] ?? "Clinic {$result['current_clinic_id']}";

            return response()->json([
                'message' => "{$warehouseName} is already assigned to {$clinicName}.",
                'conflict' => [
                    'warehouse_id' => $result['warehouse_id'],
                    'current_clinic_id' => $result['current_clinic_id'],
                    'warehouse_name' => $result['warehouse_name'] ?? null,
                    'current_clinic_name' => $result['current_clinic_name'] ?? null,
                ],
            ], 409);
        }

        return response()->json($result);
    }

    public function destroy(DestroyClinicRequest $request, Clinic $clinic): JsonResponse
    {
        $this->service->delete($clinic);

        return response()->json(null, 204);
    }
}
