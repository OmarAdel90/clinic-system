<?php

namespace Modules\Clinic\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Warehouse\Models\Warehouse;

class ClinicService
{
    protected function normalizeServices(array $services): array
    {
        return array_values(array_filter(array_map(function ($service) {
            if (is_string($service)) {
                $name = trim($service);

                return $name === '' ? null : [
                    'name' => $name,
                    'cost' => 0,
                ];
            }

            if (! is_array($service)) {
                return null;
            }

            $name = trim((string) ($service['name'] ?? ''));

            if ($name === '') {
                return null;
            }

            return [
                'name' => $name,
                'cost' => floatval($service['cost'] ?? 0),
            ];
        }, $services)));
    }

    public function getAll(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            $query = Clinic::with('warehouse');

            if ($user && ! $user->can('view_any_clinic')) {
                $clinicIds = $user->assignedConversations()
                    ->join('visits', 'conversations.lead_id', '=', 'visits.lead_id')
                    ->pluck('visits.clinic_id')
                    ->unique();
                $query->whereIn('id', $clinicIds);
            }

            $search = trim((string) ($filters['search'] ?? ''));
            if ($search !== '') {
                $query->where(function (Builder $builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('arabic_name', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhere('address', 'like', '%' . $search . '%')
                        ->orWhereHas('warehouse', function (Builder $warehouseQuery) use ($search) {
                            $warehouseQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            }

            $perPage = max(1, min((int) ($filters['per_page'] ?? 10), 100));

            return $query
                ->orderBy('name')
                ->orderByDesc('id')
                ->paginate($perPage)
                ->withQueryString();
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage(), 'filters' => $filters]);
            throw $e;
        }
        
    }

    public function get(int $id, ?User $user = null): Clinic
    {
        try {
            $query = Clinic::with('warehouse');

            if ($user && ! $user->can('view_any_clinic')) {
                $clinicIds = $user->assignedConversations()
                    ->join('visits', 'conversations.lead_id', '=', 'visits.lead_id')
                    ->pluck('visits.clinic_id')
                    ->unique();
                $query->whereIn('id', $clinicIds);
            }

            return $query->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            Log::warning(__METHOD__.' model not found', ['id' => $id]);
            throw $e;
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage(), 'id' => $id]);
            throw $e;
        }
    }

    public function create(array $data): Clinic
    {
        try {
            return DB::transaction(function () use ($data) {
                if (array_key_exists('services', $data) && is_array($data['services'])) {
                    $data['services'] = $this->normalizeServices($data['services']);
                }

                return Clinic::create($data);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(Clinic $clinic, array $data, bool $confirmReassign = false): Clinic|array
    {
        try {
            return DB::transaction(function () use ($clinic, $data, $confirmReassign) {
                if (array_key_exists('services', $data) && is_array($data['services'])) {
                    $data['services'] = $this->normalizeServices($data['services']);
                }

                $providesMedication = array_key_exists('provides_medication', $data)
                    ? (bool) $data['provides_medication']
                    : (bool) $clinic->provides_medication;

                if (! $providesMedication) {
                    Warehouse::where('clinic_id', $clinic->id)->update(['clinic_id' => null]);
                    unset($data['warehouse_id']);
                }

                if (array_key_exists('warehouse_id', $data)) {
                    $warehouseId = $data['warehouse_id'];
                    unset($data['warehouse_id']);

                    if ($warehouseId === null) {
                        Warehouse::where('clinic_id', $clinic->id)->update(['clinic_id' => null]);
                    } else {
                        $currentOwner = Warehouse::where('id', $warehouseId)
                            ->with('clinic')
                            ->whereNotNull('clinic_id')
                            ->where('clinic_id', '!=', $clinic->id)
                            ->first();

                        if ($currentOwner && ! $confirmReassign) {
                            return [
                                'conflict' => true,
                                'warehouse_id' => $warehouseId,
                                'current_clinic_id' => $currentOwner->clinic_id,
                                'warehouse_name' => $currentOwner->name,
                                'current_clinic_name' => $currentOwner->clinic?->name,
                            ];
                        }

                        Warehouse::where('id', $warehouseId)->update(['clinic_id' => $clinic->id]);
                    }
                }

                $clinic->update($data);

                return $clinic->fresh(['warehouse']);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['model_id' => $clinic->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Clinic $clinic): void
    {
        try {
            DB::transaction(function () use ($clinic) {
                $clinic->delete();
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['model_id' => $clinic->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
