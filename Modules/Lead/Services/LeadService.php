<?php

namespace Modules\Lead\Services;

use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\CRM\Services\CallCenterService;
use Modules\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class LeadService
{
    public function getPickerOptions(?User $user = null, array $filters = []): Collection
    {
        try {
            $query = Lead::query()
                ->select(['id', 'name', 'arabic_name', 'profile_name', 'phone', 'clinic_id', 'lead_status_id'])
                ->orderByDesc('updated_at');

            $this->applyVisibilityFilter($query, $user);

            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('arabic_name', 'like', '%' . $search . '%')
                        ->orWhere('profile_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            }

            $limit = max(1, min((int) ($filters['limit'] ?? 100), 250));

            return $query->limit($limit)->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        }
    }

    public function getAll(?User $user = null, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Lead::with([
                'leadStatus',
                'campaign',
                'clinic',
                'clinicAssignedBy',
                'assignmentState.user',
                'conversations',
            ])->withCount('medicalRecords');

            $this->applyVisibilityFilter($query, $user);

            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('arabic_name', 'like', '%' . $search . '%')
                        ->orWhere('profile_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            }

            $status = trim((string) ($filters['status'] ?? ''));
            if ($status !== '' && $status !== 'all') {
                $query->where(function (Builder $builder) use ($status) {
                    $builder->whereHas('leadStatus', function (Builder $statusQuery) use ($status) {
                        $statusQuery
                            ->where('key', $status)
                            ->orWhere('label', $status);
                    });

                    if (is_numeric($status)) {
                        $builder->orWhere('lead_status_id', (int) $status);
                    }
                });
            }

            $assignmentStatus = $filters['assignment_status'] ?? null;
            if ($assignmentStatus === 'assigned') {
                $query->whereHas('assignmentState');
            } elseif ($assignmentStatus === 'unassigned') {
                $query->whereDoesntHave('assignmentState');
            }

            $clinicAssignmentStatus = $filters['clinic_assignment_status'] ?? null;
            if ($clinicAssignmentStatus === 'assigned') {
                $query->whereNotNull('clinic_id');
            } elseif ($clinicAssignmentStatus === 'unassigned') {
                $query->whereNull('clinic_id');
            }

            $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

            return $query
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        }
    }

    public function get(int $id, ?User $user = null): Lead
    {
        try {
            $query = Lead::with([
                'leadStatus',
                'campaign',
                'clinic',
                'clinicAssignedBy',
                'assignmentState.user',
                'conversations',
            ])->withCount('medicalRecords');

            $this->applyVisibilityFilter($query, $user);

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

    public function create(array $data): Lead
    {
        try {
            return DB::transaction(function () use ($data) {
                $lead = Lead::create($data);

                app(CallCenterService::class)->assignNextLead($lead->id);

                return $lead->fresh([
                    'leadStatus',
                    'campaign',
                    'clinic',
                    'clinicAssignedBy',
                    'assignmentState.user',
                    'conversations',
                ])->loadCount('medicalRecords');
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Lead $lead, array $data): Lead
    {
        try {
            $lead->update($data);
            return $lead->fresh();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $lead->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Lead $lead): void
    {
        try {
            $lead->delete();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $lead->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignClinic(Lead $lead, int $clinicId, ?User $actor = null): Lead
    {
        try {
            return DB::transaction(function () use ($lead, $clinicId, $actor) {
                Clinic::findOrFail($clinicId);

                $lead->update([
                    'clinic_id' => $clinicId,
                    'clinic_assigned_by' => $actor?->id,
                    'clinic_assigned_at' => now(),
                ]);

                return $lead->fresh([
                    'leadStatus',
                    'campaign',
                    'clinic',
                    'clinicAssignedBy',
                    'assignmentState.user',
                    'conversations',
                ])->loadCount('medicalRecords');
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['lead_id' => $lead->id, 'clinic_id' => $clinicId]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $lead->id, 'clinic_id' => $clinicId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['lead_id' => $lead->id, 'clinic_id' => $clinicId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function applyVisibilityFilter(Builder $query, ?User $user = null): void
    {
        if ($user && !$user->can('view_any_lead')) {
            $leadIds = $user->assignedConversations()->pluck('lead_id');
            $query->whereIn('id', $leadIds);
        }
    }
}
