<?php

namespace Modules\TreatmentPlan\Services;

use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\TreatmentPlan\Models\TreatmentPlan;
use Modules\Visit\Models\Visit;
use Modules\Warehouse\Models\WarehouseInventory;
use Modules\Warehouse\Services\WarehouseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class TreatmentPlanService
{
    public function __construct(
        protected WarehouseService $warehouseService,
    ) {}
    public function getAll(?User $user = null)
    {
        try {
            $query = TreatmentPlan::with(['lead', 'user', 'clinic', 'visits']);

            if ($user && !$user->can('view_any_treatment_plan')) {
                $query->where('user_id', $user->id);
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

    public function get(int $id, ?User $user = null): TreatmentPlan
    {
        try {
            $query = TreatmentPlan::with(['lead', 'user', 'clinic', 'visits']);

            if ($user && !$user->can('view_any_treatment_plan')) {
                $query->where('user_id', $user->id);
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

    public function create(array $data): TreatmentPlan
    {
        try {
            return DB::transaction(function () use ($data) {
                $visitsData = $data['visits'] ?? [];
                unset($data['visits']);

                $data['total_visits'] = count($visitsData);
                $data['status'] = $data['status'] ?? 'active';

                $plan = TreatmentPlan::create($data);

                $this->calculateVisitCosts($plan, $visitsData);

                foreach ($visitsData as $i => $visitData) {
                    $this->reserveSupplies($plan->clinic_id, $visitData['supplies_reserved'] ?? []);

                    $visitData['visit_number'] = $i + 1;
                    $visitData['treatment_plan_id'] = $plan->id;
                    $visitData['lead_id'] = $plan->lead_id;
                    $visitData['user_id'] = $plan->user_id;
                    $visitData['clinic_id'] = $plan->clinic_id;
                    $visitData['status'] = 'scheduled';

                    Visit::create($visitData);
                }

                return $plan->load(['lead', 'user', 'clinic', 'visits']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function calculateVisitCosts(TreatmentPlan $plan, array &$visitsData): void
    {
        foreach ($visitsData as &$visitData) {
            $serviceCost = floatval($visitData['service_cost'] ?? 0);
            $suppliesCost = 0;
            foreach ($visitData['supplies_reserved'] ?? [] as $item) {
                $suppliesCost += intval($item['quantity']) * floatval($item['unit_price'] ?? 0);
            }
            $visitData['services_cost'] = $serviceCost;
            $visitData['supplies_cost'] = $suppliesCost;
            $visitData['total_cost'] = $serviceCost + $suppliesCost;
        }
    }

    protected function reserveSupplies(int $clinicId, array $supplies): void
    {
        $warehouseId = $this->warehouseService->getWarehouseIdForClinic($clinicId);

        if (!$warehouseId || empty($supplies)) {
            return;
        }

        foreach ($supplies as $item) {
            $inventory = WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('sku', $item['sku'])
                ->first();

            if (!$inventory) {
                throw new \RuntimeException("SKU '{$item['sku']}' not found in warehouse.");
            }

            $qty = intval($item['quantity']);

            if ($inventory->available < $qty) {
                throw new \RuntimeException(
                    "Insufficient available stock for SKU '{$item['sku']}': have {$inventory->available}, need {$qty}."
                );
            }

            $inventory->increment('reserved_quantity', $qty);
        }
    }

    protected function releaseSupplies(int $clinicId, array $supplies): void
    {
        $warehouseId = $this->warehouseService->getWarehouseIdForClinic($clinicId);

        if (!$warehouseId || empty($supplies)) {
            return;
        }

        foreach ($supplies as $item) {
            WarehouseInventory::where('warehouse_id', $warehouseId)
                ->where('sku', $item['sku'])
                ->decrement('reserved_quantity', intval($item['quantity']));
        }
    }
}
