<?php

namespace Modules\Patient\Services;

use Modules\Auth\Models\User;
use Modules\Patient\Models\MedicalRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class MedicalRecordService
{
    public function getByLead(int $leadId, ?User $user = null): Collection
    {
        try {
            $query = MedicalRecord::where('lead_id', $leadId);

            if ($user && !$user->can('view_any_medical_record')) {
                $query->where('uploaded_by', $user->id);
            }

            return $query->orderBy('created_at', 'desc')->get();
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function get(int $id, ?User $user = null): MedicalRecord
    {
        try {
            $query = MedicalRecord::where('id', $id);

            if ($user && !$user->can('view_any_medical_record')) {
                $query->where('uploaded_by', $user->id);
            }

            return $query->firstOrFail();
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

    public function upload(int $leadId, array $data): MedicalRecord
    {
        try {
            return DB::transaction(function () use ($leadId, $data) {
                $file = $data['file'];

                $path = $file->store('medical-records/' . $leadId);

                return MedicalRecord::create([
                    'lead_id'       => $leadId,
                    'type'          => $data['type'],
                    'file_path'     => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'notes'         => $data['notes'] ?? null,
                    'uploaded_by'   => auth()->id(),
                ]);
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['lead_id' => $leadId, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function update(MedicalRecord $record, array $data): MedicalRecord
    {
        try {
            return DB::transaction(function () use ($record, $data) {
                if (isset($data['file'])) {
                    Storage::disk('local')->delete($record->file_path);

                    $path = $data['file']->store('medical-records/' . $record->lead_id);

                    $record->update([
                        'file_path'     => $path,
                        'original_name' => $data['file']->getClientOriginalName(),
                        'mime_type'     => $data['file']->getMimeType(),
                        'type'          => $data['type'] ?? $record->type,
                        'notes'         => $data['notes'] ?? $record->notes,
                    ]);
                } else {
                    $record->update([
                        'type'  => $data['type'] ?? $record->type,
                        'notes' => $data['notes'] ?? $record->notes,
                    ]);
                }

                return $record->fresh();
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $record->id, 'error' => $e->getMessage(), 'data' => $data]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(MedicalRecord $record): void
    {
        try {
            DB::transaction(function () use ($record) {
                Storage::disk('local')->delete($record->file_path);
                $record->delete();
            });
        } catch (QueryException $e) {
            Log::error(__METHOD__ . ' failed', ['model_id' => $record->id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function filePath(MedicalRecord $record): string
    {
        try {
            return Storage::disk('local')->path($record->file_path);
        } catch (\Throwable $e) {
            Log::critical(__METHOD__ . ' encountered an unexpected error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
