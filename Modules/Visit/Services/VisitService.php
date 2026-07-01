<?php

namespace Modules\Visit\Services;

use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Invoice\Models\Invoice;
use Modules\Visit\Models\Report;
use Modules\Warehouse\Services\WarehouseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class VisitService
{
    public function __construct(
        protected WarehouseService $warehouseService,
    ) {}

    public function getAll(?User $user = null)
    {
        try {
            $query = Report::with(['user', 'lead', 'clinic', 'invoice']);

            if ($user && !$user->can('view_any_visit')) {
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

    public function get(string $id, ?User $user = null): Report
    {
        try {
            $query = Report::with(['user', 'lead', 'clinic', 'invoice']);

            if ($user && !$user->can('view_any_visit')) {
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

    public function getByLead(int $leadId)
    {
        try {
            return Report::with(['user', 'clinic', 'invoice'])
                ->where('lead_id', $leadId)
                ->orderBy('visit_date', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getByUser(int $userId)
    {
        try {
            return Report::with(['lead', 'clinic', 'invoice'])
                ->where('user_id', $userId)
                ->orderBy('visit_date', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): Report
    {
        try {
            return DB::transaction(function () use ($data) {
                $data['status'] = $data['status'] ?? 'active';

                $report = Report::create($data);

                $warehouseId = $this->warehouseService->getWarehouseIdForClinic($report->clinic_id);

                if ($warehouseId && !empty($report->supplies_used)) {
                    $this->warehouseService->deductInventory($warehouseId, $report->supplies_used);
                }

                if ($report->cost_known) {
                    $this->generateInvoice($report);
                }

                return $report->load(['user', 'lead', 'clinic', 'invoice']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Report $report, array $data): Report
    {
        try {
            return DB::transaction(function () use ($report, $data) {
                $report->update($data);

                if (isset($data['supplies_used'])) {
                    $warehouseId = $this->warehouseService->getWarehouseIdForClinic($report->clinic_id);

                    if ($warehouseId) {
                        $this->warehouseService->deductInventory($warehouseId, $data['supplies_used']);
                    }
                }

                if ($report->cost_known && !$report->invoice) {
                    $this->generateInvoice($report);
                }

                return $report->fresh(['user', 'lead', 'clinic', 'invoice']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $report->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Report $report): void
    {
        try {
            $report->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $report->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function generateInvoice(Report $report): Invoice
    {
        $servicesCost = 0;
        $suppliesCost = 0;
        $service = Clinic::find($report->clinic_id);
        if ($service && $service->services) {
            foreach ($service->services as $procedure) {
                $servicesCost += floatval($procedure['cost'] ?? 0);
            }
        }

        $supplies = $report->supplies_used ?? [];
        foreach ($supplies as $item) {
            $suppliesCost += intval($item['quantity']) * floatval($item['unit_price'] ?? 0);
        }

        $totalCost = $servicesCost + $suppliesCost;

        return Invoice::create([
            'lead_id'        => $report->lead_id,
            'clinic_id'      => $report->clinic_id,
            'report_id'      => $report->id,
            'invoice_number' => 'INV-' . strtoupper(uniqid()),
            'services_cost'  => $servicesCost,
            'supplies_cost'  => $suppliesCost,
            'total_cost'     => $totalCost,
            'amount_paid'    => 0,
            'status'         => 'unpaid',
            'issued_at'      => now(),
            'due_date'       => now()->addDays(30),
        ]);
    }
}
