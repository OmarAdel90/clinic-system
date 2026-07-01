<?php

namespace Modules\Transaction\Controllers;

use App\Http\Controllers\Controller;
use Modules\Transaction\Models\WarehouseSupplierTransaction;
use Modules\Transaction\Requests\IndexTransactionRequest;
use Modules\Transaction\Requests\ShowTransactionRequest;
use Modules\Transaction\Requests\StoreTransactionRequest;
use Modules\Transaction\Requests\UpdateTransactionRequest;
use Modules\Transaction\Requests\DestroyTransactionRequest;
use Modules\Transaction\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $service) {}

    public function index(IndexTransactionRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll());
    }

    public function show(ShowTransactionRequest $request, WarehouseSupplierTransaction $transaction): JsonResponse
    {
        return response()->json(
            $this->service->get($transaction->transaction_id)
        );
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        try {
            return response()->json($this->service->create($request->validated()), 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateTransactionRequest $request, WarehouseSupplierTransaction $transaction): JsonResponse
    {
        return response()->json($this->service->update($transaction, $request->validated()));
    }

    public function destroy(DestroyTransactionRequest $request, WarehouseSupplierTransaction $transaction): JsonResponse
    {
        $this->service->delete($transaction);
        return response()->json(null, 204);
    }
}
