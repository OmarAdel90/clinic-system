<?php

namespace Modules\Visit\Services;

use Modules\Auth\Models\User;
use Modules\Visit\Models\Visit;
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
            $query = Visit::with(['user', 'lead', 'clinic', 'treatmentPlan', 'conversation', 'report.invoice']);

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

    public function get(string $id, ?User $user = null): Visit
    {
        try {
            $query = Visit::with(['user', 'lead', 'clinic', 'treatmentPlan', 'conversation', 'report.invoice']);

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
            return Visit::with(['user', 'clinic', 'report.invoice'])
                ->where('lead_id', $leadId)
                ->orderBy('scheduled_date', 'desc')
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
            return Visit::with(['lead', 'clinic', 'report.invoice'])
                ->where('user_id', $userId)
                ->orderBy('scheduled_date', 'desc')
                ->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function create(array $data): Visit
    {
        try {
            return DB::transaction(function () use ($data) {
                $payload = $this->mapVisitPayload($data);
                $visit = Visit::create($payload);

                return $visit->load(['user', 'lead', 'clinic', 'treatmentPlan', 'conversation', 'report.invoice']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Visit $visit, array $data): Visit
    {
        try {
            return DB::transaction(function () use ($visit, $data) {
                $payload = $this->mapVisitPayload($data);
                $visit->update($payload);

                return $visit->fresh(['user', 'lead', 'clinic', 'treatmentPlan', 'conversation', 'report.invoice']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $visit->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Visit $visit): void
    {
        try {
            DB::transaction(function () use ($visit) {
                $report = $visit->report;

                if ($report?->invoice) {
                    $report->invoice->delete();
                }

                if ($report) {
                    $report->delete();
                }

                $visit->delete();
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $visit->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function mapVisitPayload(array $data): array
    {
        $payload = [];

        if (array_key_exists('lead_id', $data)) {
            $payload['lead_id'] = $data['lead_id'];
        }

        if (array_key_exists('user_id', $data)) {
            $payload['user_id'] = $data['user_id'];
        }

        if (array_key_exists('clinic_id', $data)) {
            $payload['clinic_id'] = $data['clinic_id'];
        }

        if (array_key_exists('visit_date', $data)) {
            $payload['scheduled_date'] = $data['visit_date'];
        }

        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        } elseif (! isset($payload['status'])) {
            $payload['status'] = 'scheduled';
        }

        return $payload;
    }
}
