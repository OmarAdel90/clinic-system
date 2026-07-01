<?php

namespace Modules\Warehouse\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Requests\DestroyWarehouseRequest;
use Modules\Warehouse\Requests\IndexWarehouseRequest;
use Modules\Warehouse\Requests\ShowWarehouseRequest;
use Modules\Warehouse\Requests\StoreWarehouseRequest;
use Modules\Warehouse\Requests\UpdateWarehouseRequest;
use Modules\Warehouse\Services\WarehouseService;

class WarehouseController extends Controller
{
    public function __construct(protected WarehouseService $service) {}

    public function index(IndexWarehouseRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->getAll($request->user()));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }

    public function show(ShowWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            return response()->json($this->service->get($warehouse->id, $request->user()));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        try {
            $warehouse = $this->service->create($request->validated());

            return response()->json($warehouse, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            $result = $this->service->update(
                $warehouse,
                $request->validated(),
                $request->boolean('confirm_reassign')
            );

            if (is_array($result) && ($result['conflict'] ?? false)) {
                return response()->json([
                    'message' => "Clinic {$result['clinic_id']} already has warehouse {$result['current_warehouse_id']} assigned.",
                    'conflict' => [
                        'clinic_id' => $result['clinic_id'],
                        'current_warehouse_id' => $result['current_warehouse_id'],
                    ],
                ], 409);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], $code ?: 500);
        }
    }

    public function destroy(DestroyWarehouseRequest $request, Warehouse $warehouse): JsonResponse
    {
        try {
            $this->service->delete($warehouse);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            $code = (int) $e->getCode();

            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], $code ?: 500);
        }
    }
}
