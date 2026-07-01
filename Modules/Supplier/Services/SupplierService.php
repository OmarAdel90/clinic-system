<?php

namespace Modules\Supplier\Services;

use Modules\Supplier\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    public function getAll(): Collection
    {
        try {
            return Supplier::with('paymentHistories')->get()->map(function ($supplier) {
                $supplier->total_owed = $supplier->totalOwed();
                $supplier->total_paid = $supplier->totalPaid();
                $supplier->balance_owed = $supplier->balanceOwed();
                return $supplier;
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id): Supplier
    {
        try {
            $supplier = Supplier::with('paymentHistories')->findOrFail($id);
            $supplier->total_owed = $supplier->totalOwed();
            $supplier->total_paid = $supplier->totalPaid();
            $supplier->balance_owed = $supplier->balanceOwed();
            return $supplier;
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $id]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    public function create(array $data): Supplier
    {
        try {
            return Supplier::create($data);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        try {
            $supplier->update($data);

            return $supplier->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $supplier->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Supplier $supplier): void
    {
        try {
            $supplier->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $supplier->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
