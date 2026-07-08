<?php

namespace Modules\Warehouse\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Warehouse\Models\Warehouse;
use Modules\Warehouse\Models\WarehouseInventory;

class WarehouseService
{
    public function getAll(?User $user = null): Collection
    {
        try {
            $query = Warehouse::with('clinic', 'inventories');

            if ($user && ! $user->can('view_any_warehouse')) {
                $clinicIds = $user->assignedConversations()
                    ->join('visits', 'conversations.lead_id', '=', 'visits.lead_id')
                    ->pluck('visits.clinic_id')
                    ->unique();
                $query->whereIn('clinic_id', $clinicIds);
            }

            return $query->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id, ?User $user = null): Warehouse
    {
        try {
            $query = Warehouse::with('clinic', 'inventories');

            if ($user && ! $user->can('view_any_warehouse')) {
                $clinicIds = $user->assignedConversations()
                    ->join('visits', 'conversations.lead_id', '=', 'visits.lead_id')
                    ->pluck('visits.clinic_id')
                    ->unique();
                $query->whereIn('clinic_id', $clinicIds);
            }

            return $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__.' model not found', ['id' => $id]);
            throw new \Exception("Warehouse with ID '$id' was not found", 404);
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    public function create(array $data): Warehouse
    {
        try {
            $clinic = Clinic::find($data['clinic_id']);
            if (! $clinic) {
                throw new \Exception('Clinic not found', 404);
            }

            if (! $clinic->provides_medication) {
                throw new \Exception('Cannot create warehouse for a clinic that does not provide medication', 422);
            }

            if (Warehouse::where('clinic_id', $data['clinic_id'])->exists()) {
                throw new \Exception('Clinic already has a warehouse', 409);
            }

            return DB::transaction(function () use ($data, $clinic) {
                $warehouse = Warehouse::create($data);

                $clinic->update(['warehouse_id' => $warehouse->id]);

                return $warehouse;
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Warehouse $warehouse, array $data, bool $confirmReassign = false): Warehouse|array
    {
        try {
            return DB::transaction(function () use ($warehouse, $data, $confirmReassign) {
                if (array_key_exists('clinic_id', $data) && $data['clinic_id'] !== $warehouse->clinic_id) {
                    $newClinicId = $data['clinic_id'];

                    if ($newClinicId !== null) {
                        $conflictingWarehouse = Warehouse::where('clinic_id', $newClinicId)
                            ->where('id', '!=', $warehouse->id)
                            ->first();

                        if ($conflictingWarehouse && ! $confirmReassign) {
                            return [
                                'conflict' => true,
                                'clinic_id' => $newClinicId,
                                'current_warehouse_id' => $conflictingWarehouse->id,
                            ];
                        }

                        if ($conflictingWarehouse) {
                            $conflictingWarehouse->update(['clinic_id' => null]);
                        }
                    }
                }

                $warehouse->update($data);

                return $warehouse->fresh();
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['model_id' => $warehouse->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

   public function delete(Warehouse $warehouse): void
{
    try {
        DB::transaction(function () use ($warehouse) {
            $warehouse->delete();
        });
    } catch (QueryException $e) {
        Log::error(__METHOD__.' failed', ['model_id' => $warehouse->id, 'error' => $e->getMessage()]);
        throw $e;
    } catch (\Throwable $e) {
        Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
        throw $e;
    }
}

    public function getWarehouseIdForClinic(int $clinicId): ?int
    {
        try {
            $warehouse = Warehouse::where('clinic_id', $clinicId)->first(['id']);

            return $warehouse?->id;
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['clinic_id' => $clinicId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deductInventory(int $warehouseId, array $supplies, bool $decrementReserved = false): void
    {
        try {
            if (empty($supplies)) {
                return;
            }

            foreach ($supplies as $item) {
                $qty = intval($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('sku', $item['sku'])
                    ->first();

                if (! $inventory) {
                    throw new \RuntimeException("SKU '{$item['sku']}' not found in warehouse.");
                }

                if ($inventory->quantity < $qty) {
                    throw new \RuntimeException(
                        "Insufficient stock for SKU '{$item['sku']}': have {$inventory->quantity}, need {$qty}."
                    );
                }

                $inventory->decrement('quantity', $qty);

                if ($decrementReserved) {
                    $reservedQty = min($qty, $inventory->reserved_quantity);
                    $inventory->decrement('reserved_quantity', $reservedQty);
                }
            }
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['warehouse_id' => $warehouseId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addInventory(int $warehouseId, array $supplies): void
    {
        try {
            if (empty($supplies)) {
                return;
            }

            foreach ($supplies as $item) {
                $qty = intval($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('sku', $item['sku'])
                    ->first();

                if (! $inventory) {
                    $inventory = WarehouseInventory::create([
                        'warehouse_id' => $warehouseId,
                        'sku' => $item['sku'],
                        'name' => $item['name'] ?? $item['sku'],
                        'arabic_name' => $item['arabic_name'] ?? null,
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                    ]);
                }

                $inventory->increment('quantity', $qty);
            }
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['warehouse_id' => $warehouseId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function releaseReserved(int $warehouseId, array $supplies): void
    {
        try {
            if (empty($supplies)) {
                return;
            }

            foreach ($supplies as $item) {
                $qty = intval($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('sku', $item['sku'])
                    ->first();

                if ($inventory && $inventory->reserved_quantity > 0) {
                    $releaseQty = min($qty, $inventory->reserved_quantity);
                    $inventory->decrement('reserved_quantity', $releaseQty);
                }
            }
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['warehouse_id' => $warehouseId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function checkSufficientStock(int $warehouseId, array $supplies): void
    {
        try {
            if (empty($supplies)) {
                return;
            }

            foreach ($supplies as $item) {
                $qty = intval($item['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                    ->where('sku', $item['sku'])
                    ->first();

                if (! $inventory) {
                    throw new \RuntimeException("SKU '{$item['sku']}' not found in warehouse.");
                }

                $available = $inventory->quantity - $inventory->reserved_quantity;

                if ($available < $qty) {
                    throw new \RuntimeException(
                        "Insufficient available stock for SKU '{$item['sku']}': have {$available} available, need {$qty}."
                    );
                }
            }
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['warehouse_id' => $warehouseId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
