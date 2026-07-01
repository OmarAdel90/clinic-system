<?php

namespace Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use Modules\Supplier\Models\Supplier;
use Modules\Supplier\Requests\IndexSupplierRequest;
use Modules\Supplier\Requests\ShowSupplierRequest;
use Modules\Supplier\Requests\StoreSupplierRequest;
use Modules\Supplier\Requests\UpdateSupplierRequest;
use Modules\Supplier\Requests\DestroySupplierRequest;
use Modules\Supplier\Services\SupplierService;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    public function __construct(protected SupplierService $service) {}

    public function index(IndexSupplierRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(ShowSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        return response()->json($this->service->get($supplier->id));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        return response()->json($this->service->update($supplier, $request->validated()));
    }

    public function destroy(DestroySupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->service->delete($supplier);
        return response()->json(null, 204);
    }
}
