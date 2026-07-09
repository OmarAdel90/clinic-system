<?php

namespace Modules\Clinic\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Models\User;
use Modules\Clinic\Models\Clinic;
use Modules\Warehouse\Models\Warehouse;

class ClinicService
{
    public function getAll(User $user): Collection
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

            return $query->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__.' failed', ['error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__.' encountered an unexpected error', ['error' => $e->getMessage()]);
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
