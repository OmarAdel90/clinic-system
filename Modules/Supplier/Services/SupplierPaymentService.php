<?php

namespace Modules\Supplier\Services;

use Modules\Supplier\Models\SupplierPaymentHistory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class SupplierPaymentService
{
    public function getAll(): Collection
    {
        try {
            return SupplierPaymentHistory::with('transaction', 'supplier')->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id): SupplierPaymentHistory
    {
        try {
            return SupplierPaymentHistory::with('transaction', 'supplier')->findOrFail($id);
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

    public function create(array $data): SupplierPaymentHistory
    {
        try {
            return SupplierPaymentHistory::create($data);
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function recordPayment(SupplierPaymentHistory $payment, float $amount): SupplierPaymentHistory
    {
        try {
            $newTotalPaid = $payment->total_paid + $amount;

            if ($newTotalPaid > $payment->total_amount) {
                throw new \Exception('Payment amount exceeds the total amount due.');
            }

            $payment->total_paid   = $newTotalPaid;
            $payment->payment_status = ($newTotalPaid >= $payment->total_amount) ? 'paid' : 'partial';
            $payment->save();

            return $payment->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $payment->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(SupplierPaymentHistory $payment, array $data): SupplierPaymentHistory
    {
        try {
            $payment->update($data);

            return $payment->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $payment->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(SupplierPaymentHistory $payment): void
    {
        try {
            $payment->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $payment->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
