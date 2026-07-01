<?php
namespace Modules\Pharmaceutical\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Pharmaceutical\Requests\DestroyPharmaceuticalRequest;
use Modules\Pharmaceutical\Requests\IndexPharmaceuticalRequest;
use Modules\Pharmaceutical\Requests\ShowPharmaceuticalRequest;
use Modules\Pharmaceutical\Requests\StorePharmaceuticalRequest;
use Modules\Pharmaceutical\Requests\UpdatePharmaceuticalRequest;
use Modules\Pharmaceutical\Services\PharmaceuticalService;

class PharmaceuticalController extends Controller
{
    public function __construct(protected PharmaceuticalService $service) {}

    public function index(IndexPharmaceuticalRequest $indexPharmaceuticalRequest): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(ShowPharmaceuticalRequest $showPharmaceuticalRequest,string $sku): JsonResponse
    {
        try {
            return response()->json($this->service->get($sku));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'sku' => $sku,
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }

    public function store(StorePharmaceuticalRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->create($request->validated()), 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }

    public function update(UpdatePharmaceuticalRequest $request, string $sku): JsonResponse
    {
        try {
            return response()->json($this->service->update($sku, $request->validated()));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'sku' => $sku,
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }

    public function destroy(DestroyPharmaceuticalRequest $destroyPharmaceuticalRequest,string $sku): JsonResponse
    {
        try {
            $this->service->delete($sku);
            return response()->json([
                'message' => 'Pharmaceutical product deleted successfully',
                'sku' => $sku,
                'status' => 'success',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'sku' => $sku,
                'status' => 'error',
            ], $e->getCode() ?: 500);
        }
    }
}