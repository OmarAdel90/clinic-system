<?php

namespace Modules\Invoice\Services;

use Modules\Auth\Models\User;
use Modules\Invoice\Models\Invoice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function getAll(?User $user = null): Collection
    {
        try {
            $query = Invoice::with(['lead', 'clinic', 'report']);

            if ($user && !$user->can('view_any_invoice')) {
                $leadIds = $user->assignedConversations()->pluck('lead_id');
                $query->whereIn('lead_id', $leadIds);
            }

            return $query->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id, ?User $user = null): Invoice
    {
        try {
            $query = Invoice::with(['lead', 'clinic', 'report']);

            if ($user && !$user->can('view_any_invoice')) {
                $leadIds = $user->assignedConversations()->pluck('lead_id');
                $query->whereIn('lead_id', $leadIds);
            }

            return $query->findOrFail($id);
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

    public function getByLead(int $leadId): Collection
    {
        try {
            return Invoice::with(['clinic', 'report'])
                ->where('lead_id', $leadId)
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function recordPayment(Invoice $invoice, float $amount): Invoice
    {
        try {
            $newPaid = $invoice->amount_paid + $amount;

            if ($newPaid > $invoice->total_cost) {
                throw new \InvalidArgumentException('Payment exceeds total cost.');
            }

            $invoice->amount_paid = $newPaid;
            $invoice->status = ($newPaid >= $invoice->total_cost) ? 'paid' : 'partial';
            $invoice->save();

            return $invoice->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $invoice->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): Invoice
    {
        try {
            return Invoice::create($data)->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Invoice $invoice, array $data): Invoice
    {
        try {
            $newTotal = array_key_exists('total_cost', $data) ? floatval($data['total_cost']) : $invoice->total_cost;
            $newPaid = array_key_exists('amount_paid', $data) ? floatval($data['amount_paid']) : $invoice->amount_paid;

            if ($newPaid > $newTotal) {
                throw new \InvalidArgumentException('Amount paid cannot exceed total cost.');
            }

            $data['status'] = $this->resolveStatus($newPaid, $newTotal);
            $invoice->update($data);
            return $invoice->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $invoice->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Invoice $invoice): void
    {
        try {
            $invoice->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $invoice->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function resolveStatus(float $amountPaid, float $totalCost): string
    {
        if ($amountPaid <= 0) {
            return 'unpaid';
        }

        if ($amountPaid >= $totalCost) {
            return 'paid';
        }

        return 'partial';
    }
}
