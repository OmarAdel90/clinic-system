<?php

namespace Modules\Visit\Services;

use Modules\Clinic\Models\Clinic;
use Modules\Invoice\Models\Invoice;
use Modules\Visit\Models\Report;
use Modules\Visit\Models\Visit;
use Modules\Visit\Events\ReportCompleted;
use Modules\Warehouse\Services\WarehouseService;
use Modules\TreatmentPlan\Models\TreatmentPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class VisitFlowService
{
    public function __construct(
        protected WarehouseService $warehouseService,
    ) {}

    public function confirmAppointment(int $visitId): Visit
    {
        try {
            return DB::transaction(function () use ($visitId) {
                $visit = Visit::findOrFail($visitId);

                if ($visit->status !== 'scheduled') {
                    throw new \RuntimeException('Only scheduled visits can be confirmed.');
                }

                $visit->status = 'confirmed';
                $visit->confirmed_at = now();
                $visit->save();

                $this->transitionLeadToConverted($visit);

                return $visit->fresh();
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['visit_id' => $visitId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['visit_id' => $visitId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'visit_id' => $visitId]);
            throw $e;
        }
    }

    public function completeVisit(int $visitId, array $reportData): Report
    {
        try {
            return DB::transaction(function () use ($visitId, $reportData) {
                $visit = Visit::with('treatmentPlan')->findOrFail($visitId);

                if ($visit->status !== 'confirmed') {
                    throw new \RuntimeException('Visit must be confirmed before completing.');
                }

                $reportData['visit_id'] = $visit->id;
                $reportData['clinic_id'] = $visit->clinic_id;
                $reportData['user_id'] = $visit->user_id;
                $reportData['lead_id'] = $visit->lead_id;
                $reportData['visit_date'] = $visit->scheduled_date;
                $reportData['status'] = 'completed';

                if (!isset($reportData['supplies_used'])) {
                    $reportData['supplies_used'] = $visit->supplies_reserved;
                }

                $report = Report::create($reportData);

                $warehouseId = $this->warehouseService->getWarehouseIdForClinic($visit->clinic_id);

                if ($warehouseId) {
                    $this->warehouseService->deductInventory($warehouseId, $report->supplies_used ?? [], true);

                    $excessReserved = $this->getExcessReserved($visit->supplies_reserved ?? [], $report->supplies_used ?? []);
                    if (!empty($excessReserved)) {
                        $this->warehouseService->releaseReserved($warehouseId, $excessReserved);
                    }
                }

                $visit->actual_date = now();
                $visit->status = 'completed';
                $visit->report_id = $report->id;
                $visit->save();

                if ($visit->treatmentPlan) {
                    $completedCount = Visit::where('treatment_plan_id', $visit->treatment_plan_id)
                        ->where('status', 'completed')
                        ->count();

                    if ($completedCount >= $visit->treatmentPlan->total_visits) {
                        $visit->treatmentPlan->update(['status' => 'completed']);
                    }
                }

                if ($visit->treatmentPlan) {
                    $this->generateFinalInvoice($visit->treatmentPlan);
                }

                ReportCompleted::dispatch($report, $visit);

                return $report->load(['user', 'lead', 'visit']);
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['visit_id' => $visitId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['visit_id' => $visitId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'visit_id' => $visitId]);
            throw $e;
        }
    }

    public function cancelVisit(int $visitId): Visit
    {
        try {
            return DB::transaction(function () use ($visitId) {
                $visit = Visit::findOrFail($visitId);

                if (in_array($visit->status, ['completed', 'cancelled', 'missed'])) {
                    throw new \RuntimeException('Visit cannot be cancelled from its current status.');
                }

                $warehouseId = $this->warehouseService->getWarehouseIdForClinic($visit->clinic_id);

                if ($warehouseId) {
                    $this->warehouseService->releaseReserved($warehouseId, $visit->supplies_reserved ?? []);
                }

                $visit->status = 'cancelled';
                $visit->save();

                return $visit->fresh();
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['visit_id' => $visitId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['visit_id' => $visitId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'visit_id' => $visitId]);
            throw $e;
        }
    }

    public function markMissed(int $visitId): Visit
    {
        try {
            return DB::transaction(function () use ($visitId) {
                $visit = Visit::findOrFail($visitId);

                if ($visit->status !== 'confirmed') {
                    throw new \RuntimeException('Only confirmed visits can be marked missed.');
                }

                $warehouseId = $this->warehouseService->getWarehouseIdForClinic($visit->clinic_id);

                if ($warehouseId) {
                    $this->warehouseService->releaseReserved($warehouseId, $visit->supplies_reserved ?? []);
                }

                $visit->status = 'missed';
                $visit->save();

                return $visit->fresh();
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['visit_id' => $visitId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['visit_id' => $visitId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'visit_id' => $visitId]);
            throw $e;
        }
    }

    protected function getExcessReserved(array $reserved, array $used): array
    {
        $usedBySku = [];
        foreach ($used as $item) {
            $sku = $item['sku'] ?? null;
            if ($sku) {
                $usedBySku[$sku] = intval($item['quantity'] ?? 0);
            }
        }

        $excess = [];
        foreach ($reserved as $item) {
            $sku = $item['sku'] ?? null;
            if (!$sku) continue;

            $reservedQty = intval($item['quantity'] ?? 0);
            $usedQty = $usedBySku[$sku] ?? 0;
            $diff = $reservedQty - $usedQty;

            if ($diff > 0) {
                $excess[] = ['sku' => $sku, 'quantity' => $diff];
            }
        }

        return $excess;
    }

    protected function transitionLeadToConverted(Visit $visit): void
    {
        $visit->loadMissing('conversation.lead');
        $conversation = $visit->conversation;

        if (!$conversation) return;

        $lead = $conversation->lead;
        if (!$lead) return;

        $convertedStatus = \Modules\Lead\Models\LeadStatus::where('key', 'converted')->first();

        if ($convertedStatus) {
            $lead->lead_status_id = $convertedStatus->id;
            $lead->save();

            $conversation->converted_at = now();
            $conversation->lead_status = 'converted';
            $conversation->save();
        }
    }

    protected function generateFinalInvoice(TreatmentPlan $plan): void
    {
        $completedVisits = Visit::where('treatment_plan_id', $plan->id)
            ->where('status', 'completed')
            ->get();

        $totalCost = $completedVisits->sum('total_cost');

        $existingInvoice = Invoice::where('treatment_plan_id', $plan->id)->first();

        if ($existingInvoice) {
            $existingInvoice->update(['total_cost' => $totalCost]);
            return;
        }

        Invoice::create([
            'lead_id'           => $plan->lead_id,
            'clinic_id'         => $plan->clinic_id,
            'treatment_plan_id' => $plan->id,
            'total_cost'        => $totalCost,
            'amount_paid'       => 0,
            'status'            => 'unpaid',
            'issued_at'         => now(),
            'due_date'          => now()->addDays(30),
        ]);
    }
}
