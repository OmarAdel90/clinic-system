<?php

namespace Modules\Supplier\Controllers;

use App\Http\Controllers\Controller;
use Modules\Supplier\Models\SupplierPaymentHistory;
use Modules\Supplier\Requests\IndexSupplierPaymentRequest;
use Modules\Supplier\Requests\ShowSupplierPaymentRequest;
use Modules\Supplier\Requests\PayRequest;
use Modules\Supplier\Requests\StoreSupplierPaymentRequest;
use Modules\Supplier\Requests\UpdateSupplierPaymentRequest;
use Modules\Supplier\Requests\DestroySupplierPaymentRequest;
use Modules\Supplier\Services\SupplierPaymentService;
use Illuminate\Http\JsonResponse;

class SupplierPaymentController extends Controller
{
    public function __construct(protected SupplierPaymentService $service) {}

    public function index(IndexSupplierPaymentRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(ShowSupplierPaymentRequest $request, SupplierPaymentHistory $supplierPayment): JsonResponse
    {
        return response()->json($this->service->get($supplierPayment->id));
    }

    public function store(StoreSupplierPaymentRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }

    public function pay(PayRequest $request, SupplierPaymentHistory $supplierPayment): JsonResponse
    {
        try {
            $payment = $this->service->recordPayment($supplierPayment, $request->validated()['amount']);
            return response()->json($payment);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateSupplierPaymentRequest $request, SupplierPaymentHistory $supplierPayment): JsonResponse
    {
        return response()->json($this->service->update($supplierPayment, $request->validated()));
    }

    public function destroy(DestroySupplierPaymentRequest $request, SupplierPaymentHistory $supplierPayment): JsonResponse
    {
        $this->service->delete($supplierPayment);
        return response()->json(null, 204);
    }
}
