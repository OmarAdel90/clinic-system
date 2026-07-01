<?php

namespace Modules\Transaction\Services;

use Modules\Supplier\Models\SupplierPaymentHistory;
use Modules\Transaction\Models\WarehouseSupplierTransaction;
use Modules\Warehouse\Models\WarehouseInventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    public function getAll(): Collection
    {
        try {
            return WarehouseSupplierTransaction::with(['warehouse', 'supplier'])->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(string $transactionId): WarehouseSupplierTransaction
    {
        try {
            return WarehouseSupplierTransaction::with(['warehouse', 'supplier'])
                ->findOrFail($transactionId);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['transaction_id' => $transactionId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['transaction_id' => $transactionId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'transaction_id' => $transactionId]);
            throw $e;
        }
    }

    public function create(array $data): WarehouseSupplierTransaction
    {
        try {
            $this->validateItems($data);

            return DB::transaction(function () use ($data) {
                $transaction = WarehouseSupplierTransaction::create($data);

                $this->syncInventory($transaction);

                $this->createPaymentRecord($transaction);

                return $transaction->load(['warehouse', 'supplier']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(WarehouseSupplierTransaction $transaction, array $data): WarehouseSupplierTransaction
    {
        try {
            $this->validateItems($data);

            return DB::transaction(function () use ($transaction, $data) {
                $oldItems = $transaction->items_bought ?? [];

                $transaction->update($data);

                $this->syncInventoryOnUpdate($transaction->fresh(), $oldItems);

                $this->updatePaymentRecord($transaction);

                return $transaction->fresh(['warehouse', 'supplier']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $transaction->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(WarehouseSupplierTransaction $transaction): void
    {
        try {
            DB::transaction(function () use ($transaction) {
                $this->reverseInventory($transaction);

                SupplierPaymentHistory::where('transaction_id', $transaction->transaction_id)->delete();

                $transaction->delete();
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $transaction->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function validateItems(array $data): void
    {
        $items = $data['items_bought'] ?? [];

        if (empty($items)) {
            return;
        }

        $skus = [];
        foreach ($items as $item) {
            $sku = $item['sku'] ?? null;

            if (!$sku) {
                throw new \InvalidArgumentException('Each item must have a sku.');
            }

            if (in_array($sku, $skus)) {
                throw new \InvalidArgumentException("Duplicate SKU '{$sku}' in the same batch.");
            }

            $skus[] = $sku;
        }
    }

    protected function createPaymentRecord(WarehouseSupplierTransaction $transaction): void
    {
        $items = $transaction->items_bought ?? [];
        if (empty($items)) return;

        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += intval($item['quantity']) * floatval($item['price'] ?? 0);
        }

        SupplierPaymentHistory::create([
            'transaction_id' => $transaction->transaction_id,
            'supplier_id'    => $transaction->supplier_id,
            'batch_id'       => $transaction->batch_number ?? ('BATCH-' . strtoupper(uniqid())),
            'total_amount'   => $totalAmount,
            'total_paid'     => 0,
            'payment_status' => 'unpaid',
        ]);
    }

    protected function syncInventory(WarehouseSupplierTransaction $transaction): void
    {
        $items = $transaction->items_bought;

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $inventory = WarehouseInventory::firstOrCreate(
                [
                    'warehouse_id' => $transaction->warehouse_id,
                    'sku'          => $item['sku'],
                ],
                [
                    'name'        => $item['name'],
                    'arabic_name' => $item['arabic_name'] ?? null,
                    'quantity'    => 0,
                ]
            );

            $inventory->increment('quantity', intval($item['quantity']));
        }
    }

    protected function syncInventoryOnUpdate(WarehouseSupplierTransaction $transaction, array $oldItems): void
    {
        $newItems = $transaction->items_bought ?? [];

        $oldBySku = [];
        foreach ($oldItems as $item) {
            $oldBySku[$item['sku']] = $item;
        }

        $newBySku = [];
        foreach ($newItems as $item) {
            $newBySku[$item['sku']] = $item;
        }

        $allSkus = array_unique(array_merge(array_keys($oldBySku), array_keys($newBySku)));

        foreach ($allSkus as $sku) {
            $oldQty = isset($oldBySku[$sku]) ? intval($oldBySku[$sku]['quantity']) : 0;
            $newQty = isset($newBySku[$sku]) ? intval($newBySku[$sku]['quantity']) : 0;
            $delta = $newQty - $oldQty;

            if ($delta === 0) {
                continue;
            }

            $inventory = WarehouseInventory::where('warehouse_id', $transaction->warehouse_id)
                ->where('sku', $sku)
                ->first();

            if ($delta > 0) {
                if (!$inventory) {
                    $inventory = WarehouseInventory::create([
                        'warehouse_id' => $transaction->warehouse_id,
                        'sku'          => $sku,
                        'name'         => $newBySku[$sku]['name'],
                        'arabic_name'  => $newBySku[$sku]['arabic_name'] ?? null,
                        'quantity'     => 0,
                    ]);
                }
                $inventory->increment('quantity', $delta);
            } else {
                $absDelta = abs($delta);
                if (!$inventory || $inventory->quantity < $absDelta) {
                    throw new \InvalidArgumentException("Insufficient stock for SKU '{$sku}'.");
                }
                $inventory->decrement('quantity', $absDelta);
            }
        }
    }

    protected function reverseInventory(WarehouseSupplierTransaction $transaction): void
    {
        $items = $transaction->items_bought ?? [];

        foreach ($items as $item) {
            $inventory = WarehouseInventory::where('warehouse_id', $transaction->warehouse_id)
                ->where('sku', $item['sku'])
                ->first();

            if ($inventory) {
                $qty = intval($item['quantity']);
                if ($inventory->quantity < $qty) {
                    throw new \InvalidArgumentException("Insufficient stock to reverse SKU '{$item['sku']}'.");
                }
                $inventory->decrement('quantity', $qty);
            }
        }
    }

    protected function updatePaymentRecord(WarehouseSupplierTransaction $transaction): void
    {
        $items = $transaction->items_bought ?? [];
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += intval($item['quantity']) * floatval($item['price'] ?? 0);
        }

        $payment = SupplierPaymentHistory::where('transaction_id', $transaction->transaction_id)->first();
        if ($payment) {
            $payment->update([
                'total_amount' => $totalAmount,
                'batch_id'     => $transaction->batch_number ?? $payment->batch_id,
            ]);
        }
    }
}
