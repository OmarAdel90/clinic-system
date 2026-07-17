<?php

namespace Modules\Visit\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Visit\Models\Report;

class ReportService
{
    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Report::with(['user', 'lead', 'clinic', 'visit', 'invoice']);

            if (! $user->can('view_any_report')) {
                $query->where('user_id', $user->id);
            }

            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('diagnosis', 'like', '%' . $search . '%')
                        ->orWhere('treatment_notes', 'like', '%' . $search . '%')
                        ->orWhere('body', 'like', '%' . $search . '%')
                        ->orWhereHas('lead', function (Builder $leadQuery) use ($search) {
                            $leadQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('arabic_name', 'like', '%' . $search . '%')
                                ->orWhere('profile_name', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('clinic', function (Builder $clinicQuery) use ($search) {
                            $clinicQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('arabic_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('arabic_name', 'like', '%' . $search . '%')
                                ->orWhere('phone_number', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('visit', function (Builder $visitQuery) use ($search) {
                            $visitQuery->where('visit_number', 'like', '%' . $search . '%');
                        });
                });
            }

            if (! empty($filters['clinic_id'])) {
                $query->where('clinic_id', (int) $filters['clinic_id']);
            }

            if (! empty($filters['user_id'])) {
                $query->where('user_id', (int) $filters['user_id']);
            }

            $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

            return $query
                ->orderByDesc('visit_date')
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

    public function get(int $id, User $user): Report
    {
        try {
            $query = Report::with(['user', 'lead', 'clinic', 'visit', 'invoice']);

            if (! $user->can('view_any_report')) {
                $query->where('user_id', $user->id);
            }

            return $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $id, 'user_id' => $user->id]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['id' => $id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Report $report, array $data, User $user): Report
    {
        try {
            return DB::transaction(function () use ($report, $data, $user) {
                if (! $user->can('view_any_report') && $report->user_id !== $user->id) {
                    throw new ModelNotFoundException();
                }

                $report->update($data);

                return $report->fresh(['user', 'lead', 'clinic', 'visit', 'invoice']);
            });
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__ . ' model not found', ['id' => $report->id, 'user_id' => $user->id]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['id' => $report->id, 'user_id' => $user->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['id' => $report->id, 'user_id' => $user->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
